<?php
$pageTitle = 'Auditoría';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

$pdo = getConnection();
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$porPagina = 20;
$offset = ($pagina - 1) * $porPagina;
try {
    $total = $pdo->query("SELECT COUNT(*) FROM auditoria")->fetchColumn();
    $totalPaginas = (int)ceil($total / $porPagina);
    $stmt = $pdo->prepare("SELECT * FROM auditoria ORDER BY fecha DESC, id DESC LIMIT $porPagina OFFSET $offset");
    $stmt->execute();
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    $rows = []; $total = 0; $totalPaginas = 0;
}
?>
<div class="page-header animate-in">
    <div class="page-header-row">
        <div>
            <?php renderBreadcrumb(); ?>
            <h4 class="page-title"><i class="bi bi-shield-check"></i> Auditoría (<?= $total ?>)</h4>
        </div>
    </div>
</div>
<div class="card animate-in">
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="empty-state"><i class="bi bi-shield-check"></i><p>Sin registros. Ejecute la migración fase3 y las acciones se registrarán automáticamente.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Tabla</th><th>ID</th><th>Detalle</th><th>IP</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td style="font-size:0.78rem;"><?= sanitize($r['fecha']) ?></td>
                        <td><?= sanitize($r['username'] ?? '-') ?></td>
                        <td><strong><?= sanitize($r['accion']) ?></strong></td>
                        <td><?= sanitize($r['tabla'] ?? '-') ?></td>
                        <td><?= $r['registro_id'] ? intval($r['registro_id']) : '-' ?></td>
                        <td style="font-size:0.78rem;"><?= sanitize($r['detalle'] ?? '-') ?></td>
                        <td style="font-size:0.78rem;"><?= sanitize($r['ip'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php if ($totalPaginas > 1): ?>
<nav class="mt-3 d-flex justify-content-center"><ul class="pagination">
    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?pagina=<?= $pagina - 1 ?>">«</a></li>
    <?php $ini = max(1, min($pagina - 4, max(1, $totalPaginas - 9))); $fin = min($totalPaginas, $ini + 9); for ($p = $ini; $p <= $fin; $p++): ?>
    <li class="page-item <?= $p == $pagina ? 'active' : '' ?>"><a class="page-link" href="?pagina=<?= $p ?>"><?= $p ?></a></li>
    <?php endfor; ?>
    <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>"><a class="page-link" href="?pagina=<?= $pagina + 1 ?>">»</a></li>
</ul></nav>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
