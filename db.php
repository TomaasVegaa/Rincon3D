<?php
/**
 * =============================================================================
 * CONEXIÓN A BASE DE DATOS MYSQL (PDO)
 * RINCÓN 3D - Compatible con XAMPP y Hostinger
 * =============================================================================
 */

// Detección automática de entorno (Local vs InfinityFree vs Hostinger)
$httpHost = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
$isLocal = (stripos($httpHost, 'localhost') !== false || stripos($httpHost, '127.0.0.1') !== false);
$isInfinityFree = (stripos($httpHost, 'ct.ws') !== false || stripos($httpHost, 'infinityfree') !== false || stripos($httpHost, 'epizy') !== false);

if (!defined('DB_HOST')) {
    if ($isLocal) {
        // 1. Configuración para XAMPP en tu computadora
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'rincon3d');
        define('DB_USER', 'root');
        define('DB_PASS', '');
    } elseif ($isInfinityFree) {
        // 2. Configuración para InfinityFree (rincon3d.ct.ws)
        define('DB_HOST', 'sql309.infinityfree.com');
        define('DB_NAME', 'if0_42396337_rincon3d');
        define('DB_USER', 'if0_42396337');
        define('DB_PASS', 'tomas140203');
    } else {
        // 3. Configuración para Hostinger (o dominio de producción)
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'u390617164_rincon3D');
        define('DB_USER', 'u390617164_rincon3D');
        define('DB_PASS', 'Tomas140203');
    }
}

/**
 * Retorna la instancia PDO de conexión a la base de datos
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (Throwable $e) {
            error_log("Error de conexión a MySQL: " . $e->getMessage());
            return null;
        }
    }
    return $pdo;
}
