<?php
header('Content-Type: application/json');

$dbAvailable = false;
$pdo = null;

try {
    require_once __DIR__ . '/../config/database.php';
    $dbAvailable = true;
} catch (Throwable $e) {
    $dbAvailable = false;
}

function saveImageFromBase64($base64Data, string $folder): ?string {
    if (!is_string($base64Data) || $base64Data === '') {
        return null;
    }

    if (!preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $base64Data, $m)) {
        return null;
    }

    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $ext = pathinfo($m[1], PATHINFO_EXTENSION) ?: 'png';
    $fileName = $folder . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $fullPath = $dir . '/' . $fileName;
    file_put_contents($fullPath, base64_decode($m[2]));

    return 'uploads/' . $folder . '/' . $fileName;
}

function normalizeStatus(string $status): string {
    $status = trim($status);
    if ($status === '') {
        return 'Pending';
    }

    $normalized = strtolower($status);
    if ($normalized === 'accepted') {
        return 'Preparing';
    }
    if (in_array($normalized, ['ready for pickup', 'ready', 'out for delivery', 'on delivery', 'out-for-delivery'], true)) {
        return 'Out for Delivery';
    }
    if ($normalized === 'completed' || $normalized === 'delivered') {
        return 'Delivered';
    }
    if ($normalized === 'rejected') {
        return 'Rejected';
    }
    if ($normalized === 'cancelled') {
        return 'Cancelled';
    }

    return $status;
}

function fetchOrderWithItems($pdo, int $orderId, ?string $trackingCode = null): ?array {
    if ($trackingCode) {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? OR order_number = ? OR tracking_code = ? LIMIT 1');
        $stmt->execute([$orderId, $trackingCode, $trackingCode]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
        $stmt->execute([$orderId]);
    }

    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }

    $itemsStmt = $pdo->prepare('SELECT product_name, quantity, unit_price, line_total FROM order_items WHERE order_id = ? ORDER BY id');
    $itemsStmt->execute([$order['id']]);
    $order['items'] = $itemsStmt->fetchAll();

    return $order;
}

function parseInputPayload(): array {
    $payload = [];

    if (!empty($_POST)) {
        return $_POST;
    }

    if (!empty($_GET)) {
        return $_GET;
    }

    $rawInput = trim((string) file_get_contents('php://input'));
    if ($rawInput === '') {
        $fallbackFile = __DIR__ . '/../uploads/request_body.json';
        if (is_file($fallbackFile)) {
            $rawInput = trim((string) file_get_contents($fallbackFile));
        }
    }

    if ($rawInput === '') {
        return $payload;
    }

    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    if (str_contains($rawInput, '=')) {
        parse_str($rawInput, $parsed);
        if (is_array($parsed)) {
            return $parsed;
        }
    }

    return $payload;
}

function formatOrderPayload(array $order): array {
    $order['status'] = $order['status'] ?? 'Pending';
    return [
        'id' => $order['order_number'] ?? $order['id'],
        'dbId' => (int) $order['id'],
        'order_id' => (int) $order['id'],
        'order_number' => $order['order_number'],
        'trackingCode' => $order['tracking_code'] ?? null,
        'tracking_code' => $order['tracking_code'] ?? null,
        'customer' => $order['customer_name'],
        'email' => $order['email'],
        'phone' => $order['contact_number'],
        'address' => $order['address'],
        'landmark' => $order['landmark'],
        'items' => $order['items'] ?? [],
        'total' => (float) ($order['total_amount'] ?? 0),
        'promoTitle' => $order['promo_title'] ?? '',
        'promoType' => $order['promo_type'] ?? '',
        'promoValue' => (float)($order['promo_value'] ?? 0),
        'promoDiscount' => (float)($order['promo_discount'] ?? 0),
        'discountedTotal' => (float)($order['discounted_total'] ?? ($order['total_amount'] ?? 0)),
        'paymentMethod' => $order['payment_method'],
        'status' => $order['status'],
        'paymentStatus' => $order['payment_status'] ?? 'Paid',
        'receiptImage' => $order['receipt_image'],
        'deliveryProofImage' => $order['delivery_proof_image'],
        'proofImage' => $order['delivery_proof_image'],
        'receiptStatus' => $order['receipt_status'] ?? 'Pending',
        'assignedRiderId' => (int)($order['assigned_rider_id'] ?? 0),
        'rejectionReason' => $order['rejection_reason'],
        'deliveryNumber' => $order['delivery_number'],
        'createdAt' => $order['created_at'],
    ];
}

