<?php
require_once __DIR__ . '/config/database.php';
header('Content-Type: text/plain');
try {
    $check = function($col){ global $pdo; $stmt=$pdo->query("SHOW COLUMNS FROM promos LIKE '" . addslashes($col) . "'"); return (bool)$stmt->fetch(); };
    $queries = [];
    if ($check('promo_title')) {
        $queries[] = "ALTER TABLE promos CHANGE promo_title title VARCHAR(200) NOT NULL";
    }
    if ($check('promo_description')) {
        $queries[] = "ALTER TABLE promos CHANGE promo_description description TEXT NULL";
    }
    if ($check('image_url')) {
        $queries[] = "ALTER TABLE promos CHANGE image_url banner VARCHAR(255) NULL";
    }

    if (empty($queries)) {
        echo "No migration needed. Columns already present.\n";
        exit(0);
    }

    foreach ($queries as $q) {
        echo "Running: $q\n";
        $pdo->exec($q);
    }

    echo "Migration completed.\n";
    // Show new columns
    $stmt = $pdo->query('SHOW COLUMNS FROM promos');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cols, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
