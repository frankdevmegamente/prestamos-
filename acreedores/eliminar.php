<?php
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id'])) {
    flash('danger', 'Debe iniciar sesión para acceder');
    redirect(BASE_PATH . 'auth/login.php');
}

$pdo = getConnection();
$id = intval($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        flash('danger', 'Token de seguridad inválido');
        redirect('index.php');
    }
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            // La BD puede no tener FKs: verificar en PHP antes de borrar
            $chk = $pdo->prepare("SELECT COUNT(*) FROM mis_deudas WHERE acreedor_id = ?");
            $chk->execute([$id]);
            $totalDeudas = (int)$chk->fetchColumn();
            $chk2 = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE acreedor_id = ?");
            $chk2->execute([$id]);
            $totalPrestamos = (int)$chk2->fetchColumn();
            if ($totalDeudas > 0 || $totalPrestamos > 0) {
                flash('danger', 'No se puede eliminar: el acreedor tiene ' . $totalDeudas . ' deuda(s) y ' . $totalPrestamos . ' préstamo(s) asociados');
                redirect('index.php');
            }
            $stmt = $pdo->prepare("DELETE FROM acreedores WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                audit($pdo, 'eliminar_acreedor', 'acreedores', $id, null);
                flash('success', 'Acreedor eliminado exitosamente');
            } else {
                flash('warning', 'No se encontró el acreedor a eliminar');
            }
        } catch (PDOException $e) {
            flash('danger', 'No se puede eliminar: el acreedor tiene deudas asociadas');
        }
    }
    redirect('index.php');
}

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT id, nombre FROM acreedores WHERE id = ?");
    $stmt->execute([$id]);
    $acreedor = $stmt->fetch();
    if (!$acreedor) {
        flash('danger', 'Acreedor no encontrado');
        redirect('index.php');
    }
} else {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Eliminación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
        .confirm-box { background: #fff; border-radius: 12px; padding: 40px; max-width: 420px; width: 100%; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .confirm-box h3 { margin-bottom: 12px; }
        .confirm-box p { color: #666; margin-bottom: 24px; }
        .btn-group { display: flex; gap: 12px; justify-content: center; }
    </style>
</head>
<body>
    <div class="confirm-box">
        <div style="font-size:2.5rem; color:#dc2626; margin-bottom:12px;"><i class="bi bi-exclamation-triangle"></i></div>
        <h3>Confirmar Eliminación</h3>
        <p>¿Está seguro de eliminar al acreedor <strong><?= sanitize($acreedor['nombre']) ?></strong>? Esta acción no se puede deshacer.</p>
        <div class="btn-group">
            <a href="index.php" class="btn btn-secondary px-4">Cancelar</a>
            <form method="POST" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-danger px-4">Eliminar</button>
            </form>
        </div>
    </div>
</body>
</html>
