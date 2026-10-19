<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once __DIR__ . '/config.php';
$products = getProducts();
$categories = getCategories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo STORE_NAME; ?> | Tienda Online de Impresión 3D</title>
  <meta name="description" content="Descubre creaciones únicas en impresión 3D: lámparas litofanía, mates facetados, macetas geométricas, figuras coleccionables y regalos personalizados en Rincón 3D.">
  <link rel="canonical" href="https://rincon3dtuc.com/">
  
  <!-- Favicon con el logo de Rincón 3D -->
  <link rel="icon" type="image/jpeg" href="assets/img/logo.jpg">

  <!-- Open Graph para WhatsApp, Instagram y Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?php echo htmlspecialchars(STORE_NAME); ?>">
  <meta property="og:title" content="<?php echo htmlspecialchars(STORE_NAME); ?> | Tienda Online de Impresión 3D">
  <meta property="og:description" content="Lámparas personalizadas en litofanía, mates geométricos térmicos, macetas origami y figuras coleccionables. Envíos a todo el país y 10% OFF en transferencia.">
  <meta property="og:image" content="assets/img/logo.jpg">
  
  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars(STORE_NAME); ?> | Tienda Online de Impresión 3D">
  <meta name="twitter:description" content="Lámparas personalizadas, mates geométricos y figuras en impresión 3D de alta precisión.">
  <meta name="twitter:image" content="assets/img/logo.jpg">

  <!-- Schema.org JSON-LD (Rich Snippets para Google) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Store",
    "name": "<?php echo addslashes(STORE_NAME); ?>",
    "image": "assets/img/logo.jpg",
    "description": "Taller creativo de impresión 3D y diseño personalizado.",
    "telephone": "+<?php echo WHATSAPP_PHONE; ?>",
    "currenciesAccepted": "ARS",
    "paymentAccepted": "Cash, Bank Transfer",
    "priceRange": "$$"
  }
  </script>
  
  <!-- Tipografías de Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..700;1,400..700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Estilos principales -->
  <link rel="stylesheet" href="assets/css/style.css?v=<?php echo file_exists(__DIR__ . '/assets/css/style.css') ? filemtime(__DIR__ . '/assets/css/style.css') : '1.1'; ?>">
  
  <!-- Configuración exportada para JavaScript -->
  <script>
    window.APP_CONFIG = {
      whatsappPhone: "<?php echo WHATSAPP_PHONE; ?>",
      currencySymbol: "<?php echo APP_CURRENCY; ?>",
      shippingCost: <?php echo SHIPPING_COST_DEFAULT; ?>,
      freeShippingThreshold: <?php echo FREE_SHIPPING_THRESHOLD; ?>,
      bankDiscountPercent: <?php echo BANK_DISCOUNT_PERCENT; ?>
    };
    window.INITIAL_PRODUCTS = <?php echo json_encode($products); ?>;
  </script>
