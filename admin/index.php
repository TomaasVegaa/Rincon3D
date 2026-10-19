<?php
require_once __DIR__ . '/auth.php';
requireAuth();

$db = getDB();
$message = '';
$messageType = 'success';

// Acción rápida: Cambiar estado (Activar / Pausar)
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($db) {
        try {
            $stmt = $db->prepare("UPDATE productos SET activo = 1 - activo WHERE id = ?");
            $stmt->execute([$id]);
        } catch (Throwable $e) {}
    }
    toggleProductJson($id);
    $message = "Estado del producto actualizado.";
}

// Acción: Eliminar producto
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($db) {
        try {
            $stmt = $db->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->execute([$id]);
        } catch (Throwable $e) {}
    }
    deleteProductFromJson($id);
    $message = "Producto eliminado correctamente.";
}

// Consultar métricas y listado de productos
$products = [];
$totalProducts = 0;
$totalOrders = 0;
$totalSales = 0;

if ($db) {
    try {
        // Métricas
        $totalProducts = (int)$db->query("SELECT COUNT(*) FROM productos")->fetchColumn();
        $totalOrders = (int)$db->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
        $totalSales = (float)$db->query("SELECT COALESCE(SUM(total), 0) FROM pedidos")->fetchColumn();

        // Listado
        $sql = "SELECT p.*, c.nombre AS categoria_nombre,
                (SELECT url_imagen FROM producto_imagenes WHERE producto_id = p.id ORDER BY es_principal DESC, orden ASC LIMIT 1) AS imagen_principal
                FROM productos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                ORDER BY p.orden ASC, p.id DESC";
        $products = $db->query($sql)->fetchAll();
    } catch (Throwable $e) {
        $message = "⚠️ Las tablas aún no están creadas en la base de datos. Recuerda importar database.sql en phpMyAdmin.";
        $messageType = 'error';
        $db = null; // Activar fallback
    }
}

