<?php
/**
 * Diagnóstico de conexión y estado del servidor Rincón 3D
 * Puedes abrir este archivo desde tu navegador: tudominio.com/check.php
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Diagnóstico del Servidor | Rincón 3D</title>
  <style>
    body { font-family: -apple-system, sans-serif; background: #F8F5F5; padding: 2rem; color: #2C1217; }
    .card { background: #fff; max-width: 600px; margin: 0 auto; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    h1 { color: #651226; margin-top: 0; font-size: 1.5rem; }
    .item { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 0.75rem; font-size: 0.9rem; }
    .ok { background: #EAF7EE; color: #1E7E4E; border: 1px solid #A7F3D0; }
    .err { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }
    .warn { background: #FEF3C7; color: #B45309; border: 1px solid #FDE68A; }
    pre { background: #F1F5F9; padding: 0.5rem; border-radius: 4px; overflow-x: auto; font-size: 0.8rem; }
  </style>
</head>
<body>
  <div class="card">
    <h1>🔧 Diagnóstico de Servidor - Rincón 3D</h1>

    <?php
    // 1. PHP Version
    echo '<div class="item ok">✓ Versión de PHP: ' . PHP_VERSION . '</div>';

    // 2. PDO Extension
    if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
        echo '<div class="item ok">✓ Extensiones PDO y PDO_MySQL activas</div>';
    } else {
        echo '<div class="item err">✗ Faltan extensiones PDO o PDO_MySQL</div>';
    }

    // 3. Cargar db.php
    require_once __DIR__ . '/db.php';
    echo '<div class="item ok">✓ Host configurado: ' . DB_HOST . ' | Base: ' . DB_NAME . '</div>';

    // 4. Test Conexión a Base de Datos
    $db = getDB();
    if ($db) {
        echo '<div class="item ok">✓ ¡Conexión a MySQL exitosa con PDO!</div>';
        
        // 5. Verificar tablas
        try {
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tables)) {
                echo '<div class="item warn">⚠️ La base de datos está conectada pero NO TIENE TABLAS todavía.<br><strong>Solución:</strong> En phpMyAdmin haz clic en "Importar" y sube el archivo <code>database.sql</code>.</div>';
            } else {
                echo '<div class="item ok">✓ Tablas encontradas (' . count($tables) . '): ' . implode(', ', $tables) . '</div>';

                // Mostrar productos de la base de datos
                require_once __DIR__ . '/config.php';
                $dbImgs = $db->query("SELECT * FROM producto_imagenes ORDER BY producto_id ASC, id ASC")->fetchAll();
                echo '<div class="item ok"><strong>Imágenes en DB:</strong><pre>' . htmlspecialchars(print_r($dbImgs, true)) . '</pre></div>';

                $dbCfg = $db->query("SELECT * FROM configuracion")->fetchAll();
                echo '<div class="item ok"><strong>Configuración en DB:</strong><pre>' . htmlspecialchars(print_r($dbCfg, true)) . '</pre></div>';

                // Check file exists on disk
                echo '<div class="item ok"><strong>Archivos en disco:</strong><pre>';
                foreach ($dbImgs as $img) {
                    $localPath = __DIR__ . '/' . $img['url_imagen'];
                    echo $img['url_imagen'] . ' -> ' . (file_exists($localPath) ? 'EXISTE (' . filesize($localPath) . ' bytes)' : 'NO EXISTE') . "\n";
                }
                echo '</pre></div>';
            }
        } catch (Throwable $e) {
            echo '<div class="item err">✗ Error consultando tablas: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        echo '<div class="item err">✗ No se pudo conectar a MySQL. Revisa que el host (' . DB_HOST . ') y la contraseña sean correctos.</div>';
    }

    // 6. Enlaces útiles
    echo '<div style="margin-top: 1.5rem; text-align: center;">';
    echo '<a href="index.php" style="color: #651226; font-weight: bold; margin-right: 1.5rem;">Ir a la Tienda</a>';
    echo '<a href="admin/login.php" style="color: #651226; font-weight: bold;">Ir al Panel Admin</a>';
    echo '</div>';
    ?>
  </div>
</body>
</html>
