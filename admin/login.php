<?php
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once __DIR__ . '/auth.php';

$error = '';
if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Por favor ingresa usuario y contraseña.';
    } else {
        if (attemptLogin($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso Administración | <?php echo STORE_NAME; ?></title>
  <link rel="icon" type="image/jpeg" href="../assets/img/logo.jpg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body class="login-body">
  <div class="login-card">
    <div class="login-header">
      <img src="../assets/img/logo.jpg" alt="Logo Rincón 3D" class="login-logo">
      <h1 class="login-title">Rincón 3D</h1>
      <p class="login-subtitle">Panel de Control & Gestión</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert-box alert-error">
        <span>⚠️ <?php echo htmlspecialchars($error); ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="login-form">
      <div class="form-group">
        <label for="username" class="form-label">Usuario</label>
        <input type="text" id="username" name="username" class="form-control" required autofocus placeholder="Usuario" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        Iniciar Sesión
      </button>
    </form>

    <div class="login-footer">
      <a href="../index.php">← Volver a la Tienda</a>
    </div>
  </div>
</body>
</html>
