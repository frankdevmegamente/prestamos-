<?php
$pageTitle = 'Clientes';
require_once __DIR__ . '/../includes/header.php';

$pdo = getConnection();
$busqueda = trim($_GET['buscar'] ?? '');
if (strlen($busqueda) > 100) $busqueda = substr($busqueda, 0, 100);
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$porPagina = 20;
$offset = ($pagina - 1) * $porPagina;

try {
if ($busqueda) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE nombre LIKE ? ESCAPE '!' OR apellido LIKE ? ESCAPE '!' OR cedula LIKE ? ESCAPE '!' OR telefono LIKE ? ESCAPE '!'");
    $like = "%" . escapeLike($busqueda) . "%";
    $stmt->execute([$like, $like, $like, $like]);
    $totalRegistros = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE nombre LIKE ? ESCAPE '!' OR apellido LIKE ? ESCAPE '!' OR cedula LIKE ? ESCAPE '!' OR telefono LIKE ? ESCAPE '!' ORDER BY fecha_registro DESC LIMIT $porPagina OFFSET $offset");
    $stmt->execute([$like, $like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT COUNT(*) FROM clientes");
    $totalRegistros = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM clientes ORDER BY fecha_registro DESC LIMIT $porPagina OFFSET $offset");
    $stmt->execute();
}

$clientes = $stmt->fetchAll();
$totalPaginas = ceil($totalRegistros / $porPagina);
} catch (Exception $e) {
    error_log("Error en listado de clientes: " . $e->getMessage());
    $clientes = [];
    $totalRegistros = 0;
    $totalPaginas = 0;
}
?>

<div class="page-header animate-in">
    <div class="page-header-row">
        <div>
            <?php renderBreadcrumb(); ?>
            <h4 class="page-title"><i class="bi bi-people"></i> Clientes</h4>
        </div>
        <a href="crear.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nuevo Cliente</a>
    </div>
</div>

<div class="search-bar animate-in">
    <i class="bi bi-search"></i>
    <form method="GET">
        <input type="text" name="buscar" placeholder="Buscar por nombre, razón social o teléfono..." value="<?= sanitize($busqueda) ?>">
        <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
        <?php if ($busqueda): ?>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<div class="card animate-in">
    <div class="card-body p-0">
        <?php if (empty($clientes)): ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h5><?= $busqueda ? 'Sin resultados' : 'Sin clientes' ?></h5>
                <p><?= $busqueda ? 'Intente con otros términos.' : 'Comience registrando su primer cliente.' ?></p>
                <?php if (!$busqueda): ?>
                    <a href="crear.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nuevo Cliente</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Razón social</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Registro</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $i => $cliente): ?>
                            <tr>
                                <td style="color:var(--text-muted);"><?= $offset + $i + 1 ?></td>
                                <td><strong><?= sanitize($cliente['nombre'] . ' ' . $cliente['apellido']) ?></strong></td>
                                <td><?= sanitize($cliente['cedula']) ?></td>
                                <td><?= sanitize($cliente['telefono'] ?: '-') ?></td>
                                <td><span class="badge badge-<?= $cliente['estado'] ?>"><?= ucfirst($cliente['estado']) ?></span></td>
                                <td style="font-size:0.78rem; color:var(--text-muted);"><?= date('d/m/Y', strtotime($cliente['fecha_registro'])) ?></td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="ver.php?id=<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-info" title="Ver"><i class="bi bi-eye"></i></a>
                                        <a href="editar.php?id=<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                        <a href="eliminar.php?id=<?= $cliente['id'] ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirmarAccion('¿Eliminar este cliente?')"><i class="bi bi-trash"></i></a>
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
