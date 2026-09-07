<?php
// Backup and drop admin/staff/rider related tables: users, riders, admins
require_once __DIR__ . '/../config/database.php';

$tables = ['users', 'riders', 'admins'];
$existing = [];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '" . addslashes($t) . "'");
        $res = $stmt ? $stmt->fetch(PDO::FETCH_NUM) : false;
        if ($res) $existing[] = $t;
    } catch (Exception $e) {
        // ignore
    }
}

if (empty($existing)) {
    echo "No admin/staff/rider tables found to drop.\n";
    exit(0);
}

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
$ts = date('Ymd_His');
$backupFile = $dataDir . "/backup_admin_tables_{$ts}.sql";
$fh = fopen($backupFile, 'w');
fwrite($fh, "-- Backup of admin/staff/rider tables: " . implode(', ', $existing) . "\n-- Generated: " . date('c') . "\n\n");

foreach ($existing as $t) {
    // Structure
    $row = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_NUM);
    if ($row && isset($row[1])) {
        fwrite($fh, "-- Table structure for {$t}\n");
        fwrite($fh, "DROP TABLE IF EXISTS `{$t}`;\n");
        fwrite($fh, $row[1] . ";\n\n");
    }

    // Data
    $rows = $pdo->query("SELECT * FROM `{$t}`")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $cols = array_map(function($c){ return "`$c`"; }, array_keys($rows[0]));
        $colList = implode(', ', $cols);
        foreach (array_chunk($rows, 100) as $chunk) {
            $values = [];
            foreach ($chunk as $r) {
                $vals = array_map(function($v) use ($pdo) {
                    if ($v === null) return 'NULL';
                    return $pdo->quote((string)$v);
                }, array_values($r));
                $values[] = '(' . implode(', ', $vals) . ')';
            }
            fwrite($fh, "INSERT INTO `{$t}` ({$colList}) VALUES\n" . implode(",\n", $values) . ";\n\n");
        }
    }
}

fclose($fh);
echo "Backup written to: {$backupFile}\n";

// Drop tables
foreach ($existing as $t) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
        echo "Dropped table: {$t}\n";
    } catch (Exception $e) {
        echo "Failed to drop {$t}: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
