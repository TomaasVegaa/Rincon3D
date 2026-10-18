<?php
require_once __DIR__ . '/auth.php';
requireAuth();

$db = getDB();
$message = '';
$messageType = 'success';

// Guardar configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'store_name' => trim($_POST['store_name'] ?? 'Rincón 3D'),
        'store_tagline' => trim($_POST['store_tagline'] ?? ''),
        'whatsapp_phone' => trim($_POST['whatsapp_phone'] ?? ''),
        'shipping_cost' => (float)($_POST['shipping_cost'] ?? 3500),
        'free_shipping_threshold' => (float)($_POST['free_shipping_threshold'] ?? 35000),
        'bank_alias' => trim($_POST['bank_alias'] ?? ''),
        'bank_cbu' => trim($_POST['bank_cbu'] ?? ''),
        'bank_holder' => trim($_POST['bank_holder'] ?? ''),
        'bank_discount_percent' => (float)($_POST['bank_discount_percent'] ?? 10),
        'banner_visible' => isset($_POST['banner_visible']) ? 1 : 0,
        'banner_title' => trim($_POST['banner_title'] ?? ''),
        'banner_description' => trim($_POST['banner_description'] ?? '')
    ];

    // 1. Guardar en MySQL si está disponible
    $savedInDb = false;
    if ($db) {
        try {
            $stmt = $db->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            foreach ($settings as $k => $v) {
                $stmt->execute([$k, (string)$v]);
            }
            $savedInDb = true;
        } catch (Throwable $e) {
            error_log("Error guardando configuracion en MySQL: " . $e->getMessage());
        }
    }

    // 2. Guardar SIEMPRE en data/settings.json (respaldo local y fallback)
    $settingsPath = __DIR__ . '/../data/settings.json';
    file_put_contents($settingsPath, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    header("Location: configuracion.php?saved=1&storage=" . ($savedInDb ? 'mysql' : 'json'));
    exit;
}

if (isset($_GET['saved'])) {
    if (isset($_GET['storage']) && $_GET['storage'] === 'json') {
        $message = "✨ ¡Configuración guardada exitosamente en el archivo local de la tienda!";
    } else {
        $message = "✨ ¡Configuración guardada correctamente en la Base de Datos y archivo local!";
    }
    $messageType = 'success';
}

// Cargar valores actuales
$current = [
    'store_name' => STORE_NAME,
    'store_tagline' => STORE_TAGLINE,
    'whatsapp_phone' => WHATSAPP_PHONE,
    'shipping_cost' => SHIPPING_COST_DEFAULT,
    'free_shipping_threshold' => FREE_SHIPPING_THRESHOLD,
    'bank_alias' => BANK_ALIAS,
    'bank_cbu' => BANK_CBU,
    'bank_holder' => BANK_HOLDER,
    'bank_discount_percent' => BANK_DISCOUNT_PERCENT,
    'banner_visible' => BANNER_VISIBLE,
    'banner_title' => BANNER_TITLE,
    'banner_description' => BANNER_DESCRIPTION
];

$settingsFromDb = false;
if ($db) {
    try {
        $stmt = $db->query("SELECT clave, valor FROM configuracion");
        while ($r = $stmt->fetch()) {
            if (isset($current[$r['clave']])) {
                $current[$r['clave']] = $r['valor'];
            }
        }
        $settingsFromDb = true;
    } catch (Throwable $e) {}
}

