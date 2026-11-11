<?php
$pageTitle = 'Rentabilidad';
require_once __DIR__ . '/../includes/header.php';

$pdo = getConnection();

try {
$stmt = $pdo->query("
    SELECT c.id, c.nombre, c.apellido,
           COALESCE(SUM(p.monto_prestado - p.abonos_capital), 0) AS capital_vigente,
           COALESCE(SUM(CASE WHEN p.fuente_fondos = 'propio' THEN (p.monto_prestado - p.abonos_capital) * (p.tasa_interes / 100) ELSE 0 END), 0) AS interes_bruto,
           COALESCE(SUM(CASE WHEN p.fuente_fondos = 'apalancado' THEN (p.monto_prestado - p.abonos_capital) * (p.tasa_acreedor / 100) ELSE 0 END), 0) AS interes_pagado,
           COALESCE(SUM(p.utilidad_mensual), 0) AS utilidad_neta,
           COALESCE(pg.total_cobrado, 0) AS total_cobrado,
           CASE WHEN SUM(p.monto_prestado - p.abonos_capital) > 0
               THEN ROUND(SUM(p.utilidad_mensual) / SUM(p.monto_prestado - p.abonos_capital) * 100, 1)
               ELSE 0 END AS margen_pct,
           CASE WHEN SUM(p.monto_prestado - p.abonos_capital) > 0
               THEN ROUND(SUM(p.utilidad_mensual) * 12 / SUM(p.monto_prestado - p.abonos_capital) * 100, 1)
               ELSE 0 END AS rendimiento_anual
    FROM clientes c
    INNER JOIN prestamos p ON p.cliente_id = c.id
    LEFT JOIN (
        SELECT p2.cliente_id, SUM(pg.monto_pagado) AS total_cobrado
        FROM pagos pg INNER JOIN prestamos p2 ON pg.prestamo_id = p2.id
        GROUP BY p2.cliente_id
    ) pg ON pg.cliente_id = c.id
    WHERE p.estado IN ('activo','moroso')
    GROUP BY c.id
    ORDER BY utilidad_neta DESC
");
$deudores = $stmt->fetchAll();

$totalBruto = 0; $totalPagado = 0; $totalNeto = 0;
foreach ($deudores as $d) {
    $totalBruto += $d['interes_bruto'];
    $totalPagado += $d['interes_pagado'];
    $totalNeto += $d['utilidad_neta'];
}
} catch (Exception $e) {
    error_log("Error en rentabilidad: " . $e->getMessage());
    $deudores = [];
    $totalBruto = 0; $totalPagado = 0; $totalNeto = 0;
}
?>

<div class="page-header animate-in">
    <div class="page-header-row">
        <div>
            <?php renderBreadcrumb(); ?>
            <h4 class="page-title"><i class="bi bi-graph-up"></i> Rentabilidad</h4>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="bi bi-printer"></i> Imprimir</button>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
    </div>
</div>

<div class="card animate-in mb-4">
    <div class="card-body p-0">
        <div class="stat-row">
            <div>
                <div class="stat-label">Interés Bruto / Mes</div>
                <div class="stat-value" style="font-size:1.15rem; color:var(--accent);"><?= formatMoney($totalBruto) ?></div>
            </div>
            <div>
                <div class="stat-label">Interés que Pago / Mes</div>
                <div class="stat-value" style="font-size:1.15rem; color:var(--danger);"><?= formatMoney($totalPagado) ?></div>
            </div>
            <div>
                <div class="stat-label">Utilidad Neta / Mes</div>
                <div class="stat-value" style="font-size:1.15rem; color:var(--success);"><?= formatMoney($totalNeto) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card animate-in">
    <div class="card-body p-0">
        <?php if (empty($deudores)): ?>
            <div class="empty-state">
                <i class="bi bi-graph-up"></i>
                <h5>Sin datos de rentabilidad</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>#</th><th>Deudor</th><th class="num">Capital Vigente</th><th class="num">Int. Bruto/Mes</th><th class="num">Int. que Pago/Mes</th><th class="num">Utilidad Neta/Mes</th><th class="num">Total Cobrado</th><th class="num">Margen %</th><th class="num">Rendim. Anual</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deudores as $i => $d): ?>
                            <?php
                            if ($d['utilidad_neta'] <= 0) { $calif = 'Sin utilidad'; $califClass = 'badge-danger'; }
                            elseif ($d['margen_pct'] >= 70) { $calif = 'Buena'; $califClass = 'badge-success'; }
                            elseif ($d['margen_pct'] >= 40) { $calif = 'Regular'; $califClass = 'badge-warning'; }
                            else { $calif = 'Baja'; $califClass = 'badge-danger'; }
                            ?>
                            <tr>
                                <td style="color:var(--text-muted);"><?= $i + 1 ?></td>
                                <td><strong><a href="estado_cuenta.php?cliente_id=<?= $d['id'] ?>"><?= sanitize($d['nombre'] . ' ' . $d['apellido']) ?></a></strong></td>
                                <td class="num"><?= formatMoney($d['capital_vigente']) ?></td>
                                <td class="num"><?= formatMoney($d['interes_bruto']) ?></td>
                                <td class="num" style="color:var(--danger);"><?= formatMoney($d['interes_pagado']) ?></td>
                                <td class="num fw-bold" style="color:<?= $d['utilidad_neta'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= formatMoney($d['utilidad_neta']) ?></td>
                                <td class="num"><?= formatMoney($d['total_cobrado']) ?></td>
                                <td class="num"><?= $d['margen_pct'] ?>%</td>
                                <td><span class="badge <?= $califClass ?>"><?= $calif ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:600; border-top:1px solid var(--border-strong);">
                            <td colspan="2">TOTALES</td>
                            <td></td>
                            <td class="num"><?= formatMoney($totalBruto) ?></td>
                            <td class="num" style="color:var(--danger);"><?= formatMoney($totalPagado) ?></td>
                            <td class="num" style="color:var(--success);"><?= formatMoney($totalNeto) ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
