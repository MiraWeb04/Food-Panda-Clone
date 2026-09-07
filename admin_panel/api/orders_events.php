<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

// Simple SSE that notifies when orders.updated_at changes
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
set_time_limit(0);

$last = $_GET['last'] ?? null;
if (!$last) {
    $row = $pdo->query('SELECT MAX(updated_at) as m FROM orders')->fetch(PDO::FETCH_ASSOC);
    $last = $row['m'] ?? date('Y-m-d H:i:s');
}

echo "retry: 2000\n\n";
while (true) {
    try {
        $row = $pdo->query('SELECT MAX(updated_at) as m FROM orders')->fetch(PDO::FETCH_ASSOC);
        $m = $row['m'] ?? null;
        if ($m && $m !== $last) {
            $last = $m;
            $data = json_encode(['event' => 'orders_updated', 'last' => $last]);
            echo "event: orders_updated\n";
            echo "data: $data\n\n";
            ob_flush(); flush();
        }
    } catch (Throwable $e) {
        // ignore DB errors and continue
    }
    sleep(2);
}

?>
