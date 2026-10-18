<?php
/**
 * =============================================================================
 * CONEXIÓN A BASE DE DATOS MYSQL (PDO)
 * RINCÓN 3D - Compatible con XAMPP y Hostinger
 * =============================================================================
 */

// Detección automática de entorno (Local vs Hosting)
$httpHost = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
$isLocal = (stripos($httpHost, 'localhost') !== false || stripos($httpHost, '127.0.0.1') !== false);

if (!defined('DB_HOST')) {
    if ($isLocal) {
        // Configuración para XAMPP en tu computadora
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'rincon3d');
        define('DB_USER', 'root');
        define('DB_PASS', '');
    } else {
        // =====================================================================
        // CONFIGURACIÓN PARA SERVIDOR / HOSTING
        // =====================================================================
        define('DB_HOST', 'sql309.infinityfree.com');
        define('DB_NAME', 'if0_42396337_rincon3d');
        define('DB_USER', 'if0_42396337');
        define('DB_PASS', 'tomas140203');
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
