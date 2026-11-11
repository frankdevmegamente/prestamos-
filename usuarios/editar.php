<?php
$pageTitle = 'Editar Usuario';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$pdo = getConnection();
$id = intval($_GET['id'] ?? 0);
$errors = [];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();
if (!$usuario) { flash('danger', 'Usuario no encontrado'); redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $rol = $_POST['rol'] ?? 'cajero';
        $estado = $_POST['estado'] ?? 'activo';
        $password = $_POST['password'] ?? '';
        if (!in_array($rol, ['admin', 'editor', 'cajero', 'lector'], true)) $rol = 'cajero';
        if (!in_array($estado, ['activo', 'inactivo'], true)) $estado = 'activo';
        if (empty($nombre)) $errors[] = 'El nombre es obligatorio';
        if ($password !== '' && strlen($password) < 8) $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres';
        if ($id === intval($_SESSION['user_id'] ?? 0) && $estado === 'inactivo') $errors[] = 'No puede desactivar su propio usuario';
        if (empty($errors)) {
            try {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE usuarios SET nombre=?, rol=?, estado=?, password_hash=? WHERE id=?");
                    $upd->execute([$nombre, $rol, $estado, $hash, $id]);
                } else {
                    $upd = $pdo->prepare("UPDATE usuarios SET nombre=?, rol=?, estado=? WHERE id=?");
                    $upd->execute([$nombre, $rol, $estado, $id]);
                }
                $_SESSION['user_rol'] = $id === intval($_SESSION['user_id'] ?? 0) ? $rol : ($_SESSION['user_rol'] ?? 'admin');
                audit($pdo, 'editar_usuario', 'usuarios', $id, "rol=$rol estado=$estado");
                flash('success', 'Usuario actualizado');
                redirect('index.php');
            } catch (Exception $e) {
                error_log("Error editando usuario: " . $e->getMessage());
                $errors[] = 'Error al actualizar';
            }
        }
    }
} else {
    $nombre = $usuario['nombre']; $rol = $usuario['rol'] ?? 'cajero'; $estado = $usuario['estado'];
}
?>
<div class="page-header animate-in">
    <div class="page-header-row">
        <div>
            <?php renderBreadcrumb(); ?>
            <h4 class="page-title"><i class="bi bi-pencil"></i> Editar Usuario <?= sanitize($usuario['username']) ?></h4>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
</div>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger animate-in"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="card animate-in">
    <div class="card-body">
        <form method="POST" data-loading>
            <?= csrfField() ?>
            <div class="floating-group"><input type="text" name="nombre" placeholder=" " value="<?= sanitize($nombre) ?>" required maxlength="100"><label>Nombre *</label></div>
            <div class="floating-group">
                <select name="rol"><option value="admin" <?= $rol === 'admin' ? 'selected' : '' ?>>admin</option><option value="editor" <?= $rol === 'editor' ? 'selected' : '' ?>>editor</option><option value="cajero" <?= $rol === 'cajero' ? 'selected' : '' ?>>cajero</option><option value="lector" <?= $rol === 'lector' ? 'selected' : '' ?>>lector</option></select>
                <label>Rol</label>
            </div>
            <div class="floating-group">
                <select name="estado"><option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activo</option><option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option></select>
                <label>Estado</label>
            </div>
            <div class="floating-group"><input type="password" name="password" placeholder=" " minlength="8" autocomplete="new-password"><label>Nueva contraseña (vacío = no cambiar)</label></div>
            <div class="text-end mt-3"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Actualizar</button></div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
