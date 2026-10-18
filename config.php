<?php
/**
 * =============================================================================
 * CONFIGURACIÓN GENERAL - RINCÓN 3D
 * Conexión a Base de Datos y Valores en Vivo
 * =============================================================================
 */

require_once __DIR__ . '/db.php';

// Valores por defecto (fallback en caso de no conectar a BD)
$defaults = [
    'store_name' => 'Rincón 3D',
    'store_tagline' => 'Impresiones 3D & Diseños Personalizados',
    'whatsapp_phone' => '5491122334455',
    'currency_symbol' => '$',
    'shipping_cost' => 3500,
    'free_shipping_threshold' => 35000,
    'bank_alias' => 'RINCON.3D.MP',
    'bank_cbu' => '0000003100085469213456',
    'bank_holder' => 'Elisa - Rincón 3D',
    'bank_discount_percent' => 10,
    'banner_visible' => 1,
    'banner_title' => 'Arte, diseño y tecnología en cada rincón',
    'banner_description' => 'Fabricamos piezas únicas con filamentos biodegradables de primera calidad. Desde recuerdos personalizados en litofanía y mates térmicos hasta iluminación ambiental y decoración.'
];

// Cargar configuración desde la Base de Datos o archivo de respaldo
$db = getDB();
$settingsLoaded = false;
if ($db) {
    try {
        $stmt = $db->query("SELECT clave, valor FROM configuracion");
        while ($row = $stmt->fetch()) {
            $key = $row['clave'];
            $val = $row['valor'];
            if (isset($defaults[$key])) {
                $defaults[$key] = is_numeric($defaults[$key]) ? (float)$val : $val;
            }
        }
        $settingsLoaded = true;
    } catch (Throwable $e) {
        error_log("Error cargando configuracion desde DB: " . $e->getMessage());
    }
}

if (!$settingsLoaded) {
    $settingsPath = __DIR__ . '/data/settings.json';
    if (file_exists($settingsPath)) {
        $saved = json_decode(file_get_contents($settingsPath), true);
        if (is_array($saved)) {
            $defaults = array_merge($defaults, $saved);
        }
    }
}

// Definición de constantes seguras para su uso en toda la aplicación
if (!defined('STORE_NAME')) define('STORE_NAME', $defaults['store_name']);
if (!defined('STORE_TAGLINE')) define('STORE_TAGLINE', $defaults['store_tagline']);
if (!defined('WHATSAPP_PHONE')) define('WHATSAPP_PHONE', $defaults['whatsapp_phone']);
$currencyClean = (!empty($defaults['currency_symbol']) && !is_numeric($defaults['currency_symbol'])) ? $defaults['currency_symbol'] : '$';
if (!defined('APP_CURRENCY')) define('APP_CURRENCY', $currencyClean);
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', $currencyClean);
if (!defined('SHIPPING_COST_DEFAULT')) define('SHIPPING_COST_DEFAULT', (float)$defaults['shipping_cost']);
if (!defined('FREE_SHIPPING_THRESHOLD')) define('FREE_SHIPPING_THRESHOLD', (float)$defaults['free_shipping_threshold']);
if (!defined('BANK_ALIAS')) define('BANK_ALIAS', $defaults['bank_alias']);
if (!defined('BANK_CBU')) define('BANK_CBU', $defaults['bank_cbu']);
if (!defined('BANK_HOLDER')) define('BANK_HOLDER', $defaults['bank_holder']);
if (!defined('BANK_DISCOUNT_PERCENT')) define('BANK_DISCOUNT_PERCENT', (float)$defaults['bank_discount_percent']);
if (!defined('BANNER_VISIBLE')) define('BANNER_VISIBLE', (int)$defaults['banner_visible']);
if (!defined('BANNER_TITLE')) define('BANNER_TITLE', $defaults['banner_title']);
if (!defined('BANNER_DESCRIPTION')) define('BANNER_DESCRIPTION', $defaults['banner_description']);

/**
 * Obtiene los productos activos desde MySQL con sus imágenes, colores y especificaciones.
 * Si la BD no está disponible, lee automáticamente data/products.json como respaldo.
 */
