<?php
require_once __DIR__ . '/auth.php';
requireAuth();

$db = getDB();
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditing = $productId > 0;

$message = '';
$messageType = 'success';

// Cargar categorías disponibles (desde MySQL o fallback por defecto)
$categories = getCategories(false);

// Datos iniciales por defecto
$product = [
    'titulo' => '',
    'categoria_id' => $categories[0]['id'] ?? 1,
    'precio' => '',
    'badge' => '',
    'customizable' => 0,
    'tiempo_produccion' => '24 a 48 hs',
    'descripcion' => '',
    'activo' => 1,
    'destacado' => 0,
    'imagenes' => [],
    'colores_texto' => '',
    'specs' => [
        'Material' => 'PLA Premium Biodegradable',
        'Dimensiones' => '',
        'Tiempo de fabricación' => '24 a 48 hs'
    ]
];

// Si estamos editando, cargar datos (primero desde MySQL, o desde JSON si MySQL no está disponible)
if ($isEditing) {
    $loaded = false;

    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM productos WHERE id = ?");
            $stmt->execute([$productId]);
            $existing = $stmt->fetch();
            if ($existing) {
                $product['titulo'] = $existing['titulo'];
                $product['categoria_id'] = $existing['categoria_id'];
                $product['precio'] = $existing['precio'];
                $product['badge'] = $existing['badge'];
                $product['customizable'] = $existing['customizable'];
                $product['tiempo_produccion'] = $existing['tiempo_produccion'];
                $product['descripcion'] = $existing['descripcion'];
                $product['activo'] = $existing['activo'];
                $product['destacado'] = $existing['destacado'];

                // Imágenes
                $stmtImgs = $db->prepare("SELECT id, url_imagen, es_principal FROM producto_imagenes WHERE producto_id = ? ORDER BY orden ASC");
                $stmtImgs->execute([$productId]);
                $product['imagenes'] = $stmtImgs->fetchAll();

                // Colores
                $stmtCols = $db->prepare("SELECT nombre_color FROM producto_colores WHERE producto_id = ?");
                $stmtCols->execute([$productId]);
                $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
                $product['colores_texto'] = implode(', ', $cols);

                // Specs
                $stmtSpecs = $db->prepare("SELECT clave, valor FROM producto_especificaciones WHERE producto_id = ?");
                $stmtSpecs->execute([$productId]);
                $product['specs'] = $stmtSpecs->fetchAll(PDO::FETCH_KEY_PAIR);
                $loaded = true;
            }
        } catch (Throwable $e) {
            error_log("Error cargando producto desde MySQL: " . $e->getMessage());
        }
    }

    // Fallback: cargar desde data/products.json si no se cargó de MySQL
    if (!$loaded) {
        $jsonProducts = getAllProductsFromJson();
        foreach ($jsonProducts as $jp) {
            if ((int)$jp['id'] === $productId) {
                $product['titulo'] = $jp['title'] ?? '';
                $product['precio'] = $jp['price'] ?? '';
                $product['badge'] = $jp['badge'] ?? '';
                $product['customizable'] = !empty($jp['customizable']) ? 1 : 0;
                $product['tiempo_produccion'] = $jp['specs']['Tiempo de fabricación'] ?? '24 a 48 hs';
                $product['descripcion'] = $jp['description'] ?? '';
                $product['activo'] = isset($jp['active']) ? ($jp['active'] ? 1 : 0) : 1;
                $product['colores_texto'] = implode(', ', $jp['colors'] ?? []);
                $product['specs'] = $jp['specs'] ?? [];

                // Mapear categoría por slug
                foreach ($categories as $cat) {
                    if ($cat['slug'] === ($jp['category'] ?? '')) {
                        $product['categoria_id'] = $cat['id'];
                        break;
                    }
                }

                // Mapear imágenes
                $product['imagenes'] = [];
                foreach (($jp['images'] ?? []) as $idx => $imgUrl) {
                    $product['imagenes'][] = [
                        'id' => $idx + 1,
                        'url_imagen' => $imgUrl,
                        'es_principal' => $idx === 0 ? 1 : 0
                    ];
                }
                $loaded = true;
                break;
            }
        }
    }
}