if (!$db) {
    // Modo fallback con JSON
    $allJson = getAllProductsFromJson();
    $totalProducts = count($allJson);
    foreach ($allJson as $p) {
        $products[] = [
            'id' => $p['id'],
            'titulo' => $p['title'],
            'categoria_nombre' => $p['categoryName'] ?? 'General',
            'precio' => $p['price'],
            'badge' => $p['badge'] ?? '',
            'customizable' => !empty($p['customizable']) ? 1 : 0,
            'activo' => isset($p['active']) ? ($p['active'] ? 1 : 0) : 1,
            'imagen_principal' => $p['images'][0] ?? '../assets/img/logo.jpg'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Productos | Panel Admin <?php echo STORE_NAME; ?></title>
  <link rel="icon" type="image/jpeg" href="../assets/img/logo.jpg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body>

  <!-- Barra de navegación del Admin -->
  <header class="admin-navbar">
    <div class="admin-nav-container">
      <div class="admin-brand">
        <img src="../assets/img/logo.jpg" alt="Logo Rincón 3D">
        <span class="admin-brand-title">Rincón 3D Panel</span>
      </div>

      <nav class="admin-links">
        <a href="index.php" class="admin-nav-link active">📦 Productos</a>
        <a href="categorias.php" class="admin-nav-link">🏷️ Categorías</a>
        <a href="pedidos.php" class="admin-nav-link">🛍️ Pedidos</a>
        <a href="configuracion.php" class="admin-nav-link">⚙️ Ajustes</a>
      </nav>

      <div class="admin-user-menu">
        <a href="../index.php" target="_blank" class="store-preview-btn">
          <span>Ver Tienda</span> ↗
        </a>
        <a href="logout.php" class="logout-btn">Salir</a>
      </div>
    </div>
  </header>

  <!-- Contenido -->
  <main class="admin-main">
    <div class="page-header">
      <div>
        <h1 class="page-title">Catálogo de Productos 3D</h1>
        <p class="page-desc">Agrega, edita, cambia precios y gestiona las fotos de tus impresiones 3D.</p>
      </div>
      <a href="producto.php" class="btn btn-primary">
        <span>+ Agregar Nuevo Producto</span>
      </a>
    </div>

    <?php if (!$db && empty($message)): ?>
      <div class="alert-box" style="background: #FFFBEB; border-left: 4px solid #F59E0B; color: #92400E; margin-bottom: 1.25rem; font-size: 0.88rem; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm);">
        <span>💡 <strong>Modo Catálogo Local Activo:</strong> MySQL no está conectado actualmente. El panel está funcionando y guardando directamente sobre <code>data/products.json</code>.</span>
      </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
      <div class="alert-box alert-<?php echo $messageType; ?>">
        <span><?php echo htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>

    <!-- Tarjetas de métricas -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-label">Productos en Catálogo</div>
        <div class="kpi-val"><?php echo $totalProducts; ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Pedidos Registrados</div>
        <div class="kpi-val"><?php echo $totalOrders; ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Ventas Registradas</div>
        <div class="kpi-val"><?php echo APP_CURRENCY . number_format($totalSales, 0, ',', '.'); ?></div>
      </div>
    </div>

    <!-- Tabla de productos -->
    <div class="card-table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 70px;">Foto</th>
            <th>Producto</th>
            <th>Categoría</th>
            <th>Precio</th>
            <th>Personalizable</th>
            <th>Estado</th>
            <th style="text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 2rem; color: var(--admin-muted);">
                No hay productos cargados todavía. Haz clic en "+ Agregar Nuevo Producto" para comenzar.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $p): 
              $imgSrc = $p['imagen_principal'] ?? '';
              $localFile = __DIR__ . '/../' . ltrim(str_replace('../', '', $imgSrc), '/');
              if (empty($imgSrc) || $imgSrc === 'assets/img/logo.jpg' || !file_exists($localFile)) {
                  if ($p['id'] == 1 && file_exists(__DIR__ . '/../assets/img/florero_japandei_1.jpg')) {
                      $imgSrc = 'assets/img/florero_japandei_1.jpg';
                  } elseif ($p['id'] == 2 && file_exists(__DIR__ . '/../assets/img/florero_japandi_1.jpg')) {
                      $imgSrc = 'assets/img/florero_japandi_1.jpg';
                  } else {
                      $imgSrc = 'assets/img/logo.jpg';
                  }
              }
              $thumb = (strpos($imgSrc, 'assets/') === 0 || strpos($imgSrc, 'uploads/') === 0) ? '../' . $imgSrc : $imgSrc;
            ?>
              <tr>
                <td>
                  <img src="<?php echo htmlspecialchars($thumb); ?>" alt="Miniatura" class="table-thumb" onerror="this.src='../assets/img/logo.jpg'">
                </td>
                <td>
                  <strong style="color: var(--admin-wine);"><?php echo htmlspecialchars($p['titulo']); ?></strong>
                  <?php if (!empty($p['badge'])): ?>
                    <span class="badge badge-wine" style="margin-left: 4px;"><?php echo htmlspecialchars($p['badge']); ?></span>
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($p['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                <td><strong><?php echo APP_CURRENCY . number_format($p['precio'], 0, ',', '.'); ?></strong></td>
                <td>
                  <?php if ($p['customizable']): ?>
                    <span class="badge badge-success">✨ Sí</span>
                  <?php else: ?>
                    <span style="color: var(--admin-muted); font-size: 0.8rem;">No</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="index.php?action=toggle&id=<?php echo $p['id']; ?>" title="Clic para cambiar estado">
                    <?php if ($p['activo']): ?>
                      <span class="badge badge-success">Activo</span>
                    <?php else: ?>
                      <span class="badge badge-muted">Pausado</span>
                    <?php endif; ?>
                  </a>
                </td>
                <td style="text-align: right;">
                  <a href="producto.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm" style="margin-right: 4px;">
                    Editar
                  </a>
                  <a href="index.php?action=delete&id=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este producto? Esta acción no se puede deshacer.')">
                    Eliminar
                  </a>
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