if (!$settingsFromDb) {
    $settingsPath = __DIR__ . '/../data/settings.json';
    if (file_exists($settingsPath)) {
        $saved = json_decode(file_get_contents($settingsPath), true);
        if (is_array($saved)) {
            $current = array_merge($current, $saved);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajustes de Tienda | Rincón 3D Admin</title>
  <link rel="icon" type="image/jpeg" href="../assets/img/logo.jpg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
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
        <a href="pedidos.php" class="admin-nav-link">🛍️ Pedidos</a>
        <a href="configuracion.php" class="admin-nav-link active">⚙️ Ajustes</a>
      </nav>

      <div class="admin-user-menu">
        <a href="../index.php" target="_blank" class="store-preview-btn">
          <span>Ver Tienda</span> ↗
        </a>
        <a href="logout.php" class="logout-btn">Salir</a>
      </div>
    </div>
  </header>

  <main class="admin-main">
    <div class="page-header">
      <div>
        <h1 class="page-title">Ajustes & Configuración del Front</h1>
        <p class="page-desc">Modifica en tiempo real los datos que se muestran a tus clientes en la tienda.</p>
      </div>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert-box alert-<?php echo $messageType; ?>">
        <span><?php echo htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" class="form-card" style="max-width: 900px;">
      
      <!-- Sección WhatsApp & Marca -->
      <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--admin-wine); margin-bottom: 1rem;">
        📲 WhatsApp & Marca
      </h3>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Teléfono de WhatsApp para recibir Pedidos *</label>
          <input type="text" name="whatsapp_phone" class="form-control" required value="<?php echo htmlspecialchars($current['whatsapp_phone']); ?>" placeholder="5491122334455">
          <span style="font-size: 0.75rem; color: var(--admin-muted);">Formato internacional sin espacios ni signos (Ejemplo: 5491123456789)</span>
        </div>

        <div class="form-group">
          <label class="form-label">Nombre de la Tienda</label>
          <input type="text" name="store_name" class="form-control" value="<?php echo htmlspecialchars($current['store_name']); ?>">
        </div>

        <div class="form-group full-width">
          <label class="form-label">Eslogan o Subtítulo</label>
          <input type="text" name="store_tagline" class="form-control" value="<?php echo htmlspecialchars($current['store_tagline']); ?>">
        </div>
      </div>

      <hr style="border: none; border-top: 1px solid var(--admin-blush-border); margin: 2rem 0;">

      <!-- Sección Banner Front -->
      <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--admin-wine); margin-bottom: 1rem;">
        🎨 Banner de Presentación del Front
      </h3>

      <div class="form-grid">
        <div class="form-group full-width">
          <label class="form-check">
            <input type="checkbox" name="banner_visible" value="1" <?php echo $current['banner_visible'] == '1' ? 'checked' : ''; ?>>
            <span style="font-weight: 600;">Mostrar Banner de Marca en la vista principal</span>
          </label>
        </div>

        <div class="form-group full-width">
          <label class="form-label">Título del Banner</label>
          <input type="text" name="banner_title" class="form-control" value="<?php echo htmlspecialchars($current['banner_title']); ?>">
        </div>

        <div class="form-group full-width">
          <label class="form-label">Descripción del Banner</label>
          <textarea name="banner_description" class="form-control" rows="2"><?php echo htmlspecialchars($current['banner_description']); ?></textarea>
        </div>
      </div>

      <hr style="border: none; border-top: 1px solid var(--admin-blush-border); margin: 2rem 0;">

      <!-- Sección Precios y Envíos -->
      <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--admin-wine); margin-bottom: 1rem;">
        🚚 Envíos y Pagos
      </h3>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Costo de Envío Estándar ($)</label>
          <input type="number" step="0.01" name="shipping_cost" class="form-control" value="<?php echo htmlspecialchars($current['shipping_cost']); ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Monto para Envío Gratis ($)</label>
          <input type="number" step="0.01" name="free_shipping_threshold" class="form-control" value="<?php echo htmlspecialchars($current['free_shipping_threshold']); ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Descuento por Transferencia (%)</label>
          <input type="number" step="0.1" name="bank_discount_percent" class="form-control" value="<?php echo htmlspecialchars($current['bank_discount_percent']); ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Alias Bancario / Mercado Pago</label>
          <input type="text" name="bank_alias" class="form-control" value="<?php echo htmlspecialchars($current['bank_alias']); ?>">
        </div>

        <div class="form-group">
          <label class="form-label">CBU Bancario</label>
          <input type="text" name="bank_cbu" class="form-control" value="<?php echo htmlspecialchars($current['bank_cbu']); ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Titular de la Cuenta</label>
          <input type="text" name="bank_holder" class="form-control" value="<?php echo htmlspecialchars($current['bank_holder']); ?>">
        </div>
      </div>

      <div style="margin-top: 2rem;">
        <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2.5rem; font-size: 1rem;">
          💾 Guardar Ajustes
        </button>
      </div>

    </form>
  </main>
</body>
</html>