function getProducts() {
    $db = getDB();
    if ($db) {
        try {
            // 1. Obtener productos activos
            $sql = "SELECT p.*, c.slug AS cat_slug, c.nombre AS cat_nombre
                    FROM productos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    WHERE p.activo = 1
                    ORDER BY p.orden ASC, p.id DESC";
            $stmt = $db->query($sql);
            $rows = $stmt->fetchAll();

            if (!empty($rows)) {
                $productIds = array_column($rows, 'id');
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));

                // 2. Obtener imágenes
                $stmtImgs = $db->prepare("SELECT producto_id, url_imagen, es_principal 
                                          FROM producto_imagenes 
                                          WHERE producto_id IN ($placeholders) 
                                          ORDER BY orden ASC");
                $stmtImgs->execute($productIds);
                $imagesByProduct = [];
                while ($img = $stmtImgs->fetch()) {
                    $u = $img['url_imagen'];
                    // Si el archivo en disco no existe (por ejemplo borrado o movido), usar imagen válida
                    $localPath = __DIR__ . '/' . $u;
                    if (strpos($u, 'assets/') === 0 && !file_exists($localPath)) {
                        if ($img['producto_id'] == 1 && file_exists(__DIR__ . '/assets/img/florero_japandei_1.jpg')) {
                            $u = 'assets/img/florero_japandei_1.jpg';
                        } elseif ($img['producto_id'] == 2 && file_exists(__DIR__ . '/assets/img/florero_japandi_1.jpg')) {
                            $u = 'assets/img/florero_japandi_1.jpg';
                        }
                    }
                    $imagesByProduct[$img['producto_id']][] = $u;
                }

                // 3. Obtener colores
                $stmtCols = $db->prepare("SELECT producto_id, nombre_color 
                                          FROM producto_colores 
                                          WHERE producto_id IN ($placeholders)");
                $stmtCols->execute($productIds);
                $colorsByProduct = [];
                while ($col = $stmtCols->fetch()) {
                    $colorsByProduct[$col['producto_id']][] = $col['nombre_color'];
                }

                // 4. Obtener especificaciones
                $stmtSpecs = $db->prepare("SELECT producto_id, clave, valor 
                                           FROM producto_especificaciones 
                                           WHERE producto_id IN ($placeholders)");
                $stmtSpecs->execute($productIds);
                $specsByProduct = [];
                while ($sp = $stmtSpecs->fetch()) {
                    $specsByProduct[$sp['producto_id']][$sp['clave']] = $sp['valor'];
                }

                // Armar estructura uniforme
                $products = [];
                foreach ($rows as $r) {
                    $pid = (int)$r['id'];
                    $imgs = $imagesByProduct[$pid] ?? ['assets/img/logo.jpg'];
                    $cols = $colorsByProduct[$pid] ?? [];
                    $specs = $specsByProduct[$pid] ?? [];

                    $products[] = [
                        'id' => $pid,
                        'title' => $r['titulo'],
                        'category' => $r['cat_slug'] ?: 'general',
                        'categoryName' => $r['cat_nombre'] ?: 'General',
                        'price' => (float)$r['precio'],
                        'badge' => $r['badge'],
                        'customizable' => (bool)$r['customizable'],
                        'description' => $r['descripcion'],
                        'images' => $imgs,
                        'colors' => $cols,
                        'specs' => $specs
                    ];
                }
                return $products;
            }
        } catch (Throwable $e) {
            error_log("Error consultando productos de MySQL: " . $e->getMessage());
        }
    }

    // Respaldo desde JSON si no hay BD o falló
    $jsonPath = __DIR__ . '/data/products.json';
    if (file_exists($jsonPath)) {
        $content = file_get_contents($jsonPath);
        $all = json_decode($content, true) ?: [];
        return array_values(array_filter($all, function($p) {
            return !isset($p['active']) || $p['active'] === true || $p['active'] === 1;
        }));
    }

    return [];
}

/**
 * Retorna todos los productos del archivo JSON (activos e inactivos)
 */
function getAllProductsFromJson() {
    $jsonPath = __DIR__ . '/data/products.json';
    if (file_exists($jsonPath)) {
        $content = file_get_contents($jsonPath);
        return json_decode($content, true) ?: [];
    }
    return [];
}

/**
 * Guarda el array completo de productos en data/products.json
 */
function saveProductsJson(array $products) {
    $jsonPath = __DIR__ . '/data/products.json';
    $json = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return file_put_contents($jsonPath, $json) !== false;
}

/**
 * Guarda o actualiza un producto individual en data/products.json
 */
function syncProductToJson(array $data) {
    $products = getAllProductsFromJson();
    $found = false;
    $targetId = (int)($data['id'] ?? 0);

    foreach ($products as $i => $p) {
        if ((int)$p['id'] === $targetId) {
            $products[$i] = array_merge($p, $data);
            $found = true;
            break;
        }
    }

    if (!$found) {
        if ($targetId <= 0) {
            $maxId = 0;
            foreach ($products as $p) {
                if ((int)$p['id'] > $maxId) $maxId = (int)$p['id'];
            }
            $data['id'] = $maxId + 1;
        }
        $products[] = $data;
    }

    return saveProductsJson($products);
}

/**
 * Elimina un producto de data/products.json
 */
function deleteProductFromJson(int $productId) {
    $products = getAllProductsFromJson();
    $filtered = array_values(array_filter($products, function($p) use ($productId) {
        return (int)$p['id'] !== $productId;
    }));
    return saveProductsJson($filtered);
}

/**
 * Alterna el estado activo/pausado de un producto en data/products.json
 */
function toggleProductJson(int $productId) {
    $products = getAllProductsFromJson();
    foreach ($products as $i => $p) {
        if ((int)$p['id'] === $productId) {
            $current = !isset($p['active']) || $p['active'] === true || $p['active'] === 1;
            $products[$i]['active'] = !$current;
            break;
        }
    }
    return saveProductsJson($products);
}

/**
 * Retorna todas las categorías desde data/categories.json
 */
function getAllCategoriesFromJson() {
    $path = __DIR__ . '/data/categories.json';
    if (file_exists($path)) {
        return json_decode(file_get_contents($path), true) ?: [];
    }
    return [];
}

/**
 * Guarda las categorías en data/categories.json
 */
function saveCategoriesJson(array $categories) {
    $path = __DIR__ . '/data/categories.json';
    return file_put_contents($path, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Guarda o actualiza una categoría individual en data/categories.json
 */
function syncCategoryToJson(array $data) {
    $categories = getAllCategoriesFromJson();
    $found = false;
    $targetId = (int)($data['id'] ?? 0);

    foreach ($categories as $i => $c) {
        if ((int)$c['id'] === $targetId) {
            $categories[$i] = array_merge($c, $data);
            $found = true;
            break;
        }
    }

    if (!$found) {
        if ($targetId <= 0) {
            $maxId = 0;
            foreach ($categories as $c) {
                if ((int)$c['id'] > $maxId) $maxId = (int)$c['id'];
            }
            $data['id'] = $maxId + 1;
        }
        $categories[] = $data;
    }

    return saveCategoriesJson($categories);
}

/**
 * Elimina una categoría de data/categories.json
 */
function deleteCategoryFromJson(int $catId) {
    $categories = getAllCategoriesFromJson();
    $filtered = array_values(array_filter($categories, function($c) use ($catId) {
        return (int)$c['id'] !== $catId;
    }));
    return saveCategoriesJson($filtered);
}

/**
 * Obtiene las categorías activas desde MySQL o data/categories.json
 */
function getCategories($onlyActive = true) {
    $db = getDB();
    if ($db) {
        try {
            $where = $onlyActive ? "WHERE activo = 1" : "";
            $stmt = $db->query("SELECT * FROM categorias $where ORDER BY orden ASC, id ASC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return $rows;
            }
        } catch (Throwable $e) {
            error_log("Error consultando categorías de MySQL: " . $e->getMessage());
        }
    }

    // Respaldo desde data/categories.json
    $jsonCats = getAllCategoriesFromJson();
    if (!empty($jsonCats)) {
        if ($onlyActive) {
            return array_values(array_filter($jsonCats, function($c) {
                return !isset($c['activo']) || $c['activo'] == 1 || $c['activo'] === true;
            }));
        }
        return $jsonCats;
    }

    // Categorías por defecto si el archivo JSON no existe
    return [
        ['id' => 1, 'slug' => 'iluminacion', 'nombre' => 'Iluminación & Lámparas', 'icono' => '💡', 'orden' => 1, 'activo' => 1],
        ['id' => 2, 'slug' => 'mates', 'nombre' => 'Mates & Bazar', 'icono' => '🧉', 'orden' => 2, 'activo' => 1],
        ['id' => 3, 'slug' => 'decoracion', 'nombre' => 'Decoración & Plantas', 'icono' => '🪴', 'orden' => 3, 'activo' => 1],
        ['id' => 4, 'slug' => 'organizacion', 'nombre' => 'Organización & Oficina', 'icono' => '📦', 'orden' => 4, 'activo' => 1],
        ['id' => 5, 'slug' => 'figuras', 'nombre' => 'Figuras & Coleccionables', 'icono' => '🐉', 'orden' => 5, 'activo' => 1]
    ];
}
