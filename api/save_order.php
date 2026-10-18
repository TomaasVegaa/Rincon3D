<?php
/**
 * API para guardar pedidos en MySQL cuando el cliente finaliza la compra
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['cart'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos o carrito vacío']);
    exit;
}

$db = getDB();
if (!$db) {
    // Si no hay BD, responder OK para no bloquear la redirección a WhatsApp
    echo json_encode(['success' => true, 'note' => 'Base de datos no disponible, pedido redirigido a WhatsApp']);
    exit;
}

try {
    $db->beginTransaction();

    $codigoPedido = 'R3D-' . strtoupper(substr(uniqid(), -6));
    $clienteNombre = trim($input['clientName'] ?? 'Cliente Anónimo');
    $clienteTelefono = trim($input['clientPhone'] ?? '');
    $modalidad = ($input['deliveryMethod'] ?? 'envio') === 'retiro' ? 'retiro' : 'envio';
    $direccion = trim($input['clientAddress'] ?? '');
    $formaPago = ($input['paymentMethod'] ?? 'transferencia') === 'efectivo' ? 'efectivo' : 'transferencia';
    $subtotal = (float)($input['subtotal'] ?? 0);
    $costoEnvio = (float)($input['shipping'] ?? 0);
    $descuento = (float)($input['discount'] ?? 0);
    $total = (float)($input['total'] ?? 0);
    $notas = trim($input['clientNotes'] ?? '');

    $stmt = $db->prepare("INSERT INTO pedidos 
        (codigo_pedido, cliente_nombre, cliente_telefono, modalidad_entrega, direccion, forma_pago, subtotal, costo_envio, descuento, total, notas, estado) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'nuevo')");
    $stmt->execute([
        $codigoPedido, $clienteNombre, $clienteTelefono, $modalidad, $direccion, $formaPago, $subtotal, $costoEnvio, $descuento, $total, $notas
    ]);
    $pedidoId = $db->lastInsertId();

    $stmtItem = $db->prepare("INSERT INTO pedido_items 
        (pedido_id, producto_id, titulo, color, personalizacion, precio_unitario, cantidad, subtotal) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($input['cart'] as $item) {
        $prodId = isset($item['productId']) ? (int)$item['productId'] : null;
        $titulo = $item['title'] ?? 'Producto 3D';
        $color = $item['color'] ?? '';
        $custom = $item['customText'] ?? '';
        $precioUnit = (float)($item['price'] ?? 0);
        $cant = max(1, (int)($item['quantity'] ?? 1));
        $itemSubtotal = $precioUnit * $cant;

        $stmtItem->execute([
            $pedidoId, $prodId, $titulo, $color, $custom, $precioUnit, $cant, $itemSubtotal
        ]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'codigoPedido' => $codigoPedido,
        'pedidoId' => $pedidoId
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error al guardar pedido en BD: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
