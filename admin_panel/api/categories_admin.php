<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Forbidden']);
    exit;
}

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? 'list';

try {
    if ($action === 'list') {
        $search = trim($_GET['search'] ?? '');
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = 'WHERE name LIKE ?';
            $params[] = "%$search%";
        }
        $stmt = $pdo->prepare("SELECT id, name, status, (SELECT COUNT(*) FROM products WHERE products.category = categories.name) AS product_count FROM categories $where ORDER BY name ASC");
        $stmt->execute($params);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'categories' => $categories]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if ($action === 'add' || $action === 'edit') {
            if ($name === '') {
                throw new Exception('Category name is required.');
            }
            if (!in_array($status, ['active', 'inactive'], true)) {
                throw new Exception('Invalid category status.');
            }

            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO categories (name, status) VALUES (?, ?)');
                $stmt->execute([$name, $status]);
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
                exit;
            }

            if ($action === 'edit') {
                if (empty($id)) {
                    throw new Exception('Category ID is required.');
                }
                $stmt = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
                $stmt->execute([$id]);
                $oldName = $stmt->fetchColumn();
                $stmt = $pdo->prepare('UPDATE categories SET name = ?, status = ? WHERE id = ?');
                $stmt->execute([$name, $status, $id]);
                if ($oldName && $oldName !== $name) {
                    $updateProducts = $pdo->prepare('UPDATE products SET category = ? WHERE category = ?');
                    $updateProducts->execute([$name, $oldName]);
                }
                echo json_encode(['success' => true]);
                exit;
            }
        }

        if ($action === 'delete') {
            if (empty($id)) {
                throw new Exception('Category ID is required.');
            }
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'toggle') {
            if (empty($id)) {
                throw new Exception('Category ID is required.');
            }
            $stmt = $pdo->prepare('UPDATE categories SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
