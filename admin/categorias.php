<?php
require_once __DIR__ . '/auth.php';
requireAuth();

$db = getDB();
$message = '';
$messageType = 'success';

$editingCategory = null;

// Acción rápida: Cambiar estado (Activar / Pausar)
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($db) {
        try {
            $stmt = $db->prepare("UPDATE categorias SET activo = 1 - activo WHERE id = ?");
            $stmt->execute([$id]);
        } catch (Throwable $e) {}
    }
    // Sincronizar en JSON
    $jsonCats = getAllCategoriesFromJson();
    foreach ($jsonCats as $i => $c) {
        if ((int)$c['id'] === $id) {
            $jsonCats[$i]['activo'] = empty($c['activo']) ? 1 : 0;
            break;
        }
    }
    saveCategoriesJson($jsonCats);
    $message = "Estado de la categoría actualizado.";
}

// Acción: Eliminar categoría
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $canDelete = true;

    if ($db) {
        try {
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
            $stmtCheck->execute([$id]);
            $count = (int)$stmtCheck->fetchColumn();
            if ($count > 0) {
                $canDelete = false;
                $message = "No se puede eliminar la categoría porque tiene $count producto(s) asignado(s).";
                $messageType = 'error';
            } else {
                $stmtDel = $db->prepare("DELETE FROM categorias WHERE id = ?");
                $stmtDel->execute([$id]);
            }
        } catch (Throwable $e) {}
    }

    if ($canDelete) {
        deleteCategoryFromJson($id);
        $message = "Categoría eliminada correctamente.";
    }
}

// Cargar categoría para edición si se pasó id
if (isset($_GET['edit_id']) && (int)$_GET['edit_id'] > 0) {
    $editId = (int)$_GET['edit_id'];
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM categorias WHERE id = ?");
            $stmt->execute([$editId]);
            $editingCategory = $stmt->fetch();
        } catch (Throwable $e) {}
    }
    if (!$editingCategory) {
        $jsonCats = getAllCategoriesFromJson();
        foreach ($jsonCats as $c) {
            if ((int)$c['id'] === $editId) {
                $editingCategory = $c;
                break;
            }
        }
    }
}

// Procesar formulario (Crear o Actualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['categoria_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $icono = trim($_POST['icono'] ?? '✦');
    $slug = trim($_POST['slug'] ?? '');
    $orden = (int)($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Si slug está vacío, generar a partir del nombre
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nombre)));
    }

    if (empty($nombre)) {
        $message = "Por favor ingresa un nombre para la categoría.";
        $messageType = "error";
    } else {
        $savedInDb = false;
        if ($db) {
            try {
                if ($id > 0) {
                    $stmt = $db->prepare("UPDATE categorias SET nombre = ?, icono = ?, slug = ?, orden = ?, activo = ? WHERE id = ?");
                    $stmt->execute([$nombre, $icono, $slug, $orden, $activo, $id]);
                } else {
                    $stmt = $db->prepare("INSERT INTO categorias (nombre, icono, slug, orden, activo) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nombre, $icono, $slug, $orden, $activo]);
                    $id = (int)$db->lastInsertId();
                }
                $savedInDb = true;
            } catch (Throwable $e) {
                error_log("Error en base de datos al guardar categoría: " . $e->getMessage());
            }
        }

        // Sincronizar en data/categories.json
        $catData = [
            'id' => $id,
            'nombre' => $nombre,
            'icono' => $icono,
            'slug' => $slug,
            'orden' => $orden,
            'activo' => $activo
        ];
        syncCategoryToJson($catData);

        $editingCategory = null;
        $message = $id > 0 ? "¡Categoría actualizada exitosamente!" : "¡Categoría creada exitosamente!";
        $messageType = "success";
    }
}

// Consultar todas las categorías con conteo de productos
$categories = [];
$catsFromDb = false;
if ($db) {
    try {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM productos WHERE categoria_id = c.id) AS total_productos
                FROM categorias c
                ORDER BY c.orden ASC, c.id ASC";
        $categories = $db->query($sql)->fetchAll();
        $catsFromDb = true;
    } catch (Throwable $e) {}
}

