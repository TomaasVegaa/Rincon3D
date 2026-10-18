<?php
/**
 * Página de Detalle de Producto - 100% Optimizada para SEO
 * Incluye Schema.org JSON-LD (Rich Snippets), OpenGraph y URLs amigables
 */
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once __DIR__ . '/config.php';

// Obtener slug o ID
$slug = trim($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);

$db = getDB();
$product = null;

if ($db && (!empty($slug) || $id > 0)) {
    try {
        if (!empty($slug)) {
            $stmt = $db->prepare("SELECT p.*, c.slug AS cat_slug, c.nombre AS cat_nombre 
                                  FROM productos p 
                                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                                  WHERE p.slug = ? AND p.activo = 1 LIMIT 1");
            $stmt->execute([$slug]);
        } else {
            $stmt = $db->prepare("SELECT p.*, c.slug AS cat_slug, c.nombre AS cat_nombre 
                                  FROM productos p 
                                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                                  WHERE p.id = ? AND p.activo = 1 LIMIT 1");
            $stmt->execute([$id]);
        }
        $row = $stmt->fetch();

        if ($row) {
            $pid = (int)$row['id'];

            // Imágenes
            $stmtImgs = $db->prepare("SELECT url_imagen FROM producto_imagenes WHERE producto_id = ? ORDER BY orden ASC");
            $stmtImgs->execute([$pid]);
            $imgs = $stmtImgs->fetchAll(PDO::FETCH_COLUMN) ?: ['assets/img/logo.jpg'];

            // Colores
            $stmtCols = $db->prepare("SELECT nombre_color FROM producto_colores WHERE producto_id = ?");
            $stmtCols->execute([$pid]);
            $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

            // Specs
            $stmtSpecs = $db->prepare("SELECT clave, valor FROM producto_especificaciones WHERE producto_id = ?");
            $stmtSpecs->execute([$pid]);
            $specs = $stmtSpecs->fetchAll(PDO::FETCH_KEY_PAIR);

            $product = [
                'id' => $pid,
                'title' => $row['titulo'],
                'slug' => $row['slug'],
                'category' => $row['cat_slug'] ?: 'general',
                'categoryName' => $row['cat_nombre'] ?: 'General',
                'price' => (float)$row['precio'],
                'badge' => $row['badge'],
                'customizable' => (bool)$row['customizable'],
                'description' => $row['descripcion'],
                'tiempo_produccion' => $row['tiempo_produccion'],
                'images' => $imgs,
                'colors' => $cols,
                'specs' => $specs
            ];
        }
    } catch (Throwable $e) {
        error_log("Error buscando producto por SEO: " . $e->getMessage());
    }
}

// Fallback desde JSON si no hay BD o no se encontró
if (!$product) {
    $all = getProducts();
    foreach ($all as $p) {
        $pSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $p['title'])));
        if ($pSlug === $slug || $p['id'] === $id) {
            $product = $p;
            $product['slug'] = $pSlug;
            break;
        }
    }
}

// Si no existe, redirigir al catálogo
if (!$product) {
    header("Location: index.php");
    exit;
}

// Datos para Meta Tags y SEO
$pageTitle = $product['title'] . " en Impresión 3D | " . STORE_NAME;
$metaDescription = "Comprá " . $product['title'] . " fabricado en impresión 3D de alta precisión. " . mb_substr($product['description'], 0, 140) . "... Envíos a todo el país y 10% OFF en transferencia.";
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
$protocol = $isHttps ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'rincon3d.ct.ws';
$canonicalUrl = $protocol . $host . '/producto/' . $product['slug'];
$mainImage = !empty($product['images'][0]) ? (strpos($product['images'][0], 'http') === 0 ? $product['images'][0] : $protocol . $host . '/' . ltrim($product['images'][0], '/')) : $protocol . $host . '/assets/img/logo.jpg';

