<?php
require_once __DIR__ . '/config/database.php';
header('Content-Type: text/plain');
try {
    $checks = [];
    $cols = ['promo_title' => "VARCHAR(200) NULL", 'promo_type' => "VARCHAR(50) NULL", 'promo_value' => "DECIMAL(10,2) NULL", 'promo_discount' => "DECIMAL(10,2) NULL", 'discounted_total' => "DECIMAL(10,2) NULL"];
    foreach ($cols as $col => $type) {
        $res = $pdo->query("SHOW COLUMNS FROM orders LIKE '" . addslashes($col) . "'")->fetch();
        if (!$res) {
            $sql = "ALTER TABLE orders ADD COLUMN $col $type";
            echo "Running: $sql\n";
            $pdo->exec($sql);
        } else {
            echo "Column $col already exists\n";
        }
    }
    echo "Done.\n";
    $stmt = $pdo->query('SHOW COLUMNS FROM orders');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo 'Migration error: ' . $e->getMessage() . "\n";
    exit(1);
}