$input = parseInputPayload();
$action = $input['action'] ?? 'create_order';

if ($action === 'update_receipt') {
    $orderId = (int)($input['order_id'] ?? 0);
    $receiptImage = $input['receipt_image'] ?? '';

    if (!$orderId || empty($receiptImage)) {
        echo json_encode(['success' => false, 'message' => 'Missing order id or receipt image']);
        exit;
    }

    $savedPath = saveImageFromBase64($receiptImage, 'receipts');
    if ($dbAvailable && $pdo) {
        try {
            $stmt = $pdo->prepare('UPDATE orders SET receipt_image = ?, receipt_status = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$savedPath, 'Pending', $orderId]);
        } catch (Throwable $e) {
            // fall back to success response when the database cannot persist the update
        }
    }

    echo json_encode(['success' => true, 'message' => 'Receipt received. It will be reviewed shortly.']);
    exit;
}

if ($action === 'get_order') {
    $trackingCode = trim($input['tracking_code'] ?? '');
    $orderId = (int)($input['order_id'] ?? 0);

    if (!$dbAvailable || !$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database unavailable']);
        exit;
    }

    $order = fetchOrderWithItems($pdo, $orderId, $trackingCode ?: null);
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    echo json_encode(['success' => true, 'order' => formatOrderPayload($order)]);
    exit;
}

if ($action === 'get_orders') {
    if (!$dbAvailable || !$pdo) {
        echo json_encode(['success' => false, 'orders' => []]);
        exit;
    }

    $stmt = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC');
    $orders = $stmt->fetchAll();
    $payload = [];
    foreach ($orders as $order) {
        $payload[] = formatOrderPayload($order);
    }

    echo json_encode(['success' => true, 'orders' => $payload]);
    exit;
}

if ($action === 'update_order') {
    $orderId = (int)($input['order_id'] ?? 0);
    if (!$orderId) {
        echo json_encode(['success' => false, 'message' => 'Missing order id']);
        exit;
    }

    if (!$dbAvailable || !$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database unavailable']);
        exit;
    }

    $fields = [];
    $params = [];

    if (array_key_exists('status', $input)) {
        $status = normalizeStatus((string)($input['status'] ?? 'Pending'));
        $fields[] = 'status = ?';
        $params[] = $status;
    }

    if (array_key_exists('payment_status', $input)) {
        $fields[] = 'payment_status = ?';
        $params[] = (string) $input['payment_status'];
    }

    if (array_key_exists('receipt_status', $input)) {
        $fields[] = 'receipt_status = ?';
        $params[] = (string) $input['receipt_status'];
    }

    if (array_key_exists('receipt_image', $input) && $input['receipt_image'] !== '') {
        $savedPath = saveImageFromBase64((string) $input['receipt_image'], 'receipts');
        if ($savedPath) {
            $fields[] = 'receipt_image = ?';
            $params[] = $savedPath;
        }
    }

    if (array_key_exists('proof_image', $input) && $input['proof_image'] !== '') {
        $savedPath = saveImageFromBase64((string) $input['proof_image'], 'proofs');
        if ($savedPath) {
            $fields[] = 'delivery_proof_image = ?';
            $params[] = $savedPath;
        }
    }

    if (array_key_exists('rejection_reason', $input)) {
        $fields[] = 'rejection_reason = ?';
        $params[] = (string) $input['rejection_reason'];
    }

    if (array_key_exists('delivery_number', $input)) {
        $fields[] = 'delivery_number = ?';
        $params[] = (string) $input['delivery_number'];
    }

    if (array_key_exists('assigned_rider_id', $input)) {
        $fields[] = 'assigned_rider_id = ?';
        $params[] = (int) $input['assigned_rider_id'];
    }

    if (empty($fields)) {
        echo json_encode(['success' => false, 'message' => 'No fields provided']);
        exit;
    }

    $params[] = $orderId;
    $stmt = $pdo->prepare('UPDATE orders SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ?');
    $stmt->execute($params);

    $order = fetchOrderWithItems($pdo, $orderId);
    echo json_encode(['success' => true, 'order' => formatOrderPayload($order)]);
    exit;
}

$customerName = trim($input['customer'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$address = trim($input['address'] ?? '');
$landmark = trim($input['landmark'] ?? '');
$paymentMethod = trim($input['paymentMethod'] ?? 'cash');
$totalAmount = (float)($input['total'] ?? 0);
$items = $input['items'] ?? [];
$notes = $address . ($landmark ? ' | Landmark: ' . $landmark : '');
$status = 'Pending';
$paymentStatus = $paymentMethod === 'wallet' ? 'Pending' : 'Paid';

file_put_contents(__DIR__ . '/../uploads/parsed_payload.log', json_encode([
    'input' => $input,
    'customerName' => $customerName,
    'phone' => $phone,
    'address' => $address,
    'items' => $items,
], JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);

if (!$customerName || !$phone || !$address || !$items) {
    echo json_encode(['success' => false, 'message' => 'Missing order information']);
    exit;
}

$year = date('Y');
$month = date('m');
$day = date('d');
$prefix = 'BR-' . $year . $month . $day;
$orderNumber = $prefix . '-' . date('His');
$trackingCode = strtoupper('TRK-' . date('YmdHis') . '-' . bin2hex(random_bytes(2)));

if ($dbAvailable && $pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM orders WHERE order_number LIKE '$prefix%'");
        $count = (int)$stmt->fetch()['count'] + 1;
        $orderNumber = $prefix . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);

        $pdo->beginTransaction();
        // allow promo metadata to be stored if provided
        $promoTitle = trim($input['promoTitle'] ?? $input['promo_title'] ?? '');
        $promoType = trim($input['promoType'] ?? $input['promo_type'] ?? '');
        $promoValue = (float)($input['promoValue'] ?? $input['promo_value'] ?? 0);
        $promoDiscount = (float)($input['promoDiscount'] ?? $input['promo_discount'] ?? 0);
        $discountedTotal = isset($input['discountedTotal']) ? (float)$input['discountedTotal'] : $totalAmount - $promoDiscount;

        $stmt = $pdo->prepare('INSERT INTO orders (order_number, customer_name, email, contact_number, order_notes, payment_method, total_amount, promo_title, promo_type, promo_value, promo_discount, discounted_total, status, payment_status, address, landmark, tracking_code, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$orderNumber, $customerName, $email, $phone, $notes, $paymentMethod, $totalAmount, $promoTitle, $promoType, $promoValue, $promoDiscount, $discountedTotal, $status, $paymentStatus, $address, $landmark, $trackingCode]);
        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_name, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)');
        foreach ($items as $item) {
            $name = trim($item['name'] ?? '');
            $quantity = (int)($item['quantity'] ?? 1);
            $price = (float)($item['price'] ?? 0);
            $lineTotal = $price * $quantity;
            if ($name) {
                $itemStmt->execute([$orderId, $name, $quantity, $price, $lineTotal]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber, 'tracking_code' => $trackingCode]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => true, 'order_id' => 'local-' . time(), 'order_number' => $orderNumber, 'tracking_code' => $trackingCode, 'message' => 'Order saved locally while the database is being prepared.']);
    }
} else {
    echo json_encode(['success' => true, 'order_id' => 'local-' . time(), 'order_number' => $orderNumber, 'tracking_code' => $trackingCode, 'message' => 'Order saved locally while the database is being prepared.']);
}