// Productos relacionados para internal linking
$allProducts = getProducts();
$relatedProducts = array_filter($allProducts, function($p) use ($product) {
    return $p['id'] !== $product['id'];
});
$relatedProducts = array_slice($relatedProducts, 0, 3);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
  <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">

  <!-- Open Graph / Redes Sociales y WhatsApp -->
  <meta property="og:type" content="product">
  <meta property="og:site_name" content="<?php echo htmlspecialchars(STORE_NAME); ?>">
  <meta property="og:title" content="<?php echo htmlspecialchars($product['title']); ?> - Rincón 3D">
  <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
  <meta property="og:image" content="<?php echo htmlspecialchars($mainImage); ?>">
  <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
  <meta property="product:price:amount" content="<?php echo $product['price']; ?>">
  <meta property="product:price:currency" content="ARS">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription); ?>">
  <meta name="twitter:image" content="<?php echo htmlspecialchars($mainImage); ?>">

  <!-- Favicon -->
  <link rel="icon" type="image/jpeg" href="assets/img/logo.jpg">

  <!-- Tipografías -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="assets/css/style.css">

  <!-- SCHEMA.ORG / JSON-LD PARA GOOGLE RICH SNIPPETS -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": "<?php echo addslashes($product['title']); ?>",
    "image": [
      <?php echo implode(',', array_map(function($img) use ($protocol, $host) {
          $full = strpos($img, 'http') === 0 ? $img : $protocol . $host . '/' . ltrim($img, '/');
          return '"' . addslashes($full) . '"';
      }, $product['images'])); ?>
    ],
    "description": "<?php echo addslashes($product['description']); ?>",
    "brand": {
      "@type": "Brand",
      "name": "<?php echo addslashes(STORE_NAME); ?>"
    },
    "category": "<?php echo addslashes($product['categoryName']); ?>",
    "offers": {
      "@type": "Offer",
      "url": "<?php echo addslashes($canonicalUrl); ?>",
      "priceCurrency": "ARS",
      "price": "<?php echo $product['price']; ?>",
      "availability": "https://schema.org/InStock",
      "itemCondition": "https://schema.org/NewCondition",
      "seller": {
        "@type": "Organization",
        "name": "<?php echo addslashes(STORE_NAME); ?>"
      }
    }
  }
  </script>

  <script>
    window.APP_CONFIG = {
      whatsappPhone: "<?php echo WHATSAPP_PHONE; ?>",
      currencySymbol: "<?php echo CURRENCY_SYMBOL; ?>",
      shippingCost: <?php echo SHIPPING_COST_DEFAULT; ?>,
      freeShippingThreshold: <?php echo FREE_SHIPPING_THRESHOLD; ?>,
      bankDiscountPercent: <?php echo BANK_DISCOUNT_PERCENT; ?>
    };
    window.INITIAL_PRODUCTS = <?php echo json_encode($allProducts); ?>;
  </script>