// PROCESAR GUARDADO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $categoriaId = (int)($_POST['categoria_id'] ?? 1);
    $precio = (float)($_POST['precio'] ?? 0);
    $badge = trim($_POST['badge'] ?? '');
    $customizable = isset($_POST['customizable']) ? 1 : 0;
    $tiempoProd = trim($_POST['tiempo_produccion'] ?? '24 a 48 hs');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;
    $coloresInput = trim($_POST['colores'] ?? '');

    // Slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titulo)));

    if (empty($titulo) || $precio <= 0) {
        $message = "Por favor ingresa un título y un precio válido.";
        $messageType = "error";
    } else {
        // Encontrar datos de categoría
        $catSlug = 'general';
        $catNombre = 'General';
        foreach ($categories as $c) {
            if ((int)$c['id'] === $categoriaId) {
                $catSlug = $c['slug'];
                $catNombre = $c['nombre'];
                break;
            }
        }

        // Colores procesados
        $colArray = [];
        if (!empty($coloresInput)) {
            $colArray = array_values(array_filter(array_map('trim', explode(',', $coloresInput))));
        }

        // Specs procesadas
        $specsProcessed = [];
        $specKeys = $_POST['spec_key'] ?? [];
        $specVals = $_POST['spec_val'] ?? [];
        for ($i = 0; $i < count($specKeys); $i++) {
            $k = trim($specKeys[$i] ?? '');
            $v = trim($specVals[$i] ?? '');
            if (!empty($k) && !empty($v)) {
                $specsProcessed[$k] = $v;
            }
        }

        // Procesar subida de foto por archivo
        $uploadedImageUrls = [];
        if (!empty($_FILES['foto_archivo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['foto_archivo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed)) {
                $newFilename = 'prod_' . ($productId ?: time()) . '_' . time() . '.' . $ext;
                $targetPath = __DIR__ . '/../assets/img/' . $newFilename;
                if (move_uploaded_file($_FILES['foto_archivo']['tmp_name'], $targetPath)) {
                    $uploadedImageUrls[] = 'assets/img/' . $newFilename;
                }
            }
        }

        // Manejo de foto agregada por URL externa
        $nuevaFotoUrl = trim($_POST['nueva_foto_url'] ?? '');
        if (!empty($nuevaFotoUrl)) {
            $uploadedImageUrls[] = $nuevaFotoUrl;
        }

        $delImgId = !empty($_POST['eliminar_imagen_id']) ? (int)$_POST['eliminar_imagen_id'] : 0;

        // 1. INTENTAR GUARDAR EN MYSQL (Si está disponible)
        $savedInDb = false;
        if ($db) {
            try {
                $db->beginTransaction();

                if ($isEditing) {
                    $stmt = $db->prepare("UPDATE productos SET 
                        categoria_id = ?, titulo = ?, slug = ?, descripcion = ?, precio = ?, badge = ?, customizable = ?, tiempo_produccion = ?, activo = ? 
                        WHERE id = ?");
                    $stmt->execute([
                        $categoriaId, $titulo, $slug, $descripcion, $precio, $badge, $customizable, $tiempoProd, $activo, $productId
                    ]);
                } else {
                    $stmt = $db->prepare("INSERT INTO productos 
                        (categoria_id, titulo, slug, descripcion, precio, badge, customizable, tiempo_produccion, activo, orden) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 99)");
                    $stmt->execute([
                        $categoriaId, $titulo, $slug, $descripcion, $precio, $badge, $customizable, $tiempoProd, $activo
                    ]);
                    $productId = (int)$db->lastInsertId();
                    $isEditing = true;
                }

                // Insertar nuevas fotos en MySQL
                foreach ($uploadedImageUrls as $uImg) {
                    $stmtImg = $db->prepare("INSERT INTO producto_imagenes (producto_id, url_imagen, orden, es_principal) VALUES (?, ?, 10, 0)");
                    $stmtImg->execute([$productId, $uImg]);
                }

                // Eliminar foto en MySQL si se solicitó
                if ($delImgId > 0) {
                    $stmtDel = $db->prepare("DELETE FROM producto_imagenes WHERE id = ? AND producto_id = ?");
                    $stmtDel->execute([$delImgId, $productId]);
                }

                // Actualizar colores en MySQL
                $db->prepare("DELETE FROM producto_colores WHERE producto_id = ?")->execute([$productId]);
                $stmtCol = $db->prepare("INSERT INTO producto_colores (producto_id, nombre_color) VALUES (?, ?)");
                foreach ($colArray as $col) {
                    $stmtCol->execute([$productId, $col]);
                }

                // Actualizar especificaciones en MySQL
                $db->prepare("DELETE FROM producto_especificaciones WHERE producto_id = ?")->execute([$productId]);
                $stmtSpec = $db->prepare("INSERT INTO producto_especificaciones (producto_id, clave, valor) VALUES (?, ?, ?)");
                foreach ($specsProcessed as $k => $v) {
                    $stmtSpec->execute([$productId, $k, $v]);
                }

                $db->commit();
                $savedInDb = true;
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("Error guardando en MySQL: " . $e->getMessage());
            }
        }

        // 2. SINCRONIZAR SIEMPRE A data/products.json (Garantiza guardado aunque la BD no esté conectada)
        $currentImgs = [];
        if ($isEditing) {
            $existingProds = getAllProductsFromJson();
            foreach ($existingProds as $ep) {
                if ((int)$ep['id'] === $productId) {
                    $currentImgs = $ep['images'] ?? [];
                    break;
                }
            }
        }
        if (empty($currentImgs) && !empty($product['imagenes'])) {
            foreach ($product['imagenes'] as $pim) {
                $currentImgs[] = $pim['url_imagen'];
            }
        }

        // Eliminar foto si se solicitó
        if ($delImgId > 0 && !empty($currentImgs)) {
            $delIndex = $delImgId - 1;
            if (isset($currentImgs[$delIndex])) {
                unset($currentImgs[$delIndex]);
                $currentImgs = array_values($currentImgs);
            }
        }

        // Agregar fotos nuevas
        foreach ($uploadedImageUrls as $uImg) {
            $currentImgs[] = $uImg;
        }
        if (empty($currentImgs)) {
            $currentImgs = ['assets/img/logo.jpg'];
        }

        // Si no había productId asignado aún
        if ($productId <= 0) {
            $allP = getAllProductsFromJson();
            $maxId = 0;
            foreach ($allP as $ap) {
                if ((int)$ap['id'] > $maxId) $maxId = (int)$ap['id'];
            }
            $productId = $maxId + 1;
        }

        $jsonData = [
            'id' => $productId,
            'title' => $titulo,
            'category' => $catSlug,
            'categoryName' => $catNombre,
            'price' => $precio,
            'badge' => $badge,
            'customizable' => (bool)$customizable,
            'description' => $descripcion,
            'images' => $currentImgs,
            'colors' => $colArray,
            'specs' => $specsProcessed,
            'active' => (bool)$activo
        ];

        syncProductToJson($jsonData);

        $storageParam = $savedInDb ? 'mysql' : 'json';
        header("Location: producto.php?id=" . $productId . "&saved=1&storage=" . $storageParam);
        exit;
    }
}

