<?php
$pageTitle = 'Historial de Pagos';
require_once __DIR__ . '/../includes/header.php';

$pdo = getConnection();
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$porPagina = 20;
$offset = ($pagina - 1) * $porPagina;
$filtroFecha = $_GET['fecha'] ?? '';
if ($filtroFecha !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroFecha)) $filtroFecha = '';
$filtroTipo = $_GET['tipo'] ?? '';
if (!in_array($filtroTipo, ['', 'cuota', 'interes', 'abono'], true)) $filtroTipo = '';

$where = [];
$params = [];
if ($filtroFecha) { $where[] = "pg.fecha_pago = ?"; $params[] = $filtroFecha; }
if ($filtroTipo) { $where[] = "COALESCE(pg.tipo_pago, 'cuota') = ?"; $params[] = $filtroTipo; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$totFilCap = 0; $totFilInt = 0; $totFilTot = 0;

try {
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pagos pg $whereSql");
$countStmt->execute($params);
$totalRegistros = $countStmt->fetchColumn();
$totalPaginas = ceil($totalRegistros / $porPagina);

$stmt = $pdo->prepare("
    SELECT pg.*, p.monto_prestado, p.plazo_meses, p.num_cuotas, CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre, c.cedula
    FROM pagos pg INNER JOIN prestamos p ON pg.prestamo_id = p.id INNER JOIN clientes c ON p.cliente_id = c.id
    $whereSql ORDER BY pg.fecha_pago DESC, pg.id DESC LIMIT $porPagina OFFSET $offset
");
$stmt->execute($params);
$pagos = $stmt->fetchAll();

// Totales del historial con los filtros aplicados (todas las páginas, no solo esta)
try {
    $totStmt = $pdo->prepare("SELECT COALESCE(SUM(capital_pagado),0), COALESCE(SUM(interes_pagado),0), COALESCE(SUM(monto_pagado),0) FROM pagos pg $whereSql");
    $totStmt->execute($params);
    $totRow = $totStmt->fetch(PDO::FETCH_NUM);
    $totFilCap = floatval($totRow[0] ?? 0);
    $totFilInt = floatval($totRow[1] ?? 0);
    $totFilTot = floatval($totRow[2] ?? 0);
} catch (Exception $e) { error_log("Error en totales pagos: " . $e->getMessage()); }

if (($_GET['export'] ?? '') === 'csv') {
    while (ob_get_level() > 0) { ob_end_clean(); } // descarta el HTML del header ya generado
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pagos_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM para que Excel muestre bien los acentos
    $f = fopen('php://output', 'w');
    fputcsv($f, ['ID', 'Prestamo', 'Cliente', 'Cedula', 'Cuota', 'Tipo', 'Capital', 'Interes', 'Mora', 'Total', 'Fecha']);
    $tiposCsv = ['cuota' => 'Cuota', 'interes' => 'Interes', 'abono' => 'Abono'];
    foreach ($pagos as $pg) {
        fputcsv($f, [$pg['id'], $pg['prestamo_id'], $pg['cliente_nombre'], $pg['cedula'], $pg['numero_cuota'], $tiposCsv[$pg['tipo_pago'] ?? 'cuota'] ?? 'Cuota', $pg['capital_pagado'], $pg['interes_pagado'], $pg['mora_pagada'], $pg['monto_pagado'], $pg['fecha_pago']]);
    }
    fclose($f);
    exit();
}
} catch (Exception $e) {
    error_log("Error en historial de pagos: " . $e->getMessage());
    $totalRegistros = 0;
    $totalPaginas = 0;
    $pagos = [];
}
?>

<div class="page-header animate-in">
    <div class="page-header-row">
        <div>
            <?php renderBreadcrumb(); ?>
            <h4 class="page-title"><i class="bi bi-credit-card"></i> Historial de Pagos</h4>
            <span class="page-subtitle"><?= $totalRegistros ?> registro(s)</span>
        </div>
        <div>
            <a href="?fecha=<?= urlencode($filtroFecha) ?>&tipo=<?= urlencode($filtroTipo) ?>&export=csv" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i> Excel</a>
        </div>
    </div>
</div>

<div class="search-bar animate-in">
    <i class="bi bi-calendar3"></i>
    <form method="GET">
        <input type="date" name="fecha" value="<?= sanitize($filtroFecha) ?>">
        <select name="tipo" style="background:transparent; border:none; border-bottom:1px solid var(--border-strong); color:var(--text); font-size:0.85rem; padding:6px 0; font-family:inherit;">
            <option value="">Todos los tipos</option>
            <option value="cuota" <?= $filtroTipo === 'cuota' ? 'selected' : '' ?>>Cuota</option>
            <option value="interes" <?= $filtroTipo === 'interes' ? 'selected' : '' ?>>Interés</option>
            <option value="abono" <?= $filtroTipo === 'abono' ? 'selected' : '' ?>>Abono</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
        <?php if ($filtroFecha || $filtroTipo): ?>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<div class="card animate-in">
    <div class="card-body p-0">
        <?php if (empty($pagos)): ?>
            <div class="empty-state">
                <i class="bi bi-credit-card"></i>
                <h5><?= ($filtroFecha || $filtroTipo) ? 'Sin pagos con ese filtro' : 'Sin pagos registrados' ?></h5>
                <p>Los pagos aparecerán aquí una vez registrados desde un préstamo.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>Cliente</th><th>Préstamo</th><th>Cuota</th><th>Tipo</th><th class="num">Capital</th><th class="num">Interés</th><th class="num">Total</th><th>Fecha</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $i => $pg): ?>
                            <tr>
                                <td style="color:var(--text-muted);"><?= $offset + $i + 1 ?></td>
                                <td><strong><?= sanitize($pg['cliente_nombre']) ?></strong><br><small style="color:var(--text-muted);"><?= sanitize($pg['cedula']) ?></small></td>
                                <td><a href="../prestamos/ver.php?id=<?= $pg['prestamo_id'] ?>">#<?= $pg['prestamo_id'] ?></a></td>
                                <td><?= ($pg['num_cuotas'] ?? 0) ? $pg['numero_cuota'] . '/' . ($pg['num_cuotas'] ?: $pg['plazo_meses']) : $pg['numero_cuota'] . '/∞' ?></td>
                                <td><span class="badge badge-<?= ($pg['tipo_pago'] ?? 'cuota') === 'abono' ? 'success' : (($pg['tipo_pago'] ?? 'cuota') === 'interes' ? 'warning' : 'primary') ?>" style="font-size:0.65rem;"><?= ['cuota' => 'Cuota', 'interes' => 'Interés', 'abono' => 'Abono'][$pg['tipo_pago'] ?? 'cuota'] ?? 'Cuota' ?></span></td>
                                <td class="num"><?= formatMoney($pg['capital_pagado']) ?></td>
                                <td class="num"><?= formatMoney($pg['interes_pagado']) ?></td>
                                <td class="num fw-bold"><?= formatMoney($pg['monto_pagado']) ?></td>
                                <td style="font-size:0.78rem; color:var(--text-muted);"><?= date('d/m/Y', strtotime($pg['fecha_pago'])) ?></td>
                                <td><a href="recibo.php?id=<?= $pg['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Imprimir recibo"><i class="bi bi-printer"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:600; border-top:1px solid var(--border-strong);">
                            <td colspan="5">TOTALES (<?= $totalRegistros ?> mov.)</td>
                            <td class="num" style="color:var(--success);"><?= formatMoney($totFilCap) ?></td>
                            <td class="num" style="color:var(--warning, #d97706);"><?= formatMoney($totFilInt) ?></td>
                            <td class="num fw-bold" style="color:var(--accent);"><?= formatMoney($totFilTot) ?></td>
                            <td colspan="2" style="font-size:0.68rem; color:var(--text-muted); font-weight:400;">todas las páginas</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($totalPaginas > 1): ?>
    <nav class="mt-3 d-flex justify-content-center">
        <ul class="pagination">
            <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&fecha=<?= urlencode($filtroFecha) ?>&tipo=<?= urlencode($filtroTipo) ?>">«</a>
            </li>
            <?php $ini = max(1, min($pagina - 4, max(1, $totalPaginas - 9))); $fin = min($totalPaginas, $ini + 9); for ($p = $ini; $p <= $fin; $p++): ?>
                <li class="page-item <?= $p == $pagina ? 'active' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $p ?>&fecha=<?= urlencode($filtroFecha) ?>&tipo=<?= urlencode($filtroTipo) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&fecha=<?= urlencode($filtroFecha) ?>&tipo=<?= urlencode($filtroTipo) ?>">»</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
