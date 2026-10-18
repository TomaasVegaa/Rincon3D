<?php
/**
 * Generador Dinámico de Sitemap XML para Google
 * Compatible con Google Search Console y motores de búsqueda
 */
header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/config.php';

// Detectar protocolo y dominio dinámicamente
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
$protocol = $isHttps ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'rincon3d.ct.ws';
$baseUrl = $protocol . $host;

// Si estamos en localhost, agregar carpeta /elisa
if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    $baseUrl .= '/elisa';
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

  <!-- Página Principal (Home) -->
  <url>
    <loc><?php echo htmlspecialchars($baseUrl . '/'); ?></loc>
    <lastmod><?php echo date('Y-m-d'); ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>

  <?php
  $db = getDB();
  if ($db):
      // 1. Categorías activas
      try {
          $cats = $db->query("SELECT slug FROM categorias WHERE activo = 1 ORDER BY orden ASC")->fetchAll();
          foreach ($cats as $cat): ?>
  <url>
    <loc><?php echo htmlspecialchars($baseUrl . '/categoria/' . $cat['slug']); ?></loc>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
          <?php endforeach;
      } catch (Throwable $e) {}

      // 2. Productos activos con imágenes para Google Image Search
      try {
          $sql = "SELECT p.slug, p.titulo, p.updated_at,
                  (SELECT url_imagen FROM producto_imagenes WHERE producto_id = p.id ORDER BY es_principal DESC, orden ASC LIMIT 1) AS imagen
                  FROM productos p
                  WHERE p.activo = 1
                  ORDER BY p.id DESC";
          $prods = $db->query($sql)->fetchAll();
          foreach ($prods as $prod):
              $lastmod = !empty($prod['updated_at']) ? date('Y-m-d', strtotime($prod['updated_at'])) : date('Y-m-d');
              $imgUrl = !empty($prod['imagen']) ? $baseUrl . '/' . ltrim($prod['imagen'], '/') : '';
          ?>
  <url>
    <loc><?php echo htmlspecialchars($baseUrl . '/producto/' . $prod['slug']); ?></loc>
    <lastmod><?php echo $lastmod; ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
    <?php if (!empty($imgUrl)): ?>
    <image:image>
      <image:loc><?php echo htmlspecialchars($imgUrl); ?></image:loc>
      <image:title><?php echo htmlspecialchars($prod['titulo']); ?></image:title>
    </image:image>
    <?php endif; ?>
  </url>
          <?php endforeach;
      } catch (Throwable $e) {}
  endif;
  ?>

</urlset>
