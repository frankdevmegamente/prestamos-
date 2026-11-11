<?php
$pageTitle = 'Nuevo Préstamo';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/breadcrumb.php';
requireLogin();

$pdo = getConnection();
$clientePreseleccionado = intval($_GET['cliente_id'] ?? 0);
$errors = [];

$capitalDisponible = 0;
try {
    $capitalDisponible = getCapitalApalancadoDisponible($pdo);
} catch (Exception $e) {
    $capitalDisponible = 0;
}

$capitalPropioInicial = floatval(getConfiguracion($pdo)['capital_propio_inicial'] ?? 0);
$capitalPropioDisponible = 0;
try {
    $capitalPropioDisponible = getCapitalPropioDisponible($pdo);
} catch (Exception $e) {
    $capitalPropioDisponible = $capitalPropioInicial;
}

try {
$clientesStmt = $pdo->query("SELECT id, nombre, apellido, cedula FROM clientes WHERE estado = 'activo' ORDER BY nombre");
$clientes = $clientesStmt->fetchAll();

$acreedoresStmt = $pdo->query("SELECT id, nombre FROM acreedores WHERE estado = 'activo' ORDER BY nombre");
$acreedores = $acreedoresStmt->fetchAll();
} catch (Exception $e) {
    $clientes = [];
    $acreedores = [];
}

$tasasAcreedor = [];
try {
    $tasStmt = $pdo->query("SELECT acreedor_id, MAX(tasa_mensual) AS tasa FROM mis_deudas WHERE estado = 'activo' GROUP BY acreedor_id");
    foreach ($tasStmt->fetchAll() as $row) $tasasAcreedor[(int)$row['acreedor_id']] = floatval($row['tasa']);
} catch (Exception $e) {
    $tasasAcreedor = [];
}

