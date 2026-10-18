<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

/**
 * Control de autenticación y sesión para el panel de Rincón 3D
 */
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../config.php';

function isAuthenticated() {
    return isset($_SESSION['rincon3d_admin_logged']) && $_SESSION['rincon3d_admin_logged'] === true;
}

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

function attemptLogin($username, $password) {
    $db = getDB();
    if (!$db) {
        // Fallback si no hay BD: usuario elisa / elisa123
        if ($username === 'elisa' && $password === 'elisa123') {
            $_SESSION['rincon3d_admin_logged'] = true;
            $_SESSION['rincon3d_admin_user'] = 'elisa';
            $_SESSION['rincon3d_admin_name'] = 'Elisa - Rincón 3D';
            return true;
        }
        return false;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['rincon3d_admin_logged'] = true;
            $_SESSION['rincon3d_admin_user'] = $user['username'];
            $_SESSION['rincon3d_admin_name'] = $user['nombre'];
            $_SESSION['rincon3d_admin_id'] = $user['id'];
            return true;
        }
    } catch (Throwable $e) {
        error_log("Error en login: " . $e->getMessage());
        // Fallback de rescate si la tabla no fue importada aún
        if ($username === 'elisa' && $password === 'elisa123') {
            $_SESSION['rincon3d_admin_logged'] = true;
            $_SESSION['rincon3d_admin_user'] = 'elisa';
            $_SESSION['rincon3d_admin_name'] = 'Elisa - Rincón 3D';
            return true;
        }
    }

    return false;
}