</head>
<body>

  <!-- Barra de avisos -->
  <aside class="top-notice-bar">
    <span>✨ Envíos a todo el país</span>
    <span class="sparkle-icon">✦</span>
    <span>10% OFF abonando por Transferencia</span>
    <span class="sparkle-icon">✦</span>
    <span>Impresión 3D de alta precisión</span>
  </aside>

  <!-- Header -->
  <header class="site-header">
    <div class="container">
      <div class="nav-wrapper">
        <a href="index.php" class="brand-link" aria-label="Inicio">
          <img src="assets/img/logo.jpg" alt="Logo Rincón 3D" class="brand-logo-img">
          <div class="brand-text">
            <span class="brand-title"><?php echo STORE_NAME; ?></span>
            <span class="brand-subtitle"><?php echo STORE_TAGLINE; ?></span>
          </div>
        </a>

        <div class="header-actions">
          <a href="index.php" class="toggle-banner-btn">← Volver al Catálogo</a>
          <button type="button" id="cart-trigger-btn" class="cart-trigger-btn">
            <svg class="cart-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <span>Carrito</span>
            <span id="cart-badge-count" class="cart-badge" style="display: none;">0</span>
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- Migas de pan (Breadcrumbs) para Google y navegación -->
  <nav class="container" style="padding: 1.25rem 1.25rem 0;" aria-label="Breadcrumb">
    <div style="font-size: 0.8125rem; color: var(--text-muted);">
      <a href="index.php" style="color: var(--wine-primary); font-weight: 600;">Inicio</a>
      <span style="margin: 0 0.4rem;">›</span>
      <span style="color: var(--text-secondary);"><?php echo htmlspecialchars($product['categoryName']); ?></span>
      <span style="margin: 0 0.4rem;">›</span>
      <span style="color: var(--text-primary); font-weight: 700;"><?php echo htmlspecialchars($product['title']); ?></span>
    </div>
  </nav>

  <!-- Contenido del Producto -->
  <main class="container" style="padding: 1.5rem 1.25rem 4rem;">
    <div style="background: #FFFFFF; border: 1px solid var(--blush-border); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); padding: 2rem;">
      <div class="modal-content-grid" style="padding: 0;">
        
        <!-- Galería de Fotos -->
        <div class="modal-gallery-column">
          <div class="gallery-main-view">
            <img id="detail-main-img" class="gallery-main-image" src="<?php echo htmlspecialchars($product['images'][0] ?? 'assets/img/logo.jpg'); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
          </div>
          <?php if (count($product['images']) > 1): ?>
            <div class="gallery-thumbnails-strip">
              <?php foreach ($product['images'] as $idx => $img): ?>
                <button type="button" class="gallery-thumb-btn <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="document.getElementById('detail-main-img').src='<?php echo htmlspecialchars($img); ?>'; document.querySelectorAll('.gallery-thumb-btn').forEach(b => b.classList.remove('active')); this.classList.add('active');">
                  <img src="<?php echo htmlspecialchars($img); ?>" alt="Foto <?php echo $idx + 1; ?>">
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Información y Compra -->
        <div class="modal-info-column">
          <span class="modal-category-badge"><?php echo htmlspecialchars($product['categoryName']); ?></span>
          <h1 class="modal-title" style="font-size: 2.1rem;"><?php echo htmlspecialchars($product['title']); ?></h1>
          
          <div class="modal-price-box">
            <span class="modal-price-current"><?php echo CURRENCY_SYMBOL . number_format($product['price'], 0, ',', '.'); ?></span>
            <span class="modal-discount-pill">10% OFF en Transferencia</span>
          </div>

          <p class="modal-description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

          <?php if (!empty($product['colors'])): ?>
            <div class="option-group">
              <div class="option-group-title">Colores de filamento disponibles:</div>
              <div class="color-options-list">
                <?php foreach ($product['colors'] as $cIdx => $col): ?>
                  <span class="color-option-chip <?php echo $cIdx === 0 ? 'selected' : ''; ?>" style="cursor: default;">
                    <?php echo htmlspecialchars($col); ?>
                  </span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($product['customizable']): ?>
            <div class="customization-input-group" style="background: var(--blush-soft); padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--blush-border);">
              <span style="font-size: 0.85rem; font-weight: 700; color: var(--wine-primary);">✨ Este producto es 100% personalizable</span>
              <span class="customization-hint">Coordinaremos tus fotografías o el nombre grabado por WhatsApp tras enviar tu pedido.</span>
            </div>
          <?php endif; ?>

          <!-- Botón de compra / WhatsApp -->
          <div class="modal-actions-row">
            <button type="button" class="modal-add-btn" onclick="window.RinconApp.quickAddToCart(<?php echo $product['id']; ?>, event)" style="font-size: 1rem; padding: 0.95rem 1.75rem;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
              </svg>
              Agregar al Carrito
            </button>
          </div>

          <!-- Ficha Técnica -->
          <?php if (!empty($product['specs'])): ?>
            <div class="tech-specs-box">
              <div class="tech-specs-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                  <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                  <line x1="6" y1="6" x2="6.01" y2="6"></line>
                  <line x1="6" y1="18" x2="6.01" y2="18"></line>
                </svg>
                Especificaciones de Fabricación 3D
              </div>
              <table class="specs-table">
                <tbody>
                  <?php foreach ($product['specs'] as $k => $v): ?>
                    <tr>
                      <td class="specs-label"><?php echo htmlspecialchars($k); ?></td>
                      <td class="specs-value"><?php echo htmlspecialchars($v); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <!-- Productos recomendados (Internal Linking SEO) -->
    <?php if (!empty($relatedProducts)): ?>
      <div style="margin-top: 3.5rem;">
        <h2 style="font-family: var(--font-serif); font-size: 1.5rem; color: var(--wine-dark); margin-bottom: 1.5rem;">
          Otras creaciones recomendadas en 3D
        </h2>
        <div class="products-grid">
          <?php foreach ($relatedProducts as $rel): 
            $relImg = $rel['images'][0] ?? 'assets/img/logo.jpg';
          ?>
            <article class="product-card">
              <div class="product-image-container" onclick="window.location.href='producto.php?id=<?php echo $rel['id']; ?>'">
                <img src="<?php echo htmlspecialchars($relImg); ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>" class="product-thumb-img">
                <?php if (!empty($rel['badge'])): ?>
                  <span class="product-badge-tag"><?php echo htmlspecialchars($rel['badge']); ?></span>
                <?php endif; ?>
              </div>
              <div class="product-card-body">
                <span class="product-category-label"><?php echo htmlspecialchars($rel['categoryName']); ?></span>
                <h3 class="product-card-title">
                  <a href="producto.php?id=<?php echo $rel['id']; ?>"><?php echo htmlspecialchars($rel['title']); ?></a>
                </h3>
                <div class="product-card-footer">
                  <div class="product-price-box">
                    <span class="product-price-label">Precio</span>
                    <span class="product-price-val"><?php echo CURRENCY_SYMBOL . number_format($rel['price'], 0, ',', '.'); ?></span>
                  </div>
                  <button type="button" class="add-cart-btn" onclick="window.RinconApp.quickAddToCart(<?php echo $rel['id']; ?>, event)">
                    Agregar
                  </button>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <!-- Incluir cajón del carrito, modales y scripts -->
  <div id="cart-drawer-backdrop" class="cart-drawer-backdrop"></div>
  <aside id="cart-drawer" class="cart-drawer">
    <div class="cart-header">
      <div class="cart-header-title">Tu Pedido</div>
      <button type="button" id="cart-close-btn" class="cart-close-btn">✕</button>
    </div>
    <div class="cart-body">
      <div id="cart-empty-view" class="cart-empty-view" style="display: none;">
        <h4>Tu carrito está vacío</h4>
      </div>
      <div id="cart-filled-view" style="display: flex; flex-direction: column; height: 100%;">
        <div id="cart-items-list" class="cart-items-list"></div>
        <div class="cart-footer" style="margin-top: auto;">
          <div class="cart-summary-line">
            <span>Subtotal:</span>
            <span id="cart-subtotal-val">$0</span>
          </div>
          <div class="cart-summary-total">
            <span>Total:</span>
            <span id="cart-total-val">$0</span>
          </div>
          <button type="button" id="to-checkout-btn" class="checkout-step-btn">Continuar con el Pedido</button>
        </div>
      </div>
      <div id="checkout-view" class="checkout-view">
        <button type="button" id="back-to-cart-btn" class="checkout-back-btn">← Volver</button>
        <form id="checkout-form">
          <div class="form-field-group">
            <label class="field-label">Nombre *</label>
            <input type="text" id="client-name" class="field-input" required>
          </div>
          <div class="form-field-group">
            <label class="field-label">WhatsApp *</label>
            <input type="tel" id="client-phone" class="field-input" required>
          </div>
          <div class="form-field-group">
            <label class="field-label">Entrega *</label>
            <select id="delivery-method" class="field-select">
              <option value="envio">Envío a Domicilio</option>
              <option value="retiro">Retiro en Taller</option>
            </select>
          </div>
          <div id="address-group" class="form-field-group">
            <label class="field-label">Dirección *</label>
            <input type="text" id="client-address" class="field-input">
          </div>
          <div class="form-field-group">
            <label class="field-label">Pago *</label>
            <div class="payment-method-cards">
              <div id="pay-transfer" class="payment-method-card selected">
                <div class="payment-card-title">Transferencia (10% OFF)</div>
              </div>
              <div id="pay-cash" class="payment-method-card">
                <div class="payment-card-title">Efectivo</div>
              </div>
            </div>
          </div>
          <button type="submit" class="whatsapp-submit-btn">📲 Enviar a WhatsApp</button>
        </form>
      </div>
    </div>
  </aside>

  <div id="toast-container" class="toast-container"></div>
  <script src="assets/js/app.js"></script>
</body>
</html>