// Detalle de deudas activas por acreedor (visible al elegir apalancado).
$deudasAcreedor = [];
try {
    $deuStmt = $pdo->query("SELECT acreedor_id, concepto, monto_recibido, tasa_mensual, capital_vigente, interes_mensual, fecha_inicio, fecha_vencimiento FROM mis_deudas WHERE estado = 'activo' ORDER BY fecha_inicio");
    foreach ($deuStmt->fetchAll() as $row) {
        $deudasAcreedor[(int)$row['acreedor_id']][] = [
            'concepto' => $row['concepto'],
            'monto' => floatval($row['monto_recibido']),
            'tasa' => floatval($row['tasa_mensual']),
            'capital' => floatval($row['capital_vigente']),
            'interes' => floatval($row['interes_mensual']),
            'inicio' => $row['fecha_inicio'],
            'vencimiento' => $row['fecha_vencimiento'],
        ];
    }
} catch (Exception $e) {
    $deudasAcreedor = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $clienteId = intval($_POST['cliente_id'] ?? 0);
        $monto = floatval($_POST['monto_prestado'] ?? 0);
        $tasa = floatval($_POST['tasa_interes'] ?? 0);
        $plazo = intval($_POST['plazo'] ?? 0);
        $tipoPlazo = 'meses';
        $observaciones = trim($_POST['observaciones'] ?? '');
        $fuenteFondos = $_POST['fuente_fondos'] ?? 'propio';
        $acreedorId = !empty($_POST['acreedor_id']) ? intval($_POST['acreedor_id']) : null;
        $tasaAcreedor = !empty($_POST['tasa_acreedor']) ? floatval($_POST['tasa_acreedor']) : null;
        $esIndefinido = isset($_POST['es_indefinido']) && $_POST['es_indefinido'] === '1';
        if ($esIndefinido) $plazo = 0;

        if (!in_array($fuenteFondos, ['propio', 'apalancado'])) $fuenteFondos = 'propio';
        // Préstamo sin interés: se guarda tasa 0 y nunca devenga.
        // Permitido también en apalancados (con aviso en pantalla): la utilidad sale negativa.
        $sinInteres = isset($_POST['sin_interes']) && $_POST['sin_interes'] === '1';
        if ($sinInteres) $tasa = 0.0;
        // Se permite prestar con capital negativo (sobregiro): el capital
        // disponible es informativo y no bloquea el registro.
        if ($fuenteFondos === 'apalancado') {
            $capitalDisponible = getCapitalApalancadoDisponible($pdo);
            if (!$acreedorId) $errors[] = 'Seleccione un acreedor para fondos apalancados';
            if ($tasaAcreedor === null || $tasaAcreedor < 0) $errors[] = 'Ingrese la tasa que paga al acreedor';
        }
        if ($fuenteFondos === 'propio') {
            $capitalPropioDisponible = getCapitalPropioDisponible($pdo);
        }
        if ($clienteId <= 0) $errors[] = 'Seleccione un cliente';
        else {
            $chkCli = $pdo->prepare("SELECT id FROM clientes WHERE id = ? AND estado = 'activo'");
            $chkCli->execute([$clienteId]);
            if (!$chkCli->fetch()) $errors[] = 'El cliente seleccionado no existe o está inactivo';
        }
        if ($fuenteFondos === 'apalancado' && $acreedorId) {
            $chkAcr = $pdo->prepare("SELECT id FROM acreedores WHERE id = ? AND estado = 'activo'");
            $chkAcr->execute([$acreedorId]);
            if (!$chkAcr->fetch()) $errors[] = 'El acreedor seleccionado no existe o está inactivo';
        }
        if ($monto <= 0) $errors[] = 'El monto debe ser mayor a 0';
        if ($monto > 100000000) $errors[] = 'El monto excede el límite permitido';
        if ($tasa <= 0 && !$sinInteres) $errors[] = 'La tasa de interés debe ser mayor a 0';
        if ($tasa > 100) $errors[] = 'La tasa de interés no puede exceder 100% mensual';
        if (!$esIndefinido) {
            if ($plazo <= 0) $errors[] = 'El plazo debe ser mayor a 0';
            if ($plazo > 600) $errors[] = 'El plazo excede el límite permitido (600 meses)';
        }
        $fechaPrestamo = $_POST['fecha_prestamo'] ?? date('Y-m-d');
        $tsFecha = strtotime($fechaPrestamo);
        if ($tsFecha === false) $errors[] = 'Fecha de préstamo inválida';
        else {
            $fechaPrestamo = date('Y-m-d', $tsFecha);
            if ($fechaPrestamo > date('Y-m-d')) $errors[] = 'La fecha de préstamo no puede ser futura';
            if ($fechaPrestamo < '2000-01-01') $errors[] = 'La fecha de préstamo no es válida';
        }
        if (strlen($observaciones) > 1000) $errors[] = 'Las observaciones no pueden exceder 1000 caracteres';

        if (empty($errors)) {
            $interesMes = round($monto * ($tasa / 100), 2);
            $interesAcreedorMes = ($tasaAcreedor !== null) ? round($monto * ($tasaAcreedor / 100), 2) : 0;
            $utilidadMensual = round($interesMes - $interesAcreedorMes, 2);
            // Solo indefinidos con interés: 1er mes por adelantado desde el día 1.
            // Definidos devengan mes a mes; sin interés siempre 0.
            $interesInicial = ($esIndefinido && !$sinInteres) ? interesInicialPrestamo($monto, $tasa) : 0.0;

            if ($esIndefinido) {
                $numCuotas = 0; $cuotaMensual = 0;
                $totalPagar = $monto; $totalIntereses = 0;
                $fechaVencimiento = null;
            } else {
                $numCuotas = numCuotasPorPlazo($plazo, $tipoPlazo);
                $cuotaMensual = calcularCuota($monto, $tasa, $plazo, $tipoPlazo);
                $totalPagar = round($cuotaMensual * $numCuotas, 2);
                $totalIntereses = round($totalPagar - $monto, 2);
                $intervalos = ['dias' => ' days', 'semanas' => ' weeks', 'meses' => ' months', 'anios' => ' years'];
                $fechaVencimiento = date('Y-m-d', strtotime($fechaPrestamo . " +$plazo " . $intervalos[$tipoPlazo]));
            }

            try {
            $stmt = $pdo->prepare("INSERT INTO prestamos (cliente_id, monto_prestado, tasa_interes, plazo_meses, tipo_plazo, num_cuotas, cuota_mensual, total_intereses, total_pagar, saldo_pendiente, interes_vigente, fecha_ultimo_devengo, fecha_prestamo, fecha_vencimiento, observaciones, fuente_fondos, acreedor_id, tasa_acreedor, utilidad_mensual) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$clienteId, $monto, $tasa, $plazo, $tipoPlazo, $numCuotas, $cuotaMensual, $totalIntereses, $totalPagar, $monto, $interesInicial, $fechaPrestamo, $fechaPrestamo, $fechaVencimiento, $observaciones, $fuenteFondos, $acreedorId, $tasaAcreedor, $utilidadMensual]);
            audit($pdo, 'crear_prestamo', 'prestamos', $pdo->lastInsertId(), "cliente=$clienteId monto=$monto tasa=$tasa plazo=" . ($esIndefinido ? 'indefinido' : $plazo) . " fuente=$fuenteFondos" . ($sinInteres ? ' sin_interes' : ($interesInicial > 0 ? " interes_inicial=$interesInicial" : '')));
            flash('success', 'Préstamo registrado exitosamente');
            redirect('index.php');
            } catch (Exception $e) {
                error_log("Error creando préstamo: " . $e->getMessage());
                $errors[] = 'Error al registrar el préstamo. Verifique los datos e intente de nuevo.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header animate-in">
    <div class="page-header-row">
        <div>
            <?php renderBreadcrumb(); ?>
            <h4 class="page-title"><i class="bi bi-cash-stack"></i> Nuevo Préstamo</h4>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger animate-in">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= sanitize($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="wizard-steps animate-in">
    <div class="wizard-step active" id="wizStep1">
        <div class="step-number">1</div>
        <span class="step-label">Cliente</span>
    </div>
    <div class="wizard-connector" id="wizConn1"></div>
    <div class="wizard-step" id="wizStep2">
        <div class="step-number">2</div>
        <span class="step-label">Datos del Préstamo</span>
    </div>
    <div class="wizard-connector" id="wizConn2"></div>
    <div class="wizard-step" id="wizStep3">
        <div class="step-number">3</div>
        <span class="step-label">Confirmar</span>
    </div>
</div>

<form method="POST" data-loading id="formPrestamo">
<?= csrfField() ?>
<div class="wizard-panel active" id="panel1">
    <div class="card animate-in">
        <div class="card-header"><i class="bi bi-person"></i> Seleccionar Cliente</div>
        <div class="card-body">
            <div class="floating-group">
                <select name="cliente_id" id="clienteSelect" required>
                    <option value="">Seleccione un cliente...</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $clientePreseleccionado == $c['id'] ? 'selected' : '' ?>>
                            <?= sanitize($c['nombre'] . ' ' . $c['apellido'] . ' - ' . $c['cedula']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label>Cliente *</label>
            </div>
            <div class="text-end mt-3">
                <button type="button" class="btn btn-primary" onclick="wizardNext(2)">Siguiente <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
    </div>
</div>

<div class="wizard-panel" id="panel2">
    <div class="row">
        <div class="col-lg-7">
            <div class="card animate-in">
                <div class="card-header"><i class="bi bi-calculator"></i> Datos del Préstamo</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="floating-group">
                                <input type="number" name="monto_prestado" id="montoPrestado" placeholder=" " step="0.01" min="0.01" value="<?= sanitize($_POST['monto_prestado'] ?? '') ?>" required>
                                <label>Monto Prestado *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="floating-group">
                                <input type="text" name="tasa_interes" id="tasaInteres" placeholder=" " inputmode="decimal" value="<?= sanitize($_POST['tasa_interes'] ?? '') ?>" required>
                                <label>Tasa Mensual (%) *</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="floating-group">
                                <input type="number" name="plazo" id="plazoInput" placeholder=" " min="1" value="<?= sanitize($_POST['plazo'] ?? '') ?>">
                                <label>Plazo (meses)</label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="es_indefinido" id="esIndefinido" value="1" <?= isset($_POST['es_indefinido']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="esIndefinido" style="font-size:0.82rem;"><i class="bi bi-infinity"></i> Sin plazo (indefinido)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="floating-group">
                                <input type="date" name="fecha_prestamo" id="fechaPrestamo" value="<?= sanitize($_POST['fecha_prestamo'] ?? date('Y-m-d')) ?>">
                                <label>Fecha de Inicio</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="floating-group">
                                <select name="fuente_fondos" id="fuenteFondos">
                                    <option value="propio" <?= ($_POST['fuente_fondos'] ?? 'propio') === 'propio' ? 'selected' : '' ?>>Propio</option>
                                    <option value="apalancado" <?= ($_POST['fuente_fondos'] ?? '') === 'apalancado' ? 'selected' : '' ?>>Apalancado</option>
                                </select>
                                <label>Fuente de Fondos</label>
                            </div>
                        </div>
                        <div class="col-md-6" id="acreedorGroup" style="display:none;">
                            <div class="floating-group">
                                <select name="acreedor_id" id="acreedorSelect">
                                    <option value="">Seleccione acreedor...</option>
                                    <?php foreach ($acreedores as $a): ?>
                                        <option value="<?= $a['id'] ?>" <?= ($_POST['acreedor_id'] ?? '') == $a['id'] ? 'selected' : '' ?>><?= sanitize($a['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label>Acreedor</label>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="capitalGeneralGroup">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center" style="padding:10px 14px; border:1px solid var(--border-strong); border-radius:8px; background:var(--bg); margin-bottom:16px;">
                                <span style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted);">Capital Disponible General</span>
                                <span id="capitalGeneralDisplay" style="font-size:0.85rem; font-weight:600; color:var(--text);">
                                    Tuyo MXN <?= number_format($capitalPropioDisponible, 2, '.', ',') ?> · Acreedor MXN <?= number_format($capitalDisponible, 2, '.', ',') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="tasaAcreedorGroup" style="display:none;">
                        <div class="col-12">
                            <div class="floating-group">
                                <input type="number" name="tasa_acreedor" id="tasaAcreedor" placeholder=" " step="0.01" min="0" value="<?= sanitize($_POST['tasa_acreedor'] ?? '') ?>">
                                <label>Tasa que Pago (%)</label>
                            </div>
                            <small id="deudaTasaHint" class="text-muted" style="display:none; font-size:0.72rem;"></small>
                            <div id="tasaDiffWarning" style="display:none; font-size:0.72rem; margin-top:4px; color:#dc2626;"></div>
                        </div>
                    </div>
                    <div class="row" id="acreedorDetalleGroup" style="display:none;">
                        <div class="col-12">
                            <div style="padding:10px 14px; border:1px solid var(--border-strong); border-radius:8px; background:var(--bg); margin-bottom:16px;">
                                <div style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); margin-bottom:6px;"><i class="bi bi-bank"></i> Mi deuda con este acreedor</div>
                                <div id="acreedorDetalleBody" style="font-size:0.8rem;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="capitalAlertGroup" style="display:none;">
                        <div class="col-12">
                            <div class="alert alert-warning py-2 px-3 mb-0 mt-3" style="font-size:0.8rem;">
                                <i class="bi bi-exclamation-triangle"></i> <span id="capitalAlertText"></span>
                            </div>
                        </div>
                    </div>


                    <div class="floating-group">
                        <textarea name="observaciones" id="observaciones" placeholder=" " rows="2"><?= sanitize($_POST['observaciones'] ?? '') ?></textarea>
                        <label>Observaciones</label>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" class="btn btn-outline-secondary" onclick="wizardNext(1)"><i class="bi bi-arrow-left"></i> Anterior</button>
                        <button type="button" class="btn btn-primary" onclick="wizardNext(3)">Siguiente <i class="bi bi-arrow-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card animate-in">
                <div class="card-header"><i class="bi bi-info-circle"></i> Resumen</div>
                <div class="card-body">
                    <ul class="info-list mb-3">
                        <li><span class="info-label">Monto</span><span class="info-value" id="resumenMonto">MXN 0.00</span></li>
                        <li id="resumenCapitalRow" style="display:none;"><span class="info-label">Capital Disponible</span><span class="info-value fw-bold" id="resumenCapital">MXN 0.00</span></li>
                        <li id="resumenExcedenteRow" style="display:none;"><span class="info-label">Excedente</span><span class="info-value fw-bold" id="resumenExcedente" style="color:var(--success);">MXN 0.00</span></li>
                        <li><span class="info-label">Tasa mensual</span><span class="info-value" id="resumenTasa">0%</span></li>
                        <li><span class="info-label">Plazo</span><span class="info-value" id="resumenPlazo">0 meses</span></li>
                        <li><span class="info-label">Cuota</span><span class="info-value fw-bold" id="resumenCuota" style="color:var(--accent);">MXN 0.00</span></li>
                        <li><span class="info-label">Total intereses</span><span class="info-value" id="resumenIntereses" style="color:var(--danger);">MXN 0.00</span></li>
                        <li><span class="info-label">Total a pagar</span><span class="info-value fw-bold" id="resumenTotal">MXN 0.00</span></li>
                        <li><span class="info-label">Nº cuotas</span><span class="info-value" id="resumenCuotas">0</span></li>
                        <li><span class="info-label">Utilidad mensual</span><span class="info-value fw-bold" id="resumenUtilidad" style="color:var(--success);">MXN 0.00</span></li>
                        <li id="resumenCobraRow" style="display:none;"><span class="info-label">— Interés a cobrar / mes</span><span class="info-value fw-bold" id="resumenCobra" style="color:var(--success);">MXN 0.00</span></li>
                        <li id="resumenPagaRow" style="display:none;"><span class="info-label">— Interés a pagar / mes</span><span class="info-value fw-bold" id="resumenPaga" style="color:var(--danger);">MXN 0.00</span></li>
                    </ul>
                    <div id="amortizacionPreview" style="display:none;">
                        <h6 class="mb-2" style="font-size:0.82rem; color:var(--text-muted);">Tabla de Amortización</h6>
                        <div style="max-height: 240px; overflow-y: auto;">
                            <table class="table table-sm amortizacion-table">
                                <thead><tr><th>#</th><th>Fecha Pago</th><th class="num">Capital</th><th class="num">Interés</th><th class="num">Saldo</th></tr></thead>
                                <tbody id="amortizacionBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="wizard-panel" id="panel3">
    <div class="card animate-in">
        <div class="card-header"><i class="bi bi-check-circle"></i> Confirmar Préstamo</div>
        <div class="card-body py-4">
            <div class="p-3 mb-4 text-center" style="background:var(--bg); border:1px solid var(--border-strong); border-radius:8px;">
                <div style="font-size:0.7rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Total del préstamo</div>
                <div class="fw-bold" id="confTotalHero" style="font-size:1.6rem; color:var(--accent);">—</div>
                <div style="font-size:0.78rem; color:var(--text-muted);" id="confDesglose">—</div>
                <div class="mt-3" id="cobrarInteresRow" style="display:none !important;">
                    <div class="btn-group" role="group" aria-label="Modalidad de interés">
                        <button type="button" class="btn btn-sm btn-primary" id="btnConInteres" style="min-width:110px;"><i class="bi bi-percent"></i> Con interés</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSinInteres" style="min-width:110px;">Sin interés</button>
                    </div>
                </div>
                <div class="form-text" id="sinInteresAviso" style="display:none; color:var(--danger); font-size:0.75rem;">Sin interés: el cliente devuelve solo el capital (tasa 0%).</div>
                <div class="form-text" id="apalancadoSinInteresAviso" style="display:none; color:var(--danger); font-size:0.75rem;"><i class="bi bi-exclamation-triangle"></i> Atención: préstamo apalancado sin interés — seguirás pagando al acreedor (utilidad negativa).</div>
            </div>
            <input type="hidden" name="sin_interes" id="sinInteresInput" value="0">
            <div class="row mb-4">
                <div class="col-md-6">
                    <ul class="info-list">
                        <li><span class="info-label">Cliente</span><span class="info-value" id="confCliente">—</span></li>
                        <li><span class="info-label">Monto</span><span class="info-value" id="confMonto">—</span></li>
                        <li><span class="info-label">Tasa Mensual</span><span class="info-value" id="confTasa">—</span></li>
                        <li><span class="info-label">Plazo</span><span class="info-value" id="confPlazo">—</span></li>
                        <li><span class="info-label">Fecha de Inicio</span><span class="info-value" id="confFecha">—</span></li>
                        <li><span class="info-label">Fuente de Fondos</span><span class="info-value" id="confFondos">—</span></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="info-list">
                        <li><span class="info-label">Cuota Mensual</span><span class="info-value fw-bold" id="confCuota" style="color:var(--accent);">—</span></li>
                        <li><span class="info-label">Total Intereses</span><span class="info-value" id="confIntereses" style="color:var(--danger);">—</span></li>
                        <li><span class="info-label">Total a Pagar</span><span class="info-value fw-bold" id="confTotal">—</span></li>
                        <li><span class="info-label">N° de Cuotas</span><span class="info-value" id="confCuotas">—</span></li>
                        <li><span class="info-label">Utilidad / Mes</span><span class="info-value fw-bold" id="confUtilidad" style="color:var(--success);">—</span></li>
                        <li id="confCobraRow" style="display:none;"><span class="info-label">— Interés a Cobrar / Mes</span><span class="info-value fw-bold" id="confCobra" style="color:var(--success);">—</span></li>
                        <li id="confPagaRow" style="display:none;"><span class="info-label">— Interés a Pagar / Mes</span><span class="info-value fw-bold" id="confPaga" style="color:var(--danger);">—</span></li>
                        <li><span class="info-label">Observaciones</span><span class="info-value" id="confObs">—</span></li>
                    </ul>
                </div>
            </div>
            <div class="text-center">
                <i class="bi bi-check-circle" style="font-size:2rem; color:var(--success); opacity:0.3;"></i>
                <h5 class="mt-2" style="font-size:0.95rem;">¿Registrar este préstamo?</h5>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button type="button" class="btn btn-outline-secondary" onclick="wizardNext(2)"><i class="bi bi-arrow-left"></i> Volver</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Confirmar y Registrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
</form>

<script>
function wizardNext(step) {
    document.querySelectorAll('.wizard-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel' + step).classList.add('active');

    document.querySelectorAll('.wizard-step').forEach(s => { s.classList.remove('active'); s.classList.remove('completed'); });
    for (let i = 1; i <= 3; i++) {
        const el = document.getElementById('wizStep' + i);
        if (i < step) el.classList.add('completed');
        if (i === step) el.classList.add('active');
    }

    document.getElementById('wizConn1').classList.toggle('completed', step > 1);
    document.getElementById('wizConn2').classList.toggle('completed', step > 2);
    window.scrollTo({ top: 0, behavior: 'smooth' });

    if (step === 3) {
        llenarConfirmacion();
    }
}

function pintarModoInteres() {
    var off = document.getElementById('sinInteresInput').value === '1';
    document.getElementById('btnConInteres').className = 'btn btn-sm ' + (off ? 'btn-outline-secondary' : 'btn-primary');
    document.getElementById('btnSinInteres').className = 'btn btn-sm ' + (off ? 'btn-danger' : 'btn-outline-secondary');
}

function llenarConfirmacion() {
        var sel = document.getElementById('clienteSelect');
        document.getElementById('confCliente').textContent = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '—';
        document.getElementById('confMonto').textContent = document.getElementById('resumenMonto').textContent;
        document.getElementById('confTasa').textContent = document.getElementById('resumenTasa').textContent;
        document.getElementById('confPlazo').textContent = document.getElementById('resumenPlazo').textContent;
        document.getElementById('confFecha').textContent = document.getElementById('fechaPrestamo').value;
        document.getElementById('confCuota').textContent = document.getElementById('resumenCuota').textContent;
        document.getElementById('confIntereses').textContent = document.getElementById('resumenIntereses').textContent;
        document.getElementById('confTotal').textContent = document.getElementById('resumenTotal').textContent;
        document.getElementById('confCuotas').textContent = document.getElementById('resumenCuotas').textContent;
        document.getElementById('confUtilidad').textContent = document.getElementById('resumenUtilidad').textContent;
        document.getElementById('confCobra').textContent = document.getElementById('resumenCobra').textContent;
        document.getElementById('confPaga').textContent = document.getElementById('resumenPaga').textContent;
        const esApC = document.getElementById('fuenteFondos').value === 'apalancado';
        document.getElementById('confCobraRow').style.display = esApC ? '' : 'none';
        document.getElementById('confPagaRow').style.display = esApC ? '' : 'none';
        document.getElementById('confObs').textContent = document.getElementById('observaciones').value || '(sin observaciones)';

        var fuente = document.getElementById('fuenteFondos');
        var fondosText = fuente.options[fuente.selectedIndex].text;
        if (fuente.value === 'apalancado') {
            var acreedor = document.getElementById('acreedorSelect');
            fondosText += ' - ' + (acreedor.options[acreedor.selectedIndex] ? acreedor.options[acreedor.selectedIndex].text : '');
        }
        document.getElementById('confFondos').textContent = fondosText;

        // Héroe del total con interés cargado + modalidad (definidos e indefinidos).
        // Indefinidos con interés: 1er mes por adelantado desde el día 1.
        var esInd = document.getElementById('esIndefinido').checked;
        // Ocultar botones Con/Sin interés en paso 3 según requerimiento
        document.getElementById('cobrarInteresRow').style.display = 'none';
        pintarModoInteres();
        var off = document.getElementById('sinInteresInput').value === '1';
        var apalSin = off && document.getElementById('fuenteFondos').value === 'apalancado';
        document.getElementById('apalancadoSinInteresAviso').style.display = apalSin ? '' : 'none';
        document.getElementById('sinInteresInput').value = off ? '1' : '0';
        document.getElementById('sinInteresAviso').style.display = off ? '' : 'none';
        if (esInd && !off) {
            var mInd = parseFloat(document.getElementById('montoPrestado').value) || 0;
            var tInd = parseFloat(document.getElementById('tasaInteres').value) || 0;
            var iniInd = Math.round(mInd * (tInd / 100) * 100) / 100;
            document.getElementById('confTotalHero').textContent = formatMoney(mInd + iniInd);
            document.getElementById('confDesglose').textContent = formatMoney(mInd) + ' + ' + formatMoney(iniInd) + ' de intereses';
        } else {
            document.getElementById('confTotalHero').textContent = document.getElementById('confTotal').textContent;
            document.getElementById('confDesglose').textContent = document.getElementById('confMonto').textContent + ' + ' + document.getElementById('confIntereses').textContent + ' de intereses';
        }
}

document.addEventListener('DOMContentLoaded', function() {
    const montoInput = document.getElementById('montoPrestado');
    const montoLabel = montoInput.closest('.floating-group').querySelector('label');
    const tasaInput = document.getElementById('tasaInteres');
    const plazoInput = document.getElementById('plazoInput');
    const fuenteSelect = document.getElementById('fuenteFondos');
    const tasaAcreedorInput = document.getElementById('tasaAcreedor');
    const acreedorSelect = document.getElementById('acreedorSelect');
    const capitalAcreedorDisponible = <?= $capitalDisponible ?>;
    const capitalPropioDisponible = <?= $capitalPropioDisponible ?>;
    const basePath = '<?= getBasePath() ?>';
    const tasasAcreedor = <?= json_encode($tasasAcreedor) ?>;
    const deudasAcreedor = <?= json_encode($deudasAcreedor) ?>;

    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = (s === null || s === undefined) ? '' : String(s); return d.innerHTML; }

    function fmtFechaCorta(f) { if (!f) return '—'; const p = String(f).split('-'); return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : f; }

    function pintarDetalleAcreedor() {
        const group = document.getElementById('acreedorDetalleGroup');
        const body = document.getElementById('acreedorDetalleBody');
        if (!group || !body) return;
        const esApalancado = fuenteSelect.value === 'apalancado';
        const aId = parseInt(acreedorSelect.value) || 0;
        if (!esApalancado || !aId) { group.style.display = 'none'; return; }
        const deudas = deudasAcreedor[aId] || [];
        if (deudas.length === 0) {
            body.innerHTML = '<span style="color:var(--text-muted);">Sin deudas activas con este acreedor.</span>';
            group.style.display = '';
            return;
        }
        let html = '';
        let totCap = 0, totInt = 0;
        deudas.forEach(function(d, i) {
            totCap += d.capital; totInt += d.interes;
            html += '<div style="display:flex; justify-content:space-between; gap:8px; padding:3px 0;' + (i > 0 ? ' border-top:1px dashed var(--border-strong);' : '') + '">'
                + '<span>' + escapeHtml(d.concepto || 'Deuda') + ' · ' + d.tasa + '% <span style="color:var(--text-muted);">(' + fmtFechaCorta(d.inicio) + (d.vencimiento ? ' → ' + fmtFechaCorta(d.vencimiento) : '') + ')</span></span>'
                + '<span class="num" style="white-space:nowrap;">Cap ' + formatMoney(d.capital) + ' · Int ' + formatMoney(d.interes) + '</span></div>';
        });
        if (deudas.length > 1) html += '<div style="display:flex; justify-content:space-between; font-weight:700; padding-top:4px;"><span>Total (' + deudas.length + ')</span><span class="num">Cap ' + formatMoney(totCap) + ' · Int ' + formatMoney(totInt) + '</span></div>';
        body.innerHTML = html;
        group.style.display = '';
    }

    function formatMoney2(n) {
        return 'MXN ' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function capitalPorFuente() {
        return fuenteSelect.value === 'apalancado' ? capitalAcreedorDisponible : capitalPropioDisponible;
    }

    // Los dos movimientos por separado (préstamo vs acreedor).
    function pintarMovimientos(cobra, paga, esApalancado) {
        const cobraRow = document.getElementById('resumenCobraRow');
        const pagaRow = document.getElementById('resumenPagaRow');
        if (cobraRow) cobraRow.style.display = esApalancado ? '' : 'none';
        if (pagaRow) pagaRow.style.display = esApalancado ? '' : 'none';
        document.getElementById('resumenCobra').textContent = formatMoney(cobra);
        document.getElementById('resumenPaga').textContent = formatMoney(paga);
    }

    function actualizarAlertCapital(monto) {
        const alertGroup = document.getElementById('capitalAlertGroup');
        const alertText = document.getElementById('capitalAlertText');
        const esApa = fuenteSelect.value === 'apalancado';
        const cap = esApa ? capitalAcreedorDisponible : capitalPropioDisponible;
        if (monto > cap) {
            alertGroup.style.display = '';
            alertText.innerHTML = esApa
                ? 'Atención: el monto excede tu capital de acreedor disponible (' + formatMoney2(cap) + '). Se registrará con capital negativo (' + formatMoney2(cap - monto) + ').'
                : 'Atención: el monto excede tu capital propio disponible (' + formatMoney2(cap) + '). Se registrará con capital negativo (' + formatMoney2(cap - monto) + ').';
            return;
        }
        alertGroup.style.display = 'none';
    }

    function actualizarReferenciaAcreedor() {
        const hint = document.getElementById('deudaTasaHint');
        const warn = document.getElementById('tasaDiffWarning');
        if (!hint || !warn) return;
        const aId = parseInt(acreedorSelect.value) || 0;
        const tasaDeuda = tasasAcreedor[aId];
        if (tasaDeuda !== undefined) {
            hint.textContent = 'Tu deuda activa con este acreedor: ' + tasaDeuda + '% mensual';
            hint.style.display = '';
            // Auto-rellena la tasa si el campo está vacío (mejora UX, evita error "Ingrese la tasa")
            if (tasaAcreedorInput && (tasaAcreedorInput.value === '' || tasaAcreedorInput.value === '0')) {
                tasaAcreedorInput.value = tasaDeuda;
            }
            const t = parseFloat(tasaAcreedorInput.value) || 0;
            if (t > 0 && Math.abs(t - tasaDeuda) > 0.001) {
                warn.textContent = '⚠️ Difiere de tu deuda activa (' + tasaDeuda + '%). Tu utilidad real será otra.';
                warn.style.display = '';
            } else {
                warn.style.display = 'none';
            }
        } else {
            hint.style.display = 'none';
            warn.style.display = 'none';
        }
        pintarDetalleAcreedor();
    }

    function toggleAcreedor() {
        const esApalancado = fuenteSelect.value === 'apalancado';
        document.getElementById('acreedorGroup').style.display = esApalancado ? '' : 'none';
        document.getElementById('tasaAcreedorGroup').style.display = esApalancado ? '' : 'none';
        const aSel = document.getElementById('acreedorSelect');
        const tAcr = document.getElementById('tasaAcreedor');
        // Sin 'required' dinámico: los paneles del wizard se ocultan con
        // display:none y un required oculto/vacío bloquea el submit con
        // "An invalid form control is not focusable". Valida el servidor.
        if (aSel) aSel.disabled = false;
        if (tAcr) tAcr.disabled = false;
        montoLabel.textContent = 'Monto Prestado *';
        if (esApalancado) actualizarReferenciaAcreedor();
    }

    fuenteSelect.addEventListener('change', function() { toggleAcreedor(); calcular(); });
    if (acreedorSelect) acreedorSelect.addEventListener('change', function() { actualizarReferenciaAcreedor(); calcular(); });
    if (tasaAcreedorInput) tasaAcreedorInput.addEventListener('input', actualizarReferenciaAcreedor);

    function calcular() {
        const monto = parseFloat(montoInput.value) || 0;
        const tasa = parseFloat(tasaInput.value) || 0;
        // Modalidad de interés: la única fuente de verdad es el hidden sinInteresInput
        const sinInt = document.getElementById('sinInteresInput').value === '1';
        const cobrarInteres = !sinInt;
        const tasaCalc = cobrarInteres ? tasa : 0;
        const indefinido = document.getElementById('esIndefinido').checked;
        const plazo = indefinido ? 0 : (parseInt(plazoInput.value) || 0);
        const tipoPlazo = 'meses';
        const cfg = TIPO_PLAZO[tipoPlazo];
        const tasaAcreedor = parseFloat(tasaAcreedorInput ? tasaAcreedorInput.value : 0) || 0;
        const esApalancado = fuenteSelect.value === 'apalancado';

        document.getElementById('resumenMonto').textContent = formatMoney(monto);
        document.getElementById('resumenTasa').textContent = cobrarInteres ? tasa + '% mensual' : '0% (sin interés)';
        document.getElementById('resumenPlazo').textContent = indefinido ? 'Indefinido' : (plazo > 0 ? plazo + ' meses' : 'Indefinido');

        const capitalRow = document.getElementById('resumenCapitalRow');
        const excedenteRow = document.getElementById('resumenExcedenteRow');
        const capDisponible = capitalPorFuente();
        capitalRow.style.display = '';
        document.getElementById('resumenCapital').textContent = formatMoney(capDisponible);
        if (monto > 0) {
            const excedente = capDisponible - monto;
            excedenteRow.style.display = '';
            const excedenteEl = document.getElementById('resumenExcedente');
            excedenteEl.textContent = formatMoney(excedente);
            excedenteEl.style.color = excedente >= 0 ? 'var(--success)' : 'var(--danger)';
        } else {
            excedenteRow.style.display = 'none';
        }
        actualizarAlertCapital(monto);

        if (monto > 0 && plazo > 0) {
            const fechaInicio = document.getElementById('fechaPrestamo').value;
            const resultado = generarTablaAmortizacion(monto, tasaCalc, plazo, tipoPlazo, fechaInicio);
            document.getElementById('resumenCuota').textContent = formatMoney(resultado.cuotaPeriodo);
            document.getElementById('resumenIntereses').textContent = formatMoney(resultado.totalIntereses);
            document.getElementById('resumenTotal').textContent = formatMoney(resultado.totalPagar);
            document.getElementById('resumenCuotas').textContent = resultado.numCuotas + ' cuota(s)';

            const tbody = document.getElementById('amortizacionBody');
            tbody.innerHTML = '';
            resultado.tabla.forEach(row => {
                tbody.innerHTML += '<tr><td>' + row.cuota + '</td><td>' + row.fecha + '</td><td class="num">' + formatMoney(row.capital) + '</td><td class="num">' + formatMoney(row.interes) + '</td><td class="num">' + formatMoney(row.saldo) + '</td></tr>';
            });
            document.getElementById('amortizacionPreview').style.display = 'block';

            const interesMes = monto * (tasaCalc / 100);
            const interesAcreedor = monto * (tasaAcreedor / 100);
            const utilidad = interesMes - interesAcreedor;
            const utilidadEl = document.getElementById('resumenUtilidad');
            utilidadEl.textContent = formatMoney(utilidad);
            utilidadEl.style.color = utilidad >= 0 ? 'var(--success)' : 'var(--danger)';
            pintarMovimientos(interesMes, interesAcreedor, esApalancado);
        } else {
            document.getElementById('resumenCuota').textContent = indefinido ? 'Interés mensual' : formatMoney(0);
            document.getElementById('resumenIntereses').textContent = indefinido ? 'Según saldo' : formatMoney(0);
            document.getElementById('resumenTotal').textContent = indefinido ? formatMoney(monto) : formatMoney(0);
            document.getElementById('resumenCuotas').textContent = indefinido ? '∞' : '0';
            document.getElementById('amortizacionPreview').style.display = 'none';
            const interesMesInd = monto * (tasaCalc / 100);
            const interesAcreedorInd = monto * (tasaAcreedor / 100);
            const utilidadInd = interesMesInd - interesAcreedorInd;
            const utilidadElInd = document.getElementById('resumenUtilidad');
            utilidadElInd.textContent = formatMoney(utilidadInd);
            utilidadElInd.style.color = utilidadInd >= 0 ? 'var(--success)' : 'var(--danger)';
            pintarMovimientos(interesMesInd, interesAcreedorInd, esApalancado);
        }
    }

    function toggleIndefinido() {
        const ind = document.getElementById('esIndefinido').checked;
        plazoInput.disabled = ind;
        if (ind) plazoInput.value = '';
        calcular();
    }
    document.getElementById('esIndefinido').addEventListener('change', toggleIndefinido);

    function syncSinInteres() {
        const off = document.getElementById('sinInteresInput').value === '1';
        document.getElementById('sinInteresAviso').style.display = off ? '' : 'none';
        const apalSin = off && fuenteSelect.value === 'apalancado';
        document.getElementById('apalancadoSinInteresAviso').style.display = apalSin ? '' : 'none';
    }
    function setModoInteres(con) {
        document.getElementById('sinInteresInput').value = con ? '0' : '1';
        pintarModoInteres();
        syncSinInteres();
        calcular();
        if (document.getElementById('panel3').classList.contains('active')) llenarConfirmacion();
    }
    document.getElementById('btnConInteres').addEventListener('click', function() { setModoInteres(true); });
    document.getElementById('btnSinInteres').addEventListener('click', function() { setModoInteres(false); });

    montoInput.addEventListener('input', calcular);
    tasaInput.addEventListener('input', calcular);
    plazoInput.addEventListener('input', calcular);
    document.getElementById('fechaPrestamo').addEventListener('input', calcular);
    if (tasaAcreedorInput) tasaAcreedorInput.addEventListener('input', calcular);

    toggleAcreedor();
    toggleIndefinido();
    pintarModoInteres();
    // Mantener ocultos los botones Con/Sin interés en paso 3
    var row = document.getElementById('cobrarInteresRow');
    if (row) { row.style.display = 'none'; }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
