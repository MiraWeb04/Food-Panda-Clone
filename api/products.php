<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
try {
    $stmt = $pdo->query('SELECT id, name, category, description, price, image, available FROM products WHERE available = 1 AND category IN (SELECT name FROM categories WHERE status = "active") ORDER BY created_at DESC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // normalize image path
    foreach ($rows as &$r) {
        if ($r['image']) {
            if (preg_match('#^(https?://|/)#i', $r['image'])) {
                $r['image'] = $r['image'];
            } else {
                $r['image'] = '/uploads/products/' . ltrim($r['image'], '/');
            }
        } else {
            $r['image'] = '/images/bean_rooted_logo.png';
        }
    }
    echo json_encode(['success'=>true,'products'=>$rows]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage(),'products'=>[]]);
}
