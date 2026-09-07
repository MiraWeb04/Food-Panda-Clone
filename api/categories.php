<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query('SELECT name, status FROM categories ORDER BY name ASC');
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'categories' => $categories]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'categories' => []]);
}
