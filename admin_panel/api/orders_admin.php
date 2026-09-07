<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

try {
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Order ID required');
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) throw new Exception('Order not found');

        // fetch items
        $items = [];
        $it = $pdo->prepare('SELECT product_name, quantity, unit_price, line_total FROM order_items WHERE order_id = ? ORDER BY id');
        $it->execute([$order['id']]);
        $items = $it->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'order' => $order, 'items' => $items]);
        exit;
    }

    // list with filters
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(10, (int)($_GET['perPage'] ?? 20)));
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];

    $search = trim($_GET['search'] ?? '');
    if ($search !== '') {
        $where[] = '(order_number LIKE ? OR customer_name LIKE ? OR contact_number LIKE ? OR email LIKE ?)';
        $like = "%$search%";
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    }

    $status = trim($_GET['status'] ?? '');
    if ($status !== '') {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    $payment = trim($_GET['payment'] ?? '');
    if ($payment !== '') {
        $where[] = 'payment_method = ?';
        $params[] = $payment;
    }

    $start = trim($_GET['start_date'] ?? '');
    $end = trim($_GET['end_date'] ?? '');
    if ($start !== '') {
        $where[] = "DATE(created_at) >= ?";
        $params[] = $start;
    }
    if ($end !== '') {
        $where[] = "DATE(created_at) <= ?";
        $params[] = $end;
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // include rider name when available via subquery (safe if riders table missing)
    $stmt = $pdo->prepare("SELECT orders.*, (SELECT CONCAT(first_name,' ',last_name) FROM riders WHERE id = orders.assigned_rider_id LIMIT 1) AS rider_name FROM orders $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?");
    // bind params then perPage and offset
    $execParams = $params;
    $execParams[] = $perPage;
    $execParams[] = $offset;
    $stmt->execute($execParams);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'orders' => $rows,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => (int)ceil($total / $perPage)
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
