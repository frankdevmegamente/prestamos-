<?php
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    header("Location: " . BASE_PATH . "index.php");
    exit();
}

$errors = [];

$maintenanceActive = false;
try {
    $pdoTmp = getConnection();
    $maintenanceActive = isMaintenanceMode($pdoTmp);
} catch (Exception $e) {
    $maintenanceActive = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido. Intente de nuevo.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $errors[] = 'Ingrese usuario y contraseña';
        } else {
            try {
                $pdo = getConnection();
                $stmt = $pdo->prepare("SELECT id, username, nombre, password_hash, rol FROM usuarios WHERE username = ? AND estado = 'activo'");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    $rol = $user['rol'] ?? 'admin';
                    if (isMaintenanceMode($pdo) && $rol !== 'admin') {
                        $errors[] = 'El sistema se encuentra en mantenimiento. Intente más tarde.';
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_username'] = $user['username'];
                        $_SESSION['user_nombre'] = $user['nombre'];
                        $_SESSION['user_rol'] = $rol;
                        session_regenerate_id(true);
                        session_write_close();
                        header("Location: " . BASE_PATH . "index.php");
                        exit();
                    }
                } else {
                    $errors[] = 'Usuario o contraseña incorrectos';
                }
            } catch (PDOException $e) {
                error_log("Error de login: " . $e->getMessage());
                $errors[] = 'Error al conectar con la base de datos';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Préstamos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { display:flex; justify-content:center; align-items:center; min-height:100vh; background:var(--bg); }
        .login-box { max-width:400px; width:100%; padding:40px; }
        .login-brand { text-align:center; margin-bottom:32px; }
        .login-brand i { font-size:2.5rem; color:var(--accent); }
        .login-brand h4 { margin-top:8px; font-weight:600; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-brand">
            <i class="bi bi-bank2"></i>
            <h4>Sistema de Préstamos</h4>
            <p style="color:var(--text-muted); font-size:0.85rem;">Inicie sesión para continuar</p>
        </div>

        <?php $flash = getFlash(); if ($flash): $flashType = in_array($flash['type'] ?? '', ['success','danger','warning','info'], true) ? $flash['type'] : 'info'; ?>
            <div class="alert alert-<?= $flashType ?> alert-dismissible fade show" role="alert">
                <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($maintenanceActive): ?>
            <div class="alert alert-warning">
                <i class="bi bi-tools"></i> El sistema se encuentra en mantenimiento. Solo administradores pueden ingresar. Ingresá con tu cuenta de admin para desactivarlo.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= sanitize($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="floating-group">
                <input type="text" name="username" placeholder=" " value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus>
                <label>Usuario *</label>
            </div>
            <div class="floating-group">
                <input type="password" name="password" placeholder=" " value="" required autocomplete="current-password">
                <label>Contraseña *</label>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-3" style="padding:12px;">
                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
            </button>
        </form>
    </div>

    <script src="../assets/js/app.js"></script>
</body>
</html>
