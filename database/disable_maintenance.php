<?php
// Script de emergencia para desactivar el modo mantenimiento.
// Uso: http://localhost/.../database/disable_maintenance.php (solo desde localhost)
require_once __DIR__ . '/../config/database.php';

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
$allowed = in_array($remote, ['127.0.0.1', '::1', 'localhost'], true) || (php_sapi_name() === 'cli');
if (!$allowed) {
    http_response_code(403);
    die('Acceso denegado');
}

$pdo = getConnection();
$pdo->exec("UPDATE configuracion SET mantenimiento = 0 WHERE id = 1");
echo "Modo mantenimiento desactivado.\n";
