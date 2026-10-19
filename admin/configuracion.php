<?php
require_once __DIR__ . '/auth.php';
requireAuth();

$db = getDB();
$message = '';
$messageType = 'success';

// Guardar configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0755, true);
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'jfif', 'gif'];

    // 1. Procesar foto para el Banner de la tienda (Front)
    $bannerImage = trim($_POST['banner_image'] ?? 'assets/img/admin_bg_3dprinter.jpg');
    if (empty($bannerImage)) {
        $bannerImage = 'assets/img/admin_bg_3dprinter.jpg';
    }

    if (isset($_FILES['banner_image_file']) && $_FILES['banner_image_file']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['banner_image_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['banner_image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'banner_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($tmp, $uploadsDir . '/' . $filename)) {
                $bannerImage = 'uploads/' . $filename;
            }
        }
    }

    // 2. Procesar imagen de fondo para el panel de administración
    $adminBgImage = trim($_POST['admin_bg_image'] ?? 'assets/img/admin_bg_3dprinter.jpg');
    if (empty($adminBgImage)) {
        $adminBgImage = 'assets/img/admin_bg_3dprinter.jpg';
    }

    if (isset($_FILES['admin_bg_file']) && $_FILES['admin_bg_file']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['admin_bg_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['admin_bg_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'admin_bg_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($tmp, $uploadsDir . '/' . $filename)) {
                $adminBgImage = 'uploads/' . $filename;
            }
        }
    }

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
        'banner_description' => trim($_POST['banner_description'] ?? ''),
        'banner_image' => $bannerImage,
        'admin_bg_image' => $adminBgImage
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
    'banner_description' => BANNER_DESCRIPTION,
    'banner_image' => BANNER_IMAGE,
    'admin_bg_image' => ADMIN_BG_IMAGE
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
        <a href="pedidos.php" class="admin-nav-link">🛍️ Pedidos</a>
        <a href="configuracion.php" class="admin-nav-link active">⚙️ Ajustes</a>
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
        <h1 class="page-title">Ajustes & Configuración del Front</h1>
        <p class="page-desc">Modifica en tiempo real los datos que se muestran a tus clientes en la tienda.</p>
      </div>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert-box alert-<?php echo $messageType; ?>">
        <span><?php echo htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-card" style="max-width: 900px;">
      
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

        <div class="form-group full-width">
          <label class="form-label">Foto del Banner de la Tienda (Frontend)</label>
          <input type="text" name="banner_image" id="banner_image_input" class="form-control" value="<?php echo htmlspecialchars($current['banner_image'] ?? 'assets/img/admin_bg_3dprinter.jpg'); ?>" placeholder="assets/img/admin_bg_3dprinter.jpg">
          <span style="font-size: 0.75rem; color: var(--admin-muted);">Foto principal que se muestra en el banner al ingresar a la tienda.</span>
        </div>

        <div class="form-group full-width">
          <label class="form-label">Vista Previa & Subida de Foto para el Banner</label>
          <div style="display: flex; gap: 1.25rem; align-items: center; flex-wrap: wrap;">
            <div style="width: 170px; height: 110px; border-radius: var(--radius-sm); overflow: hidden; border: 2px solid var(--admin-blush-border); box-shadow: 0 4px 12px rgba(101,18,38,0.1); background: #f0f0f0;">
              <?php 
                $bImgVal = $current['banner_image'] ?? 'assets/img/admin_bg_3dprinter.jpg';
                $bImgSrc = (strpos($bImgVal, 'http') === 0 || strpos($bImgVal, 'data:') === 0) ? $bImgVal : '../' . ltrim($bImgVal, '/');
              ?>
              <img id="banner_preview_img" src="<?php echo htmlspecialchars($bImgSrc); ?>" alt="Banner Preview" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/img/logo.jpg'">
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
              <input type="file" name="banner_image_file" id="banner_image_file_input" accept="image/*" style="display: none;">
              <button type="button" class="btn btn-secondary" onclick="document.getElementById('banner_image_file_input').click()">
                📷 Subir Nueva Foto para el Banner
              </button>
              <button type="button" class="btn btn-secondary" onclick="use3dPrinterForBanner()">
                🔄 Usar Foto Máquina 3D
              </button>
              <button type="button" class="btn btn-secondary" onclick="useLogoForBanner()">
                🏷️ Usar Logo Rincón 3D
              </button>
            </div>
          </div>
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

      <hr style="border: none; border-top: 1px solid var(--admin-blush-border); margin: 2rem 0;">

      <!-- Sección Fondo Temático del Admin -->
      <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--admin-wine); margin-bottom: 0.5rem;">
        🖼️ Fondo Temático del Panel (Máquina 3D / Taller)
      </h3>
      <p style="font-size: 0.85rem; color: var(--admin-muted); margin-bottom: 1.25rem;">
        Personaliza la imagen de fondo de todo el panel de administración. El sistema le aplica automáticamente una capa translúcida para que la máquina 3D decore el fondo con estilo sutil, sin estorbar la lectura de datos, tablas ni botones.
      </p>

      <div class="form-grid">
        <div class="form-group full-width">
          <label class="form-label">Ruta o URL de la Imagen de Fondo</label>
          <input type="text" name="admin_bg_image" id="admin_bg_image_input" class="form-control" value="<?php echo htmlspecialchars($current['admin_bg_image'] ?? 'assets/img/admin_bg_3dprinter.jpg'); ?>" placeholder="assets/img/admin_bg_3dprinter.jpg">
          <span style="font-size: 0.75rem; color: var(--admin-muted);">Predeterminada: <code>assets/img/admin_bg_3dprinter.jpg</code> (Máquina de impresión 3D en taller)</span>
        </div>

        <div class="form-group full-width">
          <label class="form-label">Vista Previa & Carga de Imagen</label>
          <div style="display: flex; gap: 1.25rem; align-items: center; flex-wrap: wrap;">
            <div style="width: 170px; height: 100px; border-radius: var(--radius-sm); overflow: hidden; border: 2px solid var(--admin-blush-border); box-shadow: 0 4px 12px rgba(101,18,38,0.1); background: #f0f0f0;">
              <?php 
                $bgImgVal = $current['admin_bg_image'] ?? 'assets/img/admin_bg_3dprinter.jpg';
                $bgImgSrc = (strpos($bgImgVal, 'http') === 0 || strpos($bgImgVal, 'data:') === 0) ? $bgImgVal : '../' . ltrim($bgImgVal, '/');
              ?>
              <img id="admin_bg_preview_img" src="<?php echo htmlspecialchars($bgImgSrc); ?>" alt="Fondo Admin" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/img/admin_bg_3dprinter.jpg'">
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
              <input type="file" name="admin_bg_file" id="admin_bg_file_input" accept="image/*" style="display: none;">
              <button type="button" class="btn btn-secondary" onclick="document.getElementById('admin_bg_file_input').click()">
                📷 Subir Nueva Foto desde tu Dispositivo
              </button>
              <button type="button" class="btn btn-secondary" onclick="resetDefaultBg()">
                🔄 Restaurar Máquina 3D por Defecto
              </button>
            </div>
          </div>
        </div>
      </div>

      <div style="margin-top: 2rem;">
        <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2.5rem; font-size: 1rem;">
          💾 Guardar Ajustes
        </button>
      </div>

    </form>
  </main>

  <script>
    // 1. Manejo de preview para Fondo del Admin
    const bgFileInput = document.getElementById('admin_bg_file_input');
    const bgImgInput = document.getElementById('admin_bg_image_input');
    const bgPreviewImg = document.getElementById('admin_bg_preview_img');

    if (bgFileInput) {
      bgFileInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
          const reader = new FileReader();
          reader.onload = function(evt) {
            if (bgPreviewImg) bgPreviewImg.src = evt.target.result;
          };
          reader.readAsDataURL(this.files[0]);
        }
      });
    }

    if (bgImgInput) {
      bgImgInput.addEventListener('input', function() {
        const val = this.value.trim();
        if (val) {
          const src = (val.startsWith('http') || val.startsWith('data:')) ? val : '../' + val.replace(/^\//, '');
          if (bgPreviewImg) bgPreviewImg.src = src;
        }
      });
    }

    function resetDefaultBg() {
      if (bgImgInput) bgImgInput.value = 'assets/img/admin_bg_3dprinter.jpg';
      if (bgPreviewImg) bgPreviewImg.src = '../assets/img/admin_bg_3dprinter.jpg';
      if (bgFileInput) bgFileInput.value = '';
    }

    // 2. Manejo de preview para Foto del Banner
    const bannerFileInput = document.getElementById('banner_image_file_input');
    const bannerImgInput = document.getElementById('banner_image_input');
    const bannerPreviewImg = document.getElementById('banner_preview_img');

    if (bannerFileInput) {
      bannerFileInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
          const reader = new FileReader();
          reader.onload = function(evt) {
            if (bannerPreviewImg) bannerPreviewImg.src = evt.target.result;
          };
          reader.readAsDataURL(this.files[0]);
        }
      });
    }

    if (bannerImgInput) {
      bannerImgInput.addEventListener('input', function() {
        const val = this.value.trim();
        if (val) {
          const src = (val.startsWith('http') || val.startsWith('data:')) ? val : '../' + val.replace(/^\//, '');
          if (bannerPreviewImg) bannerPreviewImg.src = src;
        }
      });
    }

    function use3dPrinterForBanner() {
      if (bannerImgInput) bannerImgInput.value = 'assets/img/admin_bg_3dprinter.jpg';
      if (bannerPreviewImg) bannerPreviewImg.src = '../assets/img/admin_bg_3dprinter.jpg';
      if (bannerFileInput) bannerFileInput.value = '';
    }

    function useLogoForBanner() {
      if (bannerImgInput) bannerImgInput.value = 'assets/img/logo.jpg';
      if (bannerPreviewImg) bannerPreviewImg.src = '../assets/img/logo.jpg';
      if (bannerFileInput) bannerFileInput.value = '';
    }
  </script>
</body>
</html>
