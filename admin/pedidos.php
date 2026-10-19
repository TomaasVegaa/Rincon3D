<?php
require_once __DIR__ . '/auth.php';
requireAuth();

$db = getDB();
$message = '';

// Cambiar estado de un pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id'], $_POST['nuevo_estado'])) {
    $pedId = (int)$_POST['pedido_id'];
    $nuevoEstado = $_POST['nuevo_estado'];
    $estadosValidos = ['nuevo', 'en_produccion', 'listo', 'entregado', 'cancelado'];

    if (in_array($nuevoEstado, $estadosValidos) && $db) {
        $stmt = $db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->execute([$nuevoEstado, $pedId]);
        $message = "Estado del pedido actualizado.";
    }
}

// Consultar pedidos con sus items
$pedidos = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM pedidos ORDER BY id DESC");
        $pedidos = $stmt->fetchAll();

        // Obtener items por pedido
        if (!empty($pedidos)) {
            $pedIds = array_column($pedidos, 'id');
            $placeholders = implode(',', array_fill(0, count($pedIds), '?'));
            $stmtItems = $db->prepare("SELECT * FROM pedido_items WHERE pedido_id IN ($placeholders)");
            $stmtItems->execute($pedIds);
            $itemsByPedido = [];
            while ($item = $stmtItems->fetch()) {
                $itemsByPedido[$item['pedido_id']][] = $item;
            }

            foreach ($pedidos as &$ped) {
                $ped['items'] = $itemsByPedido[$ped['id']] ?? [];
            }
            unset($ped);
        }
    } catch (Exception $e) {
        $message = "Error consultando pedidos: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedidos Recibidos | Rincón 3D Admin</title>
  <link rel="icon" type="image/jpeg" href="../assets/img/logo.jpg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
  <?php echo renderAdminBgStyle(); ?>
</head>
<body>

  <header class="admin-navbar">
    <div class="admin-nav-container">
      <div class="admin-brand">
        <img src="../assets/img/logo.jpg" alt="Logo Rincón 3D">
        <span class="admin-brand-title">Rincón 3D Panel</span>
      </div>

      <nav class="admin-links">
        <a href="index.php" class="admin-nav-link">📦 Productos</a>
        <a href="categorias.php" class="admin-nav-link">🏷️ Categorías</a>
        <a href="pedidos.php" class="admin-nav-link active">🛍️ Pedidos</a>
        <a href="configuracion.php" class="admin-nav-link">⚙️ Ajustes</a>
      </nav>

      <div class="admin-user-menu">
        <a href="../index.php?t=<?php echo time(); ?>" target="_blank" class="store-preview-btn">
          <span>Ver Tienda</span> ↗
        </a>
        <a href="logout.php" class="logout-btn">Salir</a>
      </div>
    </div>
  </header>

  <main class="admin-main">
    <div class="page-header">
      <div>
        <h1 class="page-title">Historial de Pedidos</h1>
        <p class="page-desc">Registros de compras generadas a través del carrito y enviadas a WhatsApp.</p>
      </div>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert-box alert-success">
        <span><?php echo htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>

    <div class="card-table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Código</th>
            <th>Fecha</th>
            <th>Cliente & Contacto</th>
            <th>Entrega / Dirección</th>
            <th>Pago</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Detalle</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pedidos)): ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 3rem; color: var(--admin-muted);">
                Aún no hay pedidos registrados. Cuando los clientes finalicen una compra en la tienda web, aparecerán aquí automáticamente.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($pedidos as $ped): 
              $cleanPhone = preg_replace('/[^0-9]/', '', $ped['cliente_telefono']);
            ?>
              <tr>
                <td><strong>#<?php echo htmlspecialchars($ped['codigo_pedido']); ?></strong></td>
                <td style="font-size: 0.8rem; color: var(--admin-muted);">
                  <?php echo date('d/m/Y H:i', strtotime($ped['created_at'])); ?>
                </td>
                <td>
                  <strong><?php echo htmlspecialchars($ped['cliente_nombre']); ?></strong><br>
                  <a href="https://wa.me/<?php echo $cleanPhone; ?>" target="_blank" style="color: #1E7E4E; font-weight: 600; font-size: 0.8rem;">
                    📲 <?php echo htmlspecialchars($ped['cliente_telefono']); ?>
                  </a>
                </td>
                <td style="font-size: 0.85rem;">
                  <?php if ($ped['modalidad_entrega'] === 'retiro'): ?>
                    <span class="badge badge-warning">Retiro Taller</span>
                  <?php else: ?>
                    <span class="badge badge-success">Envío Domicilio</span>
                    <div style="font-size: 0.75rem; color: var(--admin-muted); margin-top: 3px; max-width: 220px;">
                      <?php echo htmlspecialchars($ped['direccion']); ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <span style="font-size: 0.85rem; text-transform: capitalize; font-weight: 600;">
                    <?php echo $ped['forma_pago']; ?>
                  </span>
                </td>
                <td>
                  <strong style="color: var(--admin-wine); font-size: 1rem;">
                    <?php echo APP_CURRENCY . number_format($ped['total'], 0, ',', '.'); ?>
                  </strong>
                </td>
                <td>
                  <form method="POST" style="margin: 0;">
                    <input type="hidden" name="pedido_id" value="<?php echo $ped['id']; ?>">
                    <select name="nuevo_estado" onchange="this.form.submit()" class="form-control" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; width: auto;">
                      <option value="nuevo" <?php echo $ped['estado'] === 'nuevo' ? 'selected' : ''; ?>>🟡 Nuevo</option>
                      <option value="en_produccion" <?php echo $ped['estado'] === 'en_produccion' ? 'selected' : ''; ?>>⚙️ En Producción 3D</option>
                      <option value="listo" <?php echo $ped['estado'] === 'listo' ? 'selected' : ''; ?>>🟢 Listo para Entrega</option>
                      <option value="entregado" <?php echo $ped['estado'] === 'entregado' ? 'selected' : ''; ?>>✅ Entregado</option>
                      <option value="cancelado" <?php echo $ped['estado'] === 'cancelado' ? 'selected' : ''; ?>>❌ Cancelado</option>
                    </select>
                  </form>
                </td>
                <td>
                  <details style="cursor: pointer; font-size: 0.8rem;">
                    <summary style="color: var(--admin-wine); font-weight: 600;">Ver Items (<?php echo count($ped['items']); ?>)</summary>
                    <div style="margin-top: 0.5rem; background: var(--admin-bg); padding: 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--admin-blush-border); min-width: 200px;">
                      <?php foreach ($ped['items'] as $item): ?>
                        <div style="margin-bottom: 0.4rem; padding-bottom: 0.3rem; border-bottom: 1px dashed var(--admin-blush-border);">
                          <strong><?php echo htmlspecialchars($item['titulo']); ?></strong> x<?php echo $item['cantidad']; ?><br>
                          <?php if (!empty($item['color'])): ?>
                            <span style="color: var(--admin-muted);">Color: <?php echo htmlspecialchars($item['color']); ?></span><br>
                          <?php endif; ?>
                          <?php if (!empty($item['personalizacion'])): ?>
                            <span style="color: var(--admin-wine);">Personalización: "<?php echo htmlspecialchars($item['personalizacion']); ?>"</span><br>
                          <?php endif; ?>
                          <span>Subtotal: <?php echo APP_CURRENCY . number_format($item['subtotal'], 0, ',', '.'); ?></span>
                        </div>
                      <?php endforeach; ?>
                      <?php if (!empty($ped['notas'])): ?>
                        <div style="margin-top: 0.3rem; font-style: italic; color: var(--admin-muted);">
                          Nota: <?php echo htmlspecialchars($ped['notas']); ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </details>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