</head>
<body>

  <!-- Barra superior de avisos -->
  <aside class="top-notice-bar" aria-label="Aviso promocional">
    <span>✨ Envíos a todo el país</span>
    <span class="sparkle-icon">✦</span>
    <span>10% OFF abonando por Transferencia</span>
    <span class="sparkle-icon">✦</span>
    <span>Pedidos 100% personalizados</span>
  </aside>

  <!-- Encabezado y Navbar Principal -->
  <header class="site-header">
    <div class="container">
      <div class="nav-wrapper">
        <!-- Marca / Logo -->
        <a href="index.php" class="brand-link" aria-label="Inicio de Rincón 3D">
          <img src="assets/img/logo.jpg" alt="Logo Rincón 3D" class="brand-logo-img">
          <div class="brand-text">
            <span class="brand-title"><?php echo STORE_NAME; ?></span>
            <span class="brand-subtitle"><?php echo STORE_TAGLINE; ?></span>
          </div>
        </a>

        <!-- Buscador interactivo en vivo -->
        <div class="header-search">
          <div class="search-input-wrapper">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="search-input" class="search-input" placeholder="Buscar mates, lámparas, macetas..." autocomplete="off">
            <button type="button" id="search-clear-btn" class="search-clear-btn" aria-label="Limpiar búsqueda">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>

        <!-- Acciones del header: carrito -->
        <div class="header-actions">
          <button type="button" id="cart-trigger-btn" class="cart-trigger-btn" aria-label="Abrir carrito de compras">
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

  <!-- Banner Hero de Marca (Diseño refinado, con foto temática o personalizada) -->
  <section id="hero-banner" class="hero-brand-banner" style="<?php if (defined('BANNER_IMAGE') && BANNER_IMAGE && BANNER_IMAGE !== 'assets/img/logo.jpg'): ?>background-image: linear-gradient(135deg, rgba(253, 248, 248, 0.93) 0%, rgba(255, 255, 255, 0.88) 50%, rgba(248, 238, 240, 0.94) 100%), url('<?php echo htmlspecialchars(BANNER_IMAGE); ?>'); background-size: cover; background-position: center;<?php endif; ?>">
    <div class="container">
      <div class="hero-inner">
        <div class="hero-content">
          <div class="hero-pill-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
            Taller Creativo de Impresión 3D
          </div>
          <h1 class="hero-title">
            Arte, diseño y tecnología en <span>cada rincón</span>
          </h1>
          <p class="hero-description">
            Fabricamos piezas únicas con filamentos biodegradables de primera calidad. Desde recuerdos personalizados en litofanía y mates térmicos hasta iluminación ambiental y decoración para tu espacio.
          </p>
          <div class="hero-features-list">
            <div class="hero-feature-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
              Filamentos Eco PLA
            </div>
            <div class="hero-feature-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 6v6l4 2"></path>
              </svg>
              Producción 24-48 hs
            </div>
            <div class="hero-feature-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
              </svg>
              Detalles Personalizados
            </div>
          </div>
        </div>

        <div class="hero-logo-card">
          <img src="<?php echo htmlspecialchars(defined('BANNER_IMAGE') && BANNER_IMAGE ? BANNER_IMAGE : 'assets/img/admin_bg_3dprinter.jpg'); ?>" alt="<?php echo htmlspecialchars(STORE_NAME); ?>" class="hero-logo-preview" onerror="this.src='assets/img/logo.jpg'">
          <p>Pasión por los detalles</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Barra de Filtros por Categoría y Ordenamiento -->
  <section class="catalog-toolbar-section">
    <div class="container">
      <div class="catalog-toolbar">
        <div class="category-pills" role="tablist">
          <button type="button" class="cat-pill active" data-category="all" role="tab">Todos</button>
          <?php foreach ($categories as $cat): ?>
          <button type="button" class="cat-pill" data-category="<?php echo htmlspecialchars($cat['slug']); ?>" role="tab">
            <?php echo !empty($cat['icono']) ? htmlspecialchars($cat['icono']) . ' ' : ''; ?><?php echo htmlspecialchars($cat['nombre']); ?>
          </button>
          <?php endforeach; ?>
        </div>

        <div class="sort-select-wrapper">
          <label for="sort-select" class="sort-label">Ordenar:</label>
          <select id="sort-select" class="sort-select">
            <option value="featured">Destacados</option>
            <option value="price-asc">Menor precio</option>
            <option value="price-desc">Mayor precio</option>
            <option value="title-asc">Nombre A-Z</option>
          </select>
        </div>
      </div>
    </div>
  </section>

  <!-- Vista Principal: Catálogo de Productos y Precios -->
  <main class="catalog-section">
    <div class="container">
      <div class="catalog-meta-info">
        <span id="products-count" class="products-count-text"><?php echo count($products); ?> <?php echo count($products) === 1 ? 'producto' : 'productos'; ?></span>
      </div>

      <!-- Grilla interactiva de productos -->
      <div id="products-grid" class="products-grid">
        <?php foreach ($products as $p): 
          $mainImage = !empty($p['images'][0]) ? $p['images'][0] : 'assets/img/logo.jpg';
        ?>
          <article class="product-card" data-id="<?php echo $p['id']; ?>">
            <div class="product-image-container" onclick="window.RinconApp && window.RinconApp.openModal(<?php echo $p['id']; ?>)">
              <img src="<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" class="product-thumb-img" loading="lazy" onerror="this.src='assets/img/logo.jpg'" />
              <?php if (!empty($p['badge'])): ?>
                <span class="product-badge-tag"><?php echo htmlspecialchars($p['badge']); ?></span>
              <?php endif; ?>
              <?php if (!empty($p['customizable'])): ?>
                <span class="product-custom-tag">✨ Personalizable</span>
              <?php endif; ?>
              <div class="product-quick-view-overlay">
                <span class="quick-view-btn">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                  Ver Detalle & Fotos
                </span>
              </div>
            </div>
            
            <div class="product-card-body">
              <span class="product-category-label"><?php echo htmlspecialchars($p['categoryName'] ?? 'Impresión 3D'); ?></span>
              <h3 class="product-card-title" onclick="window.RinconApp && window.RinconApp.openModal(<?php echo $p['id']; ?>)"><?php echo htmlspecialchars($p['title']); ?></h3>
              <p class="product-short-desc"><?php echo htmlspecialchars($p['description'] ?? ''); ?></p>
              
              <?php if (!empty($p['colors'])): ?>
                <div class="product-color-dots">
                  <?php foreach (array_slice($p['colors'], 0, 4) as $col): 
                    $cl = strtolower($col);
                    $bg = '#651226';
                    if (strpos($cl, 'rosa') !== false) $bg = '#E8A598';
                    elseif (strpos($cl, 'blanco') !== false || strpos($cl, 'marfil') !== false) $bg = '#F4EDE4';
                    elseif (strpos($cl, 'negro') !== false || strpos($cl, 'obsidiana') !== false) $bg = '#222222';
                    elseif (strpos($cl, 'oro') !== false || strpos($cl, 'cobre') !== false) $bg = '#C5983F';
                    elseif (strpos($cl, 'verde') !== false || strpos($cl, 'salvia') !== false) $bg = '#8A9A86';
                    elseif (strpos($cl, 'gris') !== false) $bg = '#8E8E93';
                  ?>
                    <span class="color-dot" style="background-color: <?php echo $bg; ?>;" title="<?php echo htmlspecialchars($col); ?>"></span>
                  <?php endforeach; ?>
                  <?php if (count($p['colors']) > 4): ?>
                    <span class="color-dots-more">+<?php echo count($p['colors']) - 4; ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <div class="product-card-footer">
                <div class="product-price-box">
                  <span class="product-price-label">Precio</span>
                  <span class="product-price-val"><?php echo APP_CURRENCY . number_format($p['price'], 0, ',', '.'); ?></span>
                </div>
                <button type="button" class="add-cart-btn" onclick="window.RinconApp && window.RinconApp.quickAddToCart(<?php echo $p['id']; ?>, event)" aria-label="Agregar <?php echo htmlspecialchars($p['title']); ?> al carrito">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                  </svg>
                  Agregar
                </button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </main>

  <!-- =========================================================================
       MODAL DE DETALLE DE PRODUCTO CON PASARELA / GALERÍA DE FOTOS
       ========================================================================= -->
  <div id="product-modal" class="modal-backdrop" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-dialog">
      <button type="button" id="modal-close-btn" class="modal-close-btn" aria-label="Cerrar ventana">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>

      <div class="modal-content-grid">
        <!-- Columna de Pasarela de Fotos -->
        <div class="modal-gallery-column">
          <div class="gallery-main-view">
            <img id="modal-gallery-main" class="gallery-main-image" src="" alt="Foto principal de producto">
          </div>
          <div id="modal-thumbnails-strip" class="gallery-thumbnails-strip">
            <!-- Miniaturas insertadas dinámicamente -->
          </div>
        </div>

        <!-- Columna de Información y Opciones de Compra -->
        <div class="modal-info-column">
          <span id="modal-category-badge" class="modal-category-badge">Categoría</span>
          <h2 id="modal-title" class="modal-title">Título del Producto</h2>
          
          <div class="modal-price-box">
            <span id="modal-price" class="modal-price-current">$0</span>
            <span class="modal-discount-pill">10% OFF en Transferencia</span>
          </div>

          <p id="modal-description" class="modal-description">Descripción detallada del producto...</p>

          <!-- Selector de color de filamento -->
          <div class="option-group">
            <div class="option-group-title">Color de filamento disponible:</div>
            <div id="modal-colors-list" class="color-options-list">
              <!-- Chips de colores insertados dinámicamente -->
            </div>
          </div>

          <!-- Campo de personalización (visible si es customizable) -->
          <div id="modal-custom-input-group" class="customization-input-group" style="display: none;">
            <label for="modal-custom-input" class="option-group-title">Personalización del diseño:</label>
            <input type="text" id="modal-custom-input" class="customization-input" placeholder="Ej: Nombre a grabar o foto a coordinar...">
            <span class="customization-hint">Coordinaremos fotos o detalles finales al enviar el pedido por WhatsApp.</span>
          </div>

          <!-- Cantidad y Botón de Agregar -->
          <div class="modal-actions-row">
            <div class="quantity-stepper">
              <button type="button" id="modal-qty-minus" class="qty-btn" aria-label="Disminuir cantidad">−</button>
              <span id="modal-qty-val" class="qty-number">1</span>
              <button type="button" id="modal-qty-plus" class="qty-btn" aria-label="Aumentar cantidad">+</button>
            </div>
            <button type="button" id="modal-add-cart-btn" class="modal-add-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
              </svg>
              Agregar al Carrito
            </button>
          </div>

          <!-- Ficha Técnica de Impresión 3D -->
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
              <tbody id="modal-specs-table">
                <!-- Filas de especificaciones -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- =========================================================================
       DRAWER LATERAL DE CARRITO Y CHECKOUT A WHATSAPP
       ========================================================================= -->
  <div id="cart-drawer-backdrop" class="cart-drawer-backdrop"></div>
  <aside id="cart-drawer" class="cart-drawer" aria-label="Carrito de compras">
    <div class="cart-header">
      <div class="cart-header-title">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <path d="M16 10a4 4 0 0 1-8 0"></path>
        </svg>
        Tu Pedido
      </div>
      <button type="button" id="cart-close-btn" class="cart-close-btn" aria-label="Cerrar carrito">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Cuerpo del Carrito -->
    <div class="cart-body">
      <!-- Estado vacío -->
      <div id="cart-empty-view" class="cart-empty-view" style="display: none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <h4>Tu carrito está vacío</h4>
        <p>Explora nuestros productos de impresión 3D y agrégalos a tu carrito con un solo clic.</p>
        <button type="button" class="add-cart-btn" onclick="window.RinconApp.toggleCart(false)" style="margin: 0 auto;">Explorar Tienda</button>
      </div>

      <!-- Vista 1: Lista de productos agregados -->
      <div id="cart-filled-view" style="display: flex; flex-direction: column; height: 100%;">
        <div id="cart-items-list" class="cart-items-list">
          <!-- Items generados dinámicamente -->
        </div>

        <div class="cart-footer" style="margin-top: auto;">
          <div class="cart-summary-line">
            <span>Subtotal:</span>
            <span id="cart-subtotal-val" style="font-weight: 700;">$0</span>
          </div>
          <div class="cart-summary-line">
            <span>Envío estimado:</span>
            <span id="cart-shipping-val">Calculando...</span>
          </div>
          <div id="cart-discount-row" class="cart-summary-line" style="color: var(--success-color); font-weight: 600; display: none;">
            <span>Descuento Transferencia (10%):</span>
            <span id="cart-discount-val">-$0</span>
          </div>
          <div class="cart-summary-total">
            <span>Total:</span>
            <span id="cart-total-val">$0</span>
          </div>
          <button type="button" id="to-checkout-btn" class="checkout-step-btn">
            Continuar con el Pedido
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </button>
        </div>
      </div>

      <!-- Vista 2: Formulario de Checkout y Envío a WhatsApp -->
      <div id="checkout-view" class="checkout-view">
        <button type="button" id="back-to-cart-btn" class="checkout-back-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
          </svg>
          Volver a editar productos
        </button>

        <form id="checkout-form">
          <div class="form-field-group">
            <label for="client-name" class="field-label">Nombre y Apellido *</label>
            <input type="text" id="client-name" class="field-input" placeholder="Ej: María Gómez" required>
          </div>

          <div class="form-field-group">
            <label for="client-phone" class="field-label">Teléfono WhatsApp de contacto *</label>
            <input type="tel" id="client-phone" class="field-input" placeholder="Ej: 11 2345-6789" required>
          </div>

          <div class="form-field-group">
            <label for="delivery-method" class="field-label">Modalidad de Entrega *</label>
            <select id="delivery-method" class="field-select">
              <option value="envio">Envío a Domicilio (Correo / Mensajería)</option>
              <option value="retiro">Retiro gratuito en el taller</option>
            </select>
          </div>

          <div id="address-group" class="form-field-group">
            <label for="client-address" class="field-label">Domicilio completo de entrega *</label>
            <input type="text" id="client-address" class="field-input" placeholder="Calle, número, piso/depto, localidad">
          </div>

          <div class="form-field-group">
            <label class="field-label">Forma de Pago *</label>
            <div class="payment-method-cards">
              <div id="pay-transfer" class="payment-method-card selected">
                <div class="payment-card-title">Transferencia</div>
                <span class="payment-card-badge">10% OFF</span>
              </div>
              <div id="pay-cash" class="payment-method-card">
                <div class="payment-card-title">Efectivo</div>
                <span style="font-size: 0.7rem; color: var(--text-muted);">Al retirar / recibir</span>
              </div>
            </div>
          </div>

          <div class="form-field-group">
            <label for="client-notes" class="field-label">Aclaraciones adicionales (Opcional)</label>
            <textarea id="client-notes" class="field-textarea" rows="2" placeholder="Colores preferidos, horarios de entrega o fecha límite..."></textarea>
          </div>

          <button type="submit" class="whatsapp-submit-btn">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.174.086.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824z"/>
            </svg>
            Enviar Pedido por WhatsApp
          </button>
        </form>
      </div>
    </div>
  </aside>

  <!-- Botón Flotante de WhatsApp para consultas directas -->
  <a href="https://wa.me/<?php echo WHATSAPP_PHONE; ?>?text=<?php echo urlencode('¡Hola! Me gustaría hacer una consulta sobre impresiones 3D personalizadas.'); ?>" 
     class="floating-whatsapp-btn" 
     target="_blank" 
     rel="noopener noreferrer" 
     aria-label="Contactar por WhatsApp">
    <svg viewBox="0 0 24 24" fill="currentColor">
      <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.174.086.275.072.376-.043.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824z"/>
    </svg>
  </a>

  <!-- Contenedor de notificaciones Toast -->
  <div id="toast-container" class="toast-container" aria-live="polite"></div>

  <!-- Pie de Página -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <h3><?php echo STORE_NAME; ?></h3>
          <p>
            Impresiones 3D y diseño artesanal con enfoque en calidad, calidez y atención personalizada. Damos forma a tus ideas y proyectos más queridos.
          </p>
        </div>
        <div class="footer-col">
          <h4>Categorías</h4>
          <ul>
            <li><a href="javascript:void(0)" onclick="document.querySelector('.cat-pill[data-category=\'iluminacion\']').click()">Lámparas & Veladores</a></li>
            <li><a href="javascript:void(0)" onclick="document.querySelector('.cat-pill[data-category=\'mates\']').click()">Mates & Bazar 3D</a></li>
            <li><a href="javascript:void(0)" onclick="document.querySelector('.cat-pill[data-category=\'decoracion\']').click()">Macetas & Hogar</a></li>
            <li><a href="javascript:void(0)" onclick="document.querySelector('.cat-pill[data-category=\'organizacion\']').click()">Organización de Escritorio</a></li>
            <li><a href="javascript:void(0)" onclick="document.querySelector('.cat-pill[data-category=\'figuras\']').click()">Figuras Coleccionables</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Atención al Cliente</h4>
          <ul>
            <li>📍 Taller de producción 3D</li>
            <li>🚚 Envíos a todo el país</li>
            <li>💳 Transferencia con 10% OFF o Efectivo</li>
            <li>📲 WhatsApp: +<?php echo WHATSAPP_PHONE; ?></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> <?php echo STORE_NAME; ?>. Todos los derechos reservados. Diseñado con amor para impresión 3D.</p>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="assets/js/app.js?v=<?php echo file_exists(__DIR__ . '/assets/js/app.js') ? filemtime(__DIR__ . '/assets/js/app.js') : '1.1'; ?>"></script>
</body>
</html>
