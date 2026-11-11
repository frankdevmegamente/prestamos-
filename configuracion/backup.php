<?php
require_once __DIR__ . '/../config/database.php';
requireLogin();
if (!isAdmin()) { flash('danger', 'Solo administradores'); redirect(BASE_PATH . 'index.php'); }

$pdo = getConnection();
$tablas = ['clientes', 'prestamos', 'pagos', 'acreedores', 'mis_deudas', 'pagos_deudas', 'configuracion', 'usuarios', 'auditoria'];
$out = "-- Backup Sistema Préstamos " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\n";
foreach ($tablas as $t) {
    try {
        $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch();
        if (!$create) continue;
        $out .= "\nDROP TABLE IF EXISTS `$t`;\n" . ($create['Create Table'] ?? '') . ";\n";
        $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll();
        foreach ($rows as $r) {
            $cols = array_map(fn($c) => "`$c`", array_keys($r));
            $vals = array_map(fn($v) => $v === null ? "NULL" : $pdo->quote($v), array_values($r));
            $out .= "INSERT INTO `$t` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
        }
    } catch (Exception $e) { $out .= "-- omitida $t: " . $e->getMessage() . "\n"; }
}
$out .= "SET FOREIGN_KEY_CHECKS=1;\n";
audit($pdo, 'backup_db', null, null, 'Descarga backup manual');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="backup_' . date('Ymd_His') . '.sql"');
header('Content-Length: ' . strlen($out));
echo $out;
exit();
