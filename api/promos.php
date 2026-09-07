<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    // Ensure expired promos are not treated as active
    $pdo->exec("UPDATE promos SET status = 'expired' WHERE status = 'active' AND DATE(end_date) < CURDATE()");
    $stmt = $pdo->prepare('SELECT id, title, description, discount_type, discount_value, start_date, end_date, banner, status FROM promos WHERE status = ? AND DATE(start_date) <= CURDATE() AND DATE(end_date) >= CURDATE() ORDER BY start_date ASC');
    $stmt->execute(['active']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $promos = [];
    foreach ($rows as $r) {
        $promos[] = [
            'id' => $r['id'],
            'title' => $r['title'],
            'description' => $r['description'],
            'discount_type' => $r['discount_type'],
            'discount_value' => $r['discount_value'],
            'start_date' => substr($r['start_date'], 0, 10),
            'end_date' => substr($r['end_date'], 0, 10),
            'banner' => $r['banner'] ? '/uploads/promos/' . ltrim($r['banner'], '/') : null,
            'status' => $r['status'] ?? 'active'
        ];
    }
    echo json_encode(['success' => true, 'promos' => $promos]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'promos' => []]);
}
