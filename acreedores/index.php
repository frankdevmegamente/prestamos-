<?php
$pageTitle = 'Acreedores';
require_once __DIR__ . '/../includes/header.php';

$pdo = getConnection();
$busqueda = trim($_GET['buscar'] ?? '');
if (strlen($busqueda) > 100) $busqueda = substr($busqueda, 0, 100);
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$porPagina = 20;
$offset = ($pagina - 1) * $porPagina;

try {
if ($busqueda) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM acreedores WHERE nombre LIKE ? ESCAPE '!' OR telefono LIKE ? ESCAPE '!' OR rfc LIKE ? ESCAPE '!' OR ciudad LIKE ? ESCAPE '!'");
    $like = "%" . escapeLike($busqueda) . "%";
    $stmt->execute([$like, $like, $like, $like]);
    $totalRegistros = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM acreedores WHERE nombre LIKE ? ESCAPE '!' OR telefono LIKE ? ESCAPE '!' OR rfc LIKE ? ESCAPE '!' OR ciudad LIKE ? ESCAPE '!' ORDER BY fecha_registro DESC LIMIT $porPagina OFFSET $offset");
    $stmt->execute([$like, $like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT COUNT(*) FROM acreedores");
    $totalRegistros = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM acreedores ORDER BY fecha_registro DESC LIMIT $porPagina OFFSET $offset");
    $stmt->execute();
}

$acreedores = $stmt->fetchAll();
$totalPaginas = ceil($totalRegistros / $porPagina);
} catch (Exception $e) {
    error_log("Error en listado de acreedores: " . $e->getMessage());
    $acreedores = [];
    $totalRegistros = 0;
    $totalPaginas = 0;
}
?>

<div class="page-header animate-in">
    <div class="page-header-row">
        <div>
            <?php renderBreadcrumb(); ?>
            <h4 class="page-title"><i class="bi bi-bank"></i> Acreedores</h4>
        </div>
        <a href="crear.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nuevo Acreedor</a>
    </div>
</div>

<div class="search-bar animate-in">
    <i class="bi bi-search"></i>
    <form method="GET">
        <input type="text" name="buscar" placeholder="Buscar por nombre, teléfono, RFC o ciudad..." value="<?= sanitize($busqueda) ?>">
        <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
        <?php if ($busqueda): ?>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<div class="card animate-in">
    <div class="card-body p-0">
        <?php if (empty($acreedores)): ?>
            <div class="empty-state">
                <i class="bi bi-bank"></i>
                <h5><?= $busqueda ? 'Sin resultados' : 'Sin acreedores' ?></h5>
                <p><?= $busqueda ? 'Intente con otros términos.' : 'Registre sus acreedores (quienes le prestan dinero).' ?></p>
                <?php if (!$busqueda): ?>
                    <a href="crear.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nuevo Acreedor</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>RFC</th>
                            <th>Ciudad</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($acreedores as $i => $ac): ?>
                            <tr>
                                <td style="color:var(--text-muted);"><?= $offset + $i + 1 ?></td>
                                <td><strong><?= sanitize($ac['nombre']) ?></strong></td>
                                <td><?= sanitize($ac['telefono'] ?? '-') ?></td>
                                <td><?= sanitize($ac['rfc'] ?? '-') ?></td>
                                <td><?= sanitize($ac['ciudad'] ?? '-') ?></td>
                                <td><span class="badge badge-<?= $ac['estado'] ?>"><?= ucfirst($ac['estado']) ?></span></td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="ver.php?id=<?= $ac['id'] ?>" class="btn btn-sm btn-outline-info" title="Ver"><i class="bi bi-eye"></i></a>
                                        <a href="editar.php?id=<?= $ac['id'] ?>" class="btn btn-sm btn-outline-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                        <a href="eliminar.php?id=<?= $ac['id'] ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirmarAccion('¿Eliminar este acreedor?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($totalPaginas > 1): ?>
    <nav class="mt-3 d-flex justify-content-center">
        <ul class="pagination">
            <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&buscar=<?= urlencode($busqueda) ?>">«</a>
            </li>
            <?php
            $start = max(1, $pagina - 2);
            $end = min($totalPaginas, $pagina + 2);
            if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="?pagina=1&buscar=<?= urlencode($busqueda) ?>">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <?php endif;
            for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item <?= $p == $pagina ? 'active' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $p ?>&buscar=<?= urlencode($busqueda) ?>"><?= $p ?></a>
                </li>
            <?php endfor;
            if ($end < $totalPaginas): ?>
                <?php if ($end < $totalPaginas - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="?pagina=<?= $totalPaginas ?>&buscar=<?= urlencode($busqueda) ?>"><?= $totalPaginas ?></a></li>
            <?php endif; ?>
            <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&buscar=<?= urlencode($busqueda) ?>">»</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
