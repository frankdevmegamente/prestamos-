<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/breadcrumb.php';

if (basename($_SERVER['PHP_SELF']) !== 'login.php' && basename($_SERVER['PHP_SELF']) !== 'instalar.php') {
    requireLogin();
}

$maintenanceActive = false;
$maintenanceMessage = '';
if (basename($_SERVER['PHP_SELF']) !== 'login.php' && basename($_SERVER['PHP_SELF']) !== 'instalar.php') {
    try {
        $pdoMaint = getConnection();
        $maintenanceActive = isMaintenanceMode($pdoMaint);
        if ($maintenanceActive) $maintenanceMessage = getMaintenanceMessage($pdoMaint);
    } catch (Exception $e) {
        $maintenanceActive = false;
    }
    $scriptPath = str_replace('\\', '/', $_SERVER['PHP_SELF']);
    $isConfigPage = (strpos($scriptPath, '/configuracion/') !== false);
    // Escape hatch: any logged-in user can reach configuracion during maintenance to disable it.
    if ($maintenanceActive && !isAdmin() && !$isConfigPage) {
        http_response_code(503);
        $bp = defined('BASE_PATH') ? BASE_PATH : '/';
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Mantenimiento - Sistema de Préstamos</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">';
        echo '<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">';
        echo '<link href="' . $bp . 'assets/css/style.css?v=5" rel="stylesheet"></head><body>';
        echo '<div class="d-flex align-items-center justify-content-center" style="min-height:100vh;"><div class="text-center" style="max-width:480px;padding:40px;">';
        echo '<i class="bi bi-tools" style="font-size:3rem;color:var(--accent);"></i>';
        echo '<h3 class="mt-3">Sistema en mantenimiento</h3>';
        echo '<p class="text-muted">' . sanitize($maintenanceMessage) . '</p>';
        echo '<a href="' . $bp . 'auth/logout.php" class="btn btn-outline-secondary btn-sm mt-2"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>';
        echo '</div></div></body></html>';
        exit();
    }
}

function getBasePath() {
    return defined('BASE_PATH') ? BASE_PATH : '/';
}

function isMenuActive($section) {
    $self = $_SERVER['PHP_SELF'];
    if ($section === 'dashboard') {
        return (basename($self) === 'index.php' && dirname($self) === '/') ? 'active' : '';
    }
    return strpos($self, '/' . $section . '/') !== false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle ?? 'Sistema de Préstamos') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= getBasePath() ?>assets/css/style.css?v=5" rel="stylesheet">
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-bank2"></i>
            </div>
            <nav class="sidebar-nav">
                <a class="nav-link <?= isMenuActive('dashboard') ?>" href="<?= getBasePath() ?>index.php">
                    <i class="bi bi-grid-1x2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>

                <div class="nav-section-divider"></div>

                <a class="nav-link <?= isMenuActive('clientes') ?>" href="<?= getBasePath() ?>clientes/index.php">
                    <i class="bi bi-people"></i>
                    <span class="nav-text">Clientes</span>
                </a>
                <a class="nav-link <?= isMenuActive('acreedores') ?>" href="<?= getBasePath() ?>acreedores/index.php">
                    <i class="bi bi-bank"></i>
                    <span class="nav-text">Acreedores</span>
                </a>

                <div class="nav-section-divider"></div>

                <a class="nav-link <?= isMenuActive('prestamos') ?>" href="<?= getBasePath() ?>prestamos/index.php">
                    <i class="bi bi-cash-stack"></i>
                    <span class="nav-text">Préstamos</span>
                </a>
                <a class="nav-link <?= isMenuActive('morosos') ?>" href="<?= getBasePath() ?>morosos/index.php">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span class="nav-text">Morosos</span>
                </a>
                <a class="nav-link <?= isMenuActive('pagos') ?>" href="<?= getBasePath() ?>pagos/index.php">
                    <i class="bi bi-credit-card"></i>
                    <span class="nav-text">Pagos</span>
                </a>
                <a class="nav-link <?= isMenuActive('calendario') ?>" href="<?= getBasePath() ?>calendario/index.php">
                    <i class="bi bi-calendar3"></i>
                    <span class="nav-text">Calendario</span>
                </a>

                <div class="nav-section-divider"></div>

                <a class="nav-link <?= isMenuActive('historial_pagos') ?>" href="<?= getBasePath() ?>historial_pagos/index.php">
                    <i class="bi bi-journal-text"></i>
                    <span class="nav-text">Historial</span>
                </a>
                <a class="nav-link <?= isMenuActive('mis_deudas') ?>" href="<?= getBasePath() ?>mis_deudas/index.php">
                    <i class="bi bi-wallet2"></i>
                    <span class="nav-text">Mis Deudas</span>
                </a>

                <div class="nav-section-divider"></div>

                <a class="nav-link <?= isMenuActive('reportes') ?>" href="<?= getBasePath() ?>reportes/index.php">
                    <i class="bi bi-graph-up"></i>
                    <span class="nav-text">Reportes</span>
                </a>
                <?php if (!isEditor()): ?>
                <a class="nav-link <?= isMenuActive('configuracion') ?>" href="<?= getBasePath() ?>configuracion/index.php">
                    <i class="bi bi-gear"></i>
                    <span class="nav-text">Config</span>
                </a>
                <?php endif; ?>
                <?php if (isLoggedIn() && currentUserRole() === 'admin'): ?>
                <a class="nav-link <?= isMenuActive('usuarios') ?>" href="<?= getBasePath() ?>usuarios/index.php">
                    <i class="bi bi-person-gear"></i>
                    <span class="nav-text">Usuarios</span>
                </a>
                <a class="nav-link <?= isMenuActive('auditoria') ?>" href="<?= getBasePath() ?>auditoria/index.php">
                    <i class="bi bi-shield-check"></i>
                    <span class="nav-text">Auditoría</span>
                </a>
                <?php endif; ?>
            </nav>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="main-content">
            <header class="topbar">
                <button class="topbar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <div></div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <?php if (isLoggedIn()): ?>
                        <span style="font-size:0.82rem; color:var(--text-muted);">
                            <i class="bi bi-person-circle"></i> <?= sanitize($_SESSION['user_nombre'] ?? $_SESSION['user_username'] ?? '') ?>
                        </span>
                        <a href="<?= getBasePath() ?>auth/logout.php" class="btn btn-sm btn-outline-secondary" title="Cerrar sesión" style="font-size:0.78rem;">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                    <button class="dark-mode-toggle" id="darkModeToggle" title="Cambiar tema">
                        <i class="bi bi-moon"></i>
                    </button>
                </div>
            </header>

            <div class="content-area">
                <?php if ($maintenanceActive && isAdmin()): ?>
                    <div class="alert alert-warning alert-dismissible fade show animate-in" role="alert">
                        <i class="bi bi-tools"></i> <strong>Modo mantenimiento activo:</strong> los usuarios no-admin ven la página de mantenimiento.
                        <form method="POST" action="<?= getBasePath() ?>configuracion/index.php" class="d-inline ms-2">
                            <?= csrfField() ?>
                            <input type="hidden" name="mantenimiento_mensaje" value="<?= sanitize($maintenanceMessage) ?>">
                            <input type="hidden" name="mantenimiento_action" value="desactivar">
                            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-play-fill"></i> Desactivar</button>
                        </form>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php $flash = getFlash(); if ($flash): $flashType = in_array($flash['type'] ?? '', ['success','danger','warning','info'], true) ? $flash['type'] : 'info'; ?>
                    <div class="alert alert-<?= $flashType ?> alert-dismissible fade show animate-in" role="alert">
                        <?= sanitize($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
