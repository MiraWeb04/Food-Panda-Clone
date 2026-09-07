<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403); echo json_encode(['success'=>false,'message'=>'Forbidden']); exit;
}

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? 'list';

// ensure upload dir
$uploadDir = __DIR__ . '/../../uploads/products';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

try {
    if ($action === 'list') {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = max(1, (int)($_GET['per_page'] ?? 10));
        $search = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');

        $where = [];
        $params = [];
        if ($search !== '') { $where[] = '(name LIKE ? OR description LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($category !== '') { $where[] = 'category = ?'; $params[] = $category; }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = $pdo->prepare("SELECT COUNT(*) FROM products $whereSql");
        $total->execute($params); $totalCount = (int)$total->fetchColumn();

        $offset = ($page-1)*$per;
        $stmt = $pdo->prepare("SELECT * FROM products $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?");
        foreach ($params as $i=>$p) $stmt->bindValue($i+1, $p);
        $stmt->bindValue(count($params)+1, $per, PDO::PARAM_INT);
        $stmt->bindValue(count($params)+2, $offset, PDO::PARAM_INT);
        $stmt->execute(); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success'=>true,'total'=>$totalCount,'products'=>$rows]); exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add' || $action === 'edit') {
            $id = $_POST['id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $available = (int)($_POST['available'] ?? 1);

            if ($name === '' || $category === '') {
                throw new Exception('Name and category are required.');
            }

            $imageName = null;
            if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $f = $_FILES['image'];
                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                $allowedExt = ['jpg','jpeg','png','gif','webp'];
                if (!in_array($ext, $allowedExt, true)) {
                    throw new Exception('Only JPG, PNG, GIF and WEBP images are allowed.');
                }
                $imageName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                move_uploaded_file($f['tmp_name'], $uploadDir . '/' . $imageName);
            }

            if ($action === 'add') {
                $stmt = $pdo->prepare('INSERT INTO products (name, category, description, price, image, available) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $category, $description, $price, $imageName, $available]);
                echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
                exit;
            }

            if ($action === 'edit') {
                if (empty($id)) {
                    throw new Exception('Product ID is required for editing.');
                }
                $old = null;
                if ($imageName) {
                    $stmt = $pdo->prepare('SELECT image FROM products WHERE id = ?');
                    $stmt->execute([$id]);
                    $old = $stmt->fetchColumn();
                }
                $sql = 'UPDATE products SET name = ?, category = ?, description = ?, price = ?, available = ?';
                $params = [$name, $category, $description, $price, $available];
                if ($imageName) {
                    $sql .= ', image = ?';
                    $params[] = $imageName;
                }
                $sql .= ' WHERE id = ?';
                $params[] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                if (!empty($old) && $imageName) {
                    @unlink($uploadDir . '/' . $old);
                }
                echo json_encode(['success' => true]);
                exit;
            }
        }

        if ($action === 'delete') {
            $id = $_POST['id'] ?? null;
            if (empty($id)) {
                throw new Exception('Product ID is required.');
            }
            $stmt = $pdo->prepare('SELECT image FROM products WHERE id = ?');
            $stmt->execute([$id]);
            $old = $stmt->fetchColumn();
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$id]);
            if (!empty($old)) {
                @unlink($uploadDir . '/' . $old);
            }
            echo json_encode(['success' => true]);
            exit;
        }
    }

    if ($action === 'get') {
        $id = $_GET['id'] ?? null; $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?'); $stmt->execute([$id]); $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
        echo json_encode(['success'=>true,'product'=>$p]); exit;
    }

    echo json_encode(['success'=>false,'message'=>'Invalid action']);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
}
