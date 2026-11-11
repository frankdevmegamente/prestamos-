<?php
// ═══════════════════════════════════════════════════════════════════
// VISTA PREVIA — Dashboard enriquecido (Opción A vs Opción B)
// Datos 100% REALES. 100% SOLO LECTURA (sin POST, sin onboarding).
// Botones A/B para comparar: B = A + banda héroe arriba.
// Borrar este archivo al aprobar el diseño.
// Uso: preview-dashboard.php (requiere login normal)
// ═══════════════════════════════════════════════════════════════════
$pageTitle = 'PREVIEW Dashboard A/B';
require_once __DIR__ . '/includes/header.php';

$pdo = getConnection();
$config = getConfiguracion($pdo);

try {
$totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes WHERE estado = 'activo'")->fetchColumn();
$totalPrestamosActivos = $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado IN ('activo','moroso')")->fetchColumn();
$prevDef = $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado IN ('activo','moroso') AND COALESCE(plazo_meses,0) > 0")->fetchColumn();
$prevInd = $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado IN ('activo','moroso') AND COALESCE(plazo_meses,0) = 0")->fetchColumn();
$prevDeudasN = $pdo->query("SELECT COUNT(*) FROM mis_deudas WHERE estado = 'activo'")->fetchColumn();
$totalMontoPrestado = $pdo->query("SELECT COALESCE(SUM(monto_prestado),0) FROM prestamos WHERE estado IN ('activo','moroso')")->fetchColumn();
$totalMontoPrestadoHistorico = $pdo->query("SELECT COALESCE(SUM(monto_prestado),0) FROM prestamos")->fetchColumn();
$totalSaldoPendiente = $pdo->query("SELECT COALESCE(SUM(saldo_pendiente),0) FROM prestamos WHERE estado IN ('activo','moroso')")->fetchColumn();
$totalCobrado = $pdo->query("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos")->fetchColumn();
$totalInteresCobrado = $pdo->query("SELECT COALESCE(SUM(interes_pagado),0) FROM pagos")->fetchColumn();
$totalInteresPagado = $pdo->query("SELECT COALESCE(SUM(interes_pagado),0) FROM pagos_deudas")->fetchColumn();
$propioActivoMonto = $pdo->query("SELECT COALESCE(SUM(monto_prestado),0) FROM prestamos WHERE fuente_fondos='propio' AND estado IN ('activo','moroso')")->fetchColumn();
$diasGraciaCfg = intval($config['dias_gracia'] ?? 0);
try {
    $candStmt = $pdo->query("SELECT id, cliente_id, saldo_pendiente, estado, fecha_vencimiento, fecha_prestamo, plazo_meses, tasa_interes, interes_vigente, fecha_ultimo_devengo FROM prestamos WHERE estado IN ('activo','moroso') AND COALESCE(saldo_pendiente, 0) > 0");
    $prestamosMorosos = 0;
    $morClientes = [];
    foreach ($candStmt->fetchAll() as $cm) {
        if (esMorosoCombinado($pdo, $cm, $config)) {
            $prestamosMorosos++;
            $morClientes[$cm['cliente_id']] = true;
        }
    }
    $totalClientesMorosos = count($morClientes);
} catch (Exception $e) { $prestamosMorosos = 0; $totalClientesMorosos = 0; $morClientes = []; }
$prestamosPagados = $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'pagado'")->fetchColumn();

$totalDebo = $pdo->query("SELECT COALESCE(SUM(capital_vigente),0) FROM mis_deudas WHERE estado = 'activo'")->fetchColumn();

$totalCapitalDisponible = $pdo->query("
    SELECT COALESCE(SUM(d.capital_vigente), 0) - COALESCE(
        (SELECT SUM(p.monto_prestado) FROM prestamos p
          WHERE p.fuente_fondos = 'apalancado'
          AND p.estado IN ('activo','moroso')), 0)
    FROM mis_deudas d WHERE d.estado = 'activo'
")->fetchColumn();

$totalInteresCobrarMes = $pdo->query("
    SELECT COALESCE(SUM((p.monto_prestado - p.abonos_capital) * p.tasa_interes / 100), 0)
    FROM prestamos p WHERE p.estado IN ('activo','moroso')
")->fetchColumn();

$totalInteresPagarMes = $pdo->query("
    SELECT COALESCE(SUM(d.capital_vigente * d.tasa_mensual / 100), 0)
    FROM mis_deudas d WHERE d.estado = 'activo'
")->fetchColumn();

$saldoVencidoTotal = 0;
try {
    $vencStmt = $pdo->query("SELECT monto_prestado, abonos_capital, tasa_interes, saldo_pendiente, estado, fecha_vencimiento, fecha_prestamo, plazo_meses, id, interes_vigente, fecha_ultimo_devengo FROM prestamos WHERE estado IN ('activo','moroso') AND COALESCE(saldo_pendiente, 0) > 0");
    foreach ($vencStmt->fetchAll() as $vp) {
        if (esMorosoCombinado($pdo, $vp, $config)) {
            $saldoVencidoTotal += round((floatval($vp['monto_prestado']) - floatval($vp['abonos_capital'])) * floatval($vp['tasa_interes']) / 100, 2);
        }
    }
} catch (Exception $e) { error_log("Error saldo vencido preview: " . $e->getMessage()); }

$totalPorCobrarHoy = 0; $totalInteresVigente = 0; $totalMoraPendiente = 0;
$graciaInteresCfg = intval($config['dias_gracia_interes'] ?? 0);
$intVigPorGrupo = [];
try {
    $cobStmt = $pdo->query("SELECT id, cliente_id, tasa_interes, fuente_fondos, saldo_pendiente, interes_vigente, fecha_ultimo_devengo, fecha_prestamo, estado, fecha_vencimiento, plazo_meses FROM prestamos WHERE estado IN ('activo','moroso') AND COALESCE(saldo_pendiente, 0) > 0");
    foreach ($cobStmt->fetchAll() as $cb) {
        $saldoCb = round(floatval($cb['saldo_pendiente'] ?? 0), 2);
        $baseDev = substr($cb['fecha_ultimo_devengo'] ?? $cb['fecha_prestamo'], 0, 10);
        $calcCb = interesCausadoPorMeses($saldoCb, $cb['tasa_interes'], $baseDev, hoyFecha(), $graciaInteresCfg);
        $intVig = round(floatval($cb['interes_vigente'] ?? 0) + $calcCb['total'], 2);
        $dCb = diasAtrasoPrestamo($pdo, $cb, $config);
        $moraCb = round(floatval($dCb['mora'] ?? 0), 2);
        $totalInteresVigente = round($totalInteresVigente + $intVig, 2);
        $totalMoraPendiente = round($totalMoraPendiente + $moraCb, 2);
        $totalPorCobrarHoy = round($totalPorCobrarHoy + $saldoCb + $intVig + $moraCb, 2);
        $gk = $cb['cliente_id'] . '|' . $cb['tasa_interes'] . '|' . ($cb['fuente_fondos'] ?? 'propio');
        $intVigPorGrupo[$gk] = round(($intVigPorGrupo[$gk] ?? 0) + $intVig, 2);
    }
} catch (Exception $e) { error_log("Error por cobrar preview: " . $e->getMessage()); }

$mesActual = date('Y-m');
$yaCobradoInteres = $pdo->prepare("SELECT COALESCE(SUM(interes_pagado),0) FROM pagos WHERE DATE_FORMAT(fecha_pago, '%Y-%m') = ?");
$yaCobradoInteres->execute([$mesActual]);
$yaCobradoInteres = $yaCobradoInteres->fetchColumn();
$cobradoMesAct = $pdo->prepare("SELECT COALESCE(SUM(monto_pagado),0) FROM pagos WHERE DATE_FORMAT(fecha_pago, '%Y-%m') = ?");
$cobradoMesAct->execute([$mesActual]);
$cobradoMesAct = $cobradoMesAct->fetchColumn();
$cobradoMesCap = round(floatval($cobradoMesAct) - floatval($yaCobradoInteres), 2);

$gananciaTotal = $totalInteresCobrado - $totalInteresPagado;
$interesCobradoApa = $pdo->query("SELECT COALESCE(SUM(pg.interes_pagado),0) FROM pagos pg INNER JOIN prestamos pr ON pg.prestamo_id = pr.id WHERE pr.fuente_fondos = 'apalancado'")->fetchColumn();
$utilidadApa = $interesCobradoApa - $totalInteresPagado;
$interesCobradoPropio = $pdo->query("SELECT COALESCE(SUM(pg.interes_pagado),0) FROM pagos pg INNER JOIN prestamos pr ON pg.prestamo_id = pr.id WHERE pr.fuente_fondos = 'propio'")->fetchColumn();
$capitalPropioInicial = floatval(getConfiguracion($pdo)['capital_propio_inicial'] ?? 0);
try { $capitalPropioDisponible = getCapitalPropioDisponible($pdo); } catch (Exception $e) { $capitalPropioDisponible = $capitalPropioInicial + $gananciaTotal - $propioActivoMonto; }
$capitalDisponibleGeneral = $capitalPropioDisponible + $totalCapitalDisponible;

$hoyIdx = hoyFecha();
$deudoresDashboard = $pdo->query("
    SELECT c.id AS cliente_id, c.nombre, c.apellido,
           COUNT(p.id) AS num_prestamos,
           COALESCE(SUM(p.monto_prestado - p.abonos_capital), 0) AS capital_vigente,
           p.tasa_interes,
           COALESCE(SUM((p.monto_prestado - p.abonos_capital) * p.tasa_interes / 100), 0) AS interes_mes,
           p.fuente_fondos,
           MIN(p.fecha_vencimiento) AS proximo_vencimiento
    FROM clientes c
    INNER JOIN prestamos p ON p.cliente_id = c.id
    WHERE p.estado IN ('activo','moroso')
    GROUP BY c.id, c.nombre, c.apellido, p.tasa_interes, p.fuente_fondos
    ORDER BY proximo_vencimiento ASC
")->fetchAll();

$mesAnterior = date('Y-m', strtotime('-1 month'));
$clientesMesAnt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE estado = 'activo' AND DATE_FORMAT(fecha_registro, '%Y-%m') = ?");
$clientesMesAnt->execute([$mesAnterior]);
$clientesMesAnt = $clientesMesAnt->fetchColumn();
$tendenciaClientes = $clientesMesAnt > 0 ? round((($totalClientes - $clientesMesAnt) / $clientesMesAnt) * 100) : 0;

$pagosPorMes = $pdo->query("
    SELECT DATE_FORMAT(fecha_pago, '%Y-%m') AS mes, SUM(monto_pagado) AS total
    FROM pagos WHERE fecha_pago >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes ORDER BY mes ASC
")->fetchAll();
$chartMeses = array_map(function($p) { return date('M', strtotime($p['mes'] . '-01')); }, $pagosPorMes);
$chartTotales = array_map(function($p) { return floatval($p['total']); }, $pagosPorMes);

$actividad = [];
$ultimosPagos = $pdo->query("
    SELECT pg.id AS pago_id, pg.monto_pagado, pg.fecha_pago, pg.fecha_registro, CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre, p.id AS prestamo_id
    FROM pagos pg INNER JOIN prestamos p ON pg.prestamo_id = p.id INNER JOIN clientes c ON p.cliente_id = c.id
    ORDER BY pg.fecha_registro DESC LIMIT 5
")->fetchAll();
foreach ($ultimosPagos as $pg) {
    $actividad[] = ['tipo' => 'pago', 'texto_cliente' => $pg['cliente_nombre'], 'texto_detalle' => 'pagó ' . formatMoney($pg['monto_pagado']), 'fecha' => $pg['fecha_registro'], 'color' => 'success'];
}
$ultimosPrestamos = $pdo->query("
    SELECT p.monto_prestado, p.fecha_registro, CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre
    FROM prestamos p INNER JOIN clientes c ON p.cliente_id = c.id ORDER BY p.fecha_registro DESC LIMIT 5
")->fetchAll();
foreach ($ultimosPrestamos as $p) {
    $actividad[] = ['tipo' => 'prestamo', 'texto_cliente' => $p['cliente_nombre'], 'texto_detalle' => '· Préstamo nuevo de ' . formatMoney($p['monto_prestado']), 'fecha' => $p['fecha_registro'], 'color' => 'primary'];
}
usort($actividad, function($a, $b) { return strtotime($b['fecha']) - strtotime($a['fecha']); });
$actividad = array_slice($actividad, 0, 8);

} catch (Exception $e) {
    error_log("Error en preview dashboard: " . $e->getMessage());
    $totalClientes = 0; $totalPrestamosActivos = 0; $totalMontoPrestado = 0; $totalSaldoPendiente = 0;
    $totalCobrado = 0; $totalInteresCobrado = 0; $totalInteresPagado = 0; $gananciaTotal = 0;
    $totalDebo = 0; $totalCapitalDisponible = 0; $totalInteresCobrarMes = 0; $totalInteresPagarMes = 0; $saldoVencidoTotal = 0; $totalMontoPrestadoHistorico = 0;
    $totalPorCobrarHoy = 0; $totalInteresVigente = 0; $totalMoraPendiente = 0; $intVigPorGrupo = [];
    $yaCobradoInteres = 0; $cobradoMesAct = 0; $cobradoMesCap = 0;
    $interesCobradoApa = 0; $utilidadApa = 0; $interesCobradoPropio = 0;
    $propioActivoMonto = 0; $capitalPropioDisponible = 0; $capitalDisponibleGeneral = 0; $capitalPropioInicial = 0;
    $prevDef = 0; $prevInd = 0; $prevDeudasN = 0; $tendenciaClientes = 0;
    $chartMeses = []; $chartTotales = []; $actividad = []; $ultimosPagos = []; $deudoresDashboard = [];
    $prestamosMorosos = 0; $prestamosPagados = 0; $morClientes = [];
}

function timeAgoPrev($datetime) {
    $diff = (new DateTime())->diff(new DateTime($datetime));
    if ($diff->d > 0) return 'hace ' . $diff->d . 'd';
    if ($diff->h > 0) return 'hace ' . $diff->h . 'h';
    if ($diff->i > 0) return 'hace ' . $diff->i . 'm';
    return 'ahora';
}
?>

<div class="alert alert-dismissible fade show" role="alert" style="background:#7c3aed; color:#fff; font-size:0.8rem;">
    <i class="bi bi-eye"></i> <strong>VISTA PREVIA dashboard</strong> con datos reales (solo lectura) ·
    <a href="index.php" style="color:#fff; text-decoration:underline;">Volver al dashboard real</a>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
</div>

<div class="page-header animate-in">
    <div class="page-header-row">
        <div>
            <h4 class="page-title"><i class="bi bi-grid-1x2"></i> Preview: vista única</h4>
        </div>
    </div>
</div>

<div class="dash-vista">

<!-- ═══ HÉROE (Opción B, fijo) ═══ -->
<div class="card animate-in mb-3" id="heroBand" style="border-left:4px solid var(--accent);">
    <div class="card-body p-0">
        <div class="stat-row">
            <div>
                <div class="stat-label">💰 Por Cobrar Hoy</div>
                <div class="stat-value hero-value" style="color:var(--accent);"><?= formatMoney($totalPorCobrarHoy) ?></div>
                <div style="font-size:0.7rem; color:var(--text-muted);">Cap <?= formatMoney($totalSaldoPendiente) ?> · Int <?= formatMoney($totalInteresVigente) ?></div>
            </div>
            <div>
                <div class="stat-label">Este Mes Cobrado</div>
                <div class="stat-value" style="color:var(--success);"><?= formatMoney($cobradoMesAct) ?></div>
                <div style="font-size:0.7rem; color:var(--text-muted);">Cap <?= formatMoney($cobradoMesCap) ?> · Int <?= formatMoney($yaCobradoInteres) ?></div>
            </div>
            <div>
                <div class="stat-label">Interés Vigente</div>
                <div class="stat-value" style="color:var(--warning, #d97706);"><?= formatMoney($totalInteresVigente) ?></div>
                <div style="font-size:0.7rem; color:var(--text-muted);">Exigible hoy en todos los préstamos</div>
            </div>
        </div>
    </div>
</div>

<div class="card dash-stats animate-in mb-3">
    <div class="card-body p-0">
        <div class="stat-row">
            <div>
                <div class="stat-label">Clientes Activos</div>
                <div class="stat-value"><?= $totalClientes ?></div>
                <div style="font-size:0.68rem; color:var(--text-muted);"><?= $tendenciaClientes >= 0 ? '+' : '' ?><?= $tendenciaClientes ?>% vs mes anterior</div>
            </div>
            <div>
                <div class="stat-label">Préstamos Activos</div>
                <div class="stat-value"><?= $totalPrestamosActivos ?></div>
                <div style="font-size:0.68rem; color:var(--text-muted);"><?= $prevDef ?> definidos · <?= $prevInd ?> indefinidos</div>
            </div>
            <div>
                <div class="stat-label">💵 Capital Disponible</div>
                <div class="stat-value" style="color:<?= $capitalDisponibleGeneral > 0 ? 'var(--success)' : '#dc2626' ?>;"><?= formatMoney($capitalDisponibleGeneral) ?></div>
                <div style="font-size:0.68rem; color:var(--text-muted);">Propio <?= formatMoney($capitalPropioDisponible) ?> · Acreedor <?= formatMoney($totalCapitalDisponible) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-2">
    <div class="col-lg-4">
        <div class="card dash-stats animate-in h-100 mb-0">
            <div class="card-header"><i class="bi bi-briefcase"></i> Mi Cartera</div>
            <div class="card-body p-0">
                <div class="stat-row">
                    <div style="border-left:3px solid var(--accent); padding-left:12px;">
                        <div class="stat-label">💵 Por Cobrar Hoy</div>
                        <div class="stat-value" style="font-size:1.1rem;"><?= formatMoney($totalPorCobrarHoy) ?></div>
                        <div style="font-size:0.68rem; color:var(--text-muted); line-height:1.45; margin-top:4px;">
                            Cap <?= formatMoney($totalSaldoPendiente) ?> · Int <?= formatMoney($totalInteresVigente) ?>
                        </div>
                    </div>
                    <div style="border-left:3px solid var(--accent); padding-left:12px;">
                        <div class="stat-label">💵 Total Cobrado</div>
                        <div class="stat-value" style="color:var(--success);"><?= formatMoney($totalCobrado) ?></div>
                        <div style="font-size:0.68rem; color:var(--text-muted); line-height:1.45; margin-top:4px;">
                            Este mes: <?= formatMoney($cobradoMesAct) ?> (Cap <?= formatMoney($cobradoMesCap) ?> · Int <?= formatMoney($yaCobradoInteres) ?>)
                        </div>
                    </div>
                    <div style="border-left:3px solid var(--accent); padding-left:12px;">
                        <div class="stat-label">📈 Interés a Cobrar / Mes</div>
                        <div class="stat-value" style="color:var(--accent);"><?= formatMoney($totalInteresCobrarMes) ?></div>
                        <div style="font-size:0.68rem; color:var(--text-muted); line-height:1.45; margin-top:4px;">
                            Vigente hoy: <?= formatMoney($totalInteresVigente) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card dash-stats animate-in h-100 mb-0">
            <div class="card-header"><i class="bi bi-bank"></i> Mis Deudas</div>
            <div class="card-body p-0">
                <div class="stat-row">
                    <div style="border-left:3px solid #dc2626; padding-left:12px;">
                        <div class="stat-label">💳 Total que Debo</div>
                        <div class="stat-value" style="color:#dc2626;"><?= formatMoney($totalDebo) ?></div>
                        <div style="font-size:0.68rem; color:var(--text-muted); line-height:1.45; margin-top:4px;">
                            <?= $prevDeudasN ?> deuda(s) activa(s)
                        </div>
                    </div>
                    <div style="border-left:3px solid #dc2626; padding-left:12px;">
                        <div class="stat-label">📉 Interés a Pagar / Mes</div>
                        <div class="stat-value" style="color:#dc2626;"><?= formatMoney($totalInteresPagarMes) ?></div>
                    </div>
                    <div style="border-left:3px solid #059669; padding-left:12px;">
                        <div class="stat-label">🏦 Crédito Libre del Acreedor</div>
                        <div class="stat-value" style="color:<?= $totalCapitalDisponible > 0 ? '#059669' : '#dc2626' ?>;"><?= formatMoney($totalCapitalDisponible) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card dash-stats animate-in h-100 mb-0">
            <div class="card-header"><i class="bi bi-graph-up"></i> Rentabilidad</div>
            <div class="card-body p-0">
                <div class="stat-row">
                    <div style="border-left:3px solid var(--success); padding-left:12px;">
                        <div class="stat-label">💰 Ganancia Total</div>
                        <div class="stat-value" style="color:<?= $gananciaTotal >= 0 ? 'var(--success)' : '#dc2626' ?>;"><?= formatMoney($gananciaTotal) ?></div>
                        <div style="font-size:0.68rem; color:var(--text-muted); line-height:1.45; margin-top:4px;">
                            Propio <?= formatMoney($interesCobradoPropio) ?> + Apalancamiento <?= formatMoney($utilidadApa) ?>
                        </div>
                    </div>
                    <div style="border-left:3px solid var(--success); padding-left:12px;">
                        <div class="stat-label">📊 Rendimiento</div>
                        <div class="stat-value" style="color:var(--success);"><?= $totalMontoPrestadoHistorico > 0 ? number_format(($gananciaTotal / $totalMontoPrestadoHistorico) * 100, 2) . '%' : '—' ?></div>
                        <div style="font-size:0.68rem; color:var(--text-muted); line-height:1.45; margin-top:4px;">
                            Ganancia <?= formatMoney($gananciaTotal) ?> sobre <?= formatMoney($totalMontoPrestadoHistorico) ?> prestado
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5 mb-3">
        <div class="card animate-in h-100">
            <div class="card-header"><i class="bi bi-activity"></i> Actividad Reciente</div>
            <div class="card-body cap-scroll">
                <?php if (empty($actividad)): ?>
                    <div class="empty-state" style="padding:24px 12px;">
                        <i class="bi bi-clock-history"></i>
                        <p>Sin actividad reciente</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($actividad as $act): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot <?= $act['color'] ?>"></div>
                                <div class="timeline-content"><strong><?= sanitize($act['texto_cliente']) ?></strong> <?= sanitize($act['texto_detalle']) ?></div>
                                <div class="timeline-date"><?= timeAgoPrev($act['fecha']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7 mb-3">
        <div class="card animate-in h-100">
            <div class="card-header"><i class="bi bi-clock-history"></i> Últimos Pagos</div>
            <div class="card-body p-0 cap-scroll">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Cliente</th><th class="num">Monto</th><th>Fecha</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimosPagos)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Sin pagos registrados</td></tr>
                        <?php else: ?>
                            <?php foreach ($ultimosPagos as $pg): ?>
                                <tr>
                                    <td><?= sanitize($pg['cliente_nombre']) ?></td>
                                    <td class="num"><?= formatMoney($pg['monto_pagado']) ?></td>
                                    <td style="font-size:0.78rem; color:var(--text-muted);"><?= date('d/m/Y', strtotime($pg['fecha_pago'])) ?></td>
                                    <td>
                                        <a href="<?= getBasePath() ?>pagos/recibo.php?id=<?= $pg['pago_id'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver recibo">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($deudoresDashboard)): ?>
<div class="row mt-3">
    <div class="col-12 mb-3">
        <div class="card animate-in">
            <div class="card-header"><i class="bi bi-people"></i> Estado Actual por Deudor</div>
            <div class="card-body p-0 cap-scroll-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>#</th><th>Deudor</th><th class="num">Capital Vigente</th><th class="num">Tasa</th><th class="num">Interés/Mes</th><th class="num">Total a Cobrar</th><th>Fuente</th><th>Próx. Vencimiento</th><th>Estatus</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deudoresDashboard as $i => $d): ?>
                                <?php
                                $estDeu = 'Vigente';
                                $hoyDeu = $hoyIdx ?? hoyFecha();
                                $graciaDeu = intval($diasGraciaCfg ?? 0);
                                $pvDeu = !empty($d['proximo_vencimiento']) ? substr($d['proximo_vencimiento'], 0, 10) : null;
                                if (isset($morClientes[$d['cliente_id']])) $estDeu = 'VENCIDO';
                                elseif ($pvDeu && $pvDeu < date('Y-m-d', strtotime('-' . $graciaDeu . ' days'))) $estDeu = 'VENCIDO';
                                elseif ($pvDeu && (strtotime($pvDeu) - strtotime($hoyDeu)) / 86400 <= 7) $estDeu = 'Por vencer';
                                $gkDeu = $d['cliente_id'] . '|' . $d['tasa_interes'] . '|' . ($d['fuente_fondos'] ?? 'propio');
                                $intDeu = round(floatval($d['capital_vigente']) + floatval($intVigPorGrupo[$gkDeu] ?? 0), 2);
                                ?>
                                <tr>
                                    <td style="color:var(--text-muted);"><?= $i + 1 ?></td>
                                    <td><strong><?= sanitize($d['nombre'] . ' ' . $d['apellido']) ?></strong></td>
                                    <td class="num"><?= formatMoney($d['capital_vigente']) ?></td>
                                    <td class="num"><?= $d['tasa_interes'] ?>%</td>
                                    <td class="num"><?= formatMoney($d['interes_mes']) ?></td>
                                    <td class="num fw-bold" style="color:var(--accent);"><?= formatMoney($intDeu) ?></td>
                                    <td><span class="badge badge-<?= ($d['fuente_fondos'] ?? 'propio') === 'apalancado' ? 'warning' : 'primary' ?>" style="font-size:0.7rem;"><?= ucfirst($d['fuente_fondos'] ?? 'Propio') ?></span></td>
                                    <td style="font-size:0.78rem; color:var(--text-muted);"><?= $d['proximo_vencimiento'] ? date('d/m/Y', strtotime($d['proximo_vencimiento'])) : 'Indefinido' ?></td>
                                    <td><span class="badge badge-<?= $estDeu === 'VENCIDO' ? 'danger' : ($estDeu === 'Por vencer' ? 'warning' : 'success') ?>"><?= $estDeu ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</div><!-- /dash-vista -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