if (!$catsFromDb) {
    $categories = getAllCategoriesFromJson();
    $allProds = getAllProductsFromJson();
    foreach ($categories as $idx => $cat) {
        $count = 0;
        foreach ($allProds as $p) {
            if (($p['category'] ?? '') === $cat['slug']) {
                $count++;
            }
        }
        $categories[$idx]['total_productos'] = $count;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Categorías | Rincón 3D Admin</title>
  <link rel="icon" type="image/jpeg" href="../assets/img/logo.jpg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
  <?php echo renderAdminBgStyle(); ?>
  <style>
    .cat-layout {
      display: grid;
      grid-template-columns: 360px 1fr;
      gap: 2rem;
      align-items: start;
    }
    .emoji-picker-row {
      display: flex;
      gap: 0.35rem;
      flex-wrap: wrap;
      margin-top: 0.35rem;
    }
    .emoji-btn {
      background: var(--admin-bg);
      border: 1px solid var(--admin-blush-border);
      border-radius: 6px;
      padding: 4px 8px;
      cursor: pointer;
      font-size: 1rem;
      transition: all 0.2s;
    }
    .emoji-btn:hover {
      background: var(--admin-blush);
      transform: scale(1.15);
    }
    @media (max-width: 900px) {
      .cat-layout {
        grid-template-columns: 1fr;
      }
    }
  </style>
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
        <a href="categorias.php" class="admin-nav-link active">🏷️ Categorías</a>
        <a href="pedidos.php" class="admin-nav-link">🛍️ Pedidos</a>
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
        <h1 class="page-title">Gestión de Categorías</h1>
        <p class="page-desc">Crea, edita y organiza las secciones de tu tienda de impresión 3D.</p>
      </div>
    </div>

    <?php if (!empty($message)): ?>
      <div class="alert-box alert-<?php echo $messageType; ?>">
        <span><?php echo htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>

    <div class="cat-layout">
      
      <!-- Columna Formulario (Crear / Editar) -->
      <div class="form-card" style="padding: 1.5rem;">
        <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--admin-wine); margin-bottom: 1.25rem;">
          <?php echo $editingCategory ? '✏️ Editar Categoría' : '+ Nueva Categoría'; ?>
        </h3>

        <form method="POST" action="categorias.php">
          <input type="hidden" name="categoria_id" value="<?php echo $editingCategory['id'] ?? 0; ?>">

          <div class="form-group">
            <label class="form-label">Nombre de la Categoría *</label>
            <input type="text" id="cat-nombre" name="nombre" class="form-control" required 
                   placeholder="Ej: Llaveros & Souvenirs" 
                   value="<?php echo htmlspecialchars($editingCategory['nombre'] ?? ''); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Ícono / Emoji</label>
            <input type="text" id="cat-icono" name="icono" class="form-control" style="width: 80px; text-align: center; font-size: 1.25rem;" 
                   value="<?php echo htmlspecialchars($editingCategory['icono'] ?? '✦'); ?>">
            <div class="emoji-picker-row">
              <?php 
              $emojis = ['💡', '🧉', '🪴', '📦', '🐉', '🔑', '🎁', '🏆', '🎮', '🧩', '🖨️', '✦'];
              foreach ($emojis as $em): ?>
                <button type="button" class="emoji-btn" onclick="document.getElementById('cat-icono').value='<?php echo $em; ?>'"><?php echo $em; ?></button>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Slug (URL amigable para SEO)</label>
            <input type="text" id="cat-slug" name="slug" class="form-control" 
                   placeholder="llaveros-souvenirs" 
                   value="<?php echo htmlspecialchars($editingCategory['slug'] ?? ''); ?>">
            <span style="font-size: 0.75rem; color: var(--admin-muted);">Si lo dejas vacío, se autogenera automáticamente.</span>
          </div>

          <div class="form-group">
            <label class="form-label">Orden de Visualización</label>
            <input type="number" name="orden" class="form-control" style="width: 100px;" 
                   value="<?php echo htmlspecialchars($editingCategory['orden'] ?? (count($categories) + 1)); ?>">
          </div>

          <div class="form-group">
            <label class="form-check">
              <input type="checkbox" name="activo" value="1" <?php echo (!isset($editingCategory['activo']) || $editingCategory['activo']) ? 'checked' : ''; ?>>
              <span style="font-weight: 600;">Categoría activa y visible en la tienda</span>
            </label>
          </div>

          <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">
              <?php echo $editingCategory ? '💾 Guardar Cambios' : '+ Crear Categoría'; ?>
            </button>
            <?php if ($editingCategory): ?>
              <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Columna Tabla de Categorías -->
      <div class="card-table-wrapper">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 50px;">Ícono</th>
              <th>Nombre</th>
              <th>Slug</th>
              <th>Productos</th>
              <th>Orden</th>
              <th>Estado</th>
              <th style="text-align: right;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($categories)): ?>
              <tr>
                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--admin-muted);">
                  No hay categorías registradas. Completa el formulario de la izquierda para crear la primera.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($categories as $cat): ?>
                <tr>
                  <td style="font-size: 1.35rem; text-align: center;">
                    <?php echo htmlspecialchars($cat['icono'] ?: '✦'); ?>
                  </td>
                  <td>
                    <strong style="color: var(--admin-wine); font-size: 0.95rem;">
                      <?php echo htmlspecialchars($cat['nombre']); ?>
                    </strong>
                  </td>
                  <td style="font-family: monospace; font-size: 0.8rem; color: var(--admin-muted);">
                    <?php echo htmlspecialchars($cat['slug']); ?>
                  </td>
                  <td>
                    <span class="badge badge-muted">
                      <?php echo (int)$cat['total_productos']; ?> prod.
                    </span>
                  </td>
                  <td><?php echo (int)$cat['orden']; ?></td>
                  <td>
                    <a href="categorias.php?action=toggle&id=<?php echo $cat['id']; ?>" title="Clic para alternar estado">
                      <?php if ($cat['activo']): ?>
                        <span class="badge badge-success">Activa</span>
                      <?php else: ?>
                        <span class="badge badge-muted">Pausada</span>
                      <?php endif; ?>
                    </a>
                  </td>
                  <td style="text-align: right;">
                    <a href="categorias.php?edit_id=<?php echo $cat['id']; ?>" class="btn btn-secondary btn-sm" style="margin-right: 4px;">
                      Editar
                    </a>
                    <a href="categorias.php?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-danger btn-sm" 
                       onclick="return confirm('¿Seguro que deseas eliminar la categoría <?php echo htmlspecialchars(addslashes($cat['nombre'])); ?>?')">
                      Eliminar
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>

  <script>
    // Autogenerar slug en tiempo real al escribir el nombre (solo si no estamos editando)
    const nombreInput = document.getElementById('cat-nombre');
    const slugInput = document.getElementById('cat-slug');
    <?php if (!$editingCategory): ?>
    if (nombreInput && slugInput) {
      nombreInput.addEventListener('input', () => {
        slugInput.value = nombreInput.value
          .toLowerCase()
          .trim()
          .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Quitar tildes
          .replace(/[^a-z0-9-]+/g, '-')                     // Convertir a guiones
          .replace(/^-+|-+$/g, '');
      });
    }
    <?php endif; ?>
  </script>
</body>
</html>