if (isset($_GET['saved'])) {
    if (isset($_GET['storage']) && $_GET['storage'] === 'json') {
        $message = "✨ ¡Producto guardado exitosamente en el catálogo! (Sincronizado con data/products.json).";
    } else {
        $message = "✨ ¡Producto guardado exitosamente en la base de datos MySQL y catálogo!";
    }
    $messageType = "success";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $isEditing ? 'Editar Producto' : 'Nuevo Producto'; ?> | Rincón 3D Admin</title>
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

  <main class="admin-main">
    <div class="page-header">
      <div>
        <a href="index.php" style="color: var(--admin-wine); font-weight: 600; font-size: 0.85rem;">← Volver al listado</a>
        <h1 class="page-title" style="margin-top: 0.4rem;">
          <?php echo $isEditing ? 'Editar: ' . htmlspecialchars($product['titulo']) : 'Nuevo Producto de Impresión 3D'; ?>
        </h1>
      </div>
    </div>

    <?php if (!$db): ?>
      <div class="alert-box" style="background: #FFFBEB; border-left: 4px solid #F59E0B; color: #92400E; margin-bottom: 1.25rem; font-size: 0.88rem; padding: 0.85rem 1.25rem; border-radius: var(--radius-sm);">
        <span>💡 <strong>Modo Catálogo Local:</strong> La base de datos MySQL no está conectada en este momento. Los cambios que realices se guardarán y verán de inmediato en la tienda a través del catálogo local (<code>data/products.json</code>).</span>
      </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
      <div class="alert-box alert-<?php echo $messageType; ?>">
        <span><?php echo htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-card" style="max-width: 900px;">
      <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
      
      <div class="form-grid">
        <div class="form-group full-width">
          <label class="form-label">Título del Producto *</label>
          <input type="text" name="titulo" class="form-control" required value="<?php echo htmlspecialchars($product['titulo']); ?>" placeholder="Ej: Lámpara Litofanía Lunar Personalizada">
        </div>

        <div class="form-group">
          <label class="form-label">Categoría *</label>
          <select name="categoria_id" class="form-control">
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['id']; ?>" <?php echo $product['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['nombre']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Precio ($) *</label>
          <input type="number" step="0.01" name="precio" class="form-control" required value="<?php echo htmlspecialchars($product['precio']); ?>" placeholder="18500">
        </div>

        <div class="form-group">
          <label class="form-label">Etiqueta Promocional (Badge)</label>
          <input type="text" name="badge" class="form-control" value="<?php echo htmlspecialchars($product['badge'] ?? ''); ?>" placeholder="Más Vendido, Exclusivo, Tendencia...">
        </div>

        <div class="form-group">
          <label class="form-label">Tiempo Estimado de Producción 3D</label>
          <input type="text" name="tiempo_produccion" class="form-control" value="<?php echo htmlspecialchars($product['tiempo_produccion']); ?>" placeholder="24 a 48 hs">
        </div>

        <div class="form-group full-width">
          <label class="form-label">Descripción Detallada</label>
          <textarea name="descripcion" class="form-control" rows="4" placeholder="Describe los detalles, materiales y el valor artesanal del producto..."><?php echo htmlspecialchars($product['descripcion']); ?></textarea>
        </div>

        <div class="form-group full-width">
          <label class="form-label">Colores de Filamento Disponibles (Separados por coma)</label>
          <input type="text" name="colores" class="form-control" value="<?php echo htmlspecialchars($product['colores_texto']); ?>" placeholder="Borgoña Rincón 3D, Rosa Empolvado, Blanco Mármol, Negro Satinado">
          <span style="font-size: 0.75rem; color: var(--admin-muted);">Cada color aparecerá como una opción seleccionable para el cliente.</span>
        </div>

        <div class="form-group full-width" style="margin-top: 0.5rem;">
          <label class="form-check">
            <input type="checkbox" name="customizable" value="1" <?php echo $product['customizable'] ? 'checked' : ''; ?>>
            <span style="font-weight: 600;">✨ Permite personalización (el cliente podrá ingresar nombres, textos o enviar fotos para litofanía)</span>
          </label>
        </div>

        <div class="form-group full-width">
          <label class="form-check">
            <input type="checkbox" name="activo" value="1" <?php echo $product['activo'] ? 'checked' : ''; ?>>
            <span style="font-weight: 600;">Producto activo y visible en la tienda</span>
          </label>
        </div>
      </div>

      <hr style="border: none; border-top: 1px solid var(--admin-blush-border); margin: 2rem 0;">

      <!-- Sección de Galería de Fotos -->
      <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--admin-wine); margin-bottom: 1rem;">
        📸 Galería / Pasarela de Fotos
      </h3>

      <?php if (!empty($product['imagenes'])): ?>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
          <?php foreach ($product['imagenes'] as $img): 
            $imgSrc = strpos($img['url_imagen'], 'assets/') === 0 ? '../' . $img['url_imagen'] : $img['url_imagen'];
          ?>
            <div style="position: relative; width: 110px; height: 110px; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--admin-blush-border);">
              <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
              <button type="submit" name="eliminar_imagen_id" value="<?php echo $img['id']; ?>" onclick="return confirm('¿Eliminar esta foto de la galería?')" style="position: absolute; top: 4px; right: 4px; background: rgba(220,38,38,0.85); color: #fff; border-radius: 50%; width: 24px; height: 24px; font-size: 12px; font-weight: bold; border: none; cursor: pointer;">
                ✕
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Subir nueva foto desde tu computadora</label>
          <input type="file" name="foto_archivo" accept="image/*" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">O agregar foto por URL web</label>
          <input type="url" name="nueva_foto_url" class="form-control" placeholder="https://...">
        </div>
      </div>

      <hr style="border: none; border-top: 1px solid var(--admin-blush-border); margin: 2rem 0;">

      <!-- Ficha Técnica -->
      <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--admin-wine); margin-bottom: 0.5rem;">
        ⚙️ Ficha Técnica de Impresión 3D
      </h3>
      <p style="font-size: 0.8rem; color: var(--admin-muted); margin-bottom: 1rem;">Características físicas de la pieza que se mostrarán en la ficha del producto.</p>

      <div id="specs-container">
        <?php foreach ($product['specs'] as $k => $v): ?>
          <div style="display: flex; gap: 0.75rem; margin-bottom: 0.75rem;">
            <input type="text" name="spec_key[]" class="form-control" style="width: 35%;" value="<?php echo htmlspecialchars($k); ?>" placeholder="Característica (ej: Material)">
            <input type="text" name="spec_val[]" class="form-control" style="width: 65%;" value="<?php echo htmlspecialchars($v); ?>" placeholder="Detalle (ej: PLA ecológico)">
          </div>
        <?php endforeach; ?>
        <div style="display: flex; gap: 0.75rem; margin-bottom: 0.75rem;">
          <input type="text" name="spec_key[]" class="form-control" style="width: 35%;" placeholder="Nueva característica...">
          <input type="text" name="spec_val[]" class="form-control" style="width: 65%;" placeholder="Detalle...">
        </div>
      </div>

      <div style="margin-top: 2rem; display: flex; gap: 1rem;">
        <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2rem; font-size: 1rem;">
          💾 Guardar Producto
        </button>
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
      </div>

    </form>
  </main>
</body>
</html>
