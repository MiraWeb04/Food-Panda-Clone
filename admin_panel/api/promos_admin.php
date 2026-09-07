<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? 'list';
$uploadDir = __DIR__ . '/../../uploads/promos';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function buildPromoResponse(array $promo): array {
    // Map DB columns (title/description/banner) to API-friendly shape
    $promo['title'] = $promo['title'] ?? ($promo['promo_title'] ?? '');
    $promo['description'] = $promo['description'] ?? ($promo['promo_description'] ?? '');
    $banner = $promo['banner'] ?? ($promo['image_url'] ?? null);
    $promo['banner'] = $banner;
    $promo['banner_url'] = $banner ? '/uploads/promos/' . ltrim($banner, '/') : null;
    $today = date('Y-m-d');
    // normalize date portion if stored as datetime
    $endDate = isset($promo['end_date']) ? substr($promo['end_date'], 0, 10) : null;
    $promo['is_expired'] = $endDate && $endDate < $today;
    $promo['computed_status'] = $promo['status'] ?? 'inactive';
    if (($promo['status'] ?? '') === 'active' && $promo['is_expired']) {
        $promo['computed_status'] = 'expired';
    }
    // keep legacy keys for compatibility
    $promo['start_date'] = isset($promo['start_date']) ? substr($promo['start_date'], 0, 10) : null;
    $promo['end_date'] = $endDate;
    return $promo;
}

try {
    // Auto-expire promos whose end_date has passed
    $pdo->exec("UPDATE promos SET status = 'expired' WHERE status = 'active' AND DATE(end_date) < CURDATE()");
    if ($action === 'list') {
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(title LIKE ? OR description LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $pdo->prepare("SELECT * FROM promos $whereSql ORDER BY start_date DESC, created_at DESC");
        $stmt->execute($params);
        $promos = array_map('buildPromoResponse', $stmt->fetchAll(PDO::FETCH_ASSOC));

        echo json_encode(['success' => true, 'promos' => $promos]);
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Promo ID is required.');
        }
        $stmt = $pdo->prepare('SELECT * FROM promos WHERE id = ?');
        $stmt->execute([$id]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$promo) {
            throw new Exception('Promo not found.');
        }
        echo json_encode(['success' => true, 'promo' => buildPromoResponse($promo)]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $discountType = trim($_POST['discount_type'] ?? 'percentage');
        $discountValue = (float)($_POST['discount_value'] ?? 0);
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $status = trim($_POST['status'] ?? 'inactive');

        if ($action === 'delete') {
            if ($id <= 0) {
                throw new Exception('Promo ID is required.');
            }
            $stmt = $pdo->prepare('SELECT banner FROM promos WHERE id = ?');
            $stmt->execute([$id]);
            $oldBanner = $stmt->fetchColumn();
            $stmt = $pdo->prepare('DELETE FROM promos WHERE id = ?');
            $stmt->execute([$id]);
            if ($oldBanner) {
                @unlink($uploadDir . '/' . basename($oldBanner));
            }
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'toggle') {
            if ($id <= 0) {
                throw new Exception('Promo ID is required.');
            }
            if (!in_array($status, ['active', 'inactive'], true)) {
                throw new Exception('Invalid status.');
            }
            $stmt = $pdo->prepare('UPDATE promos SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($title === '') {
            throw new Exception('Promo title is required.');
        }
        if (!in_array($discountType, ['percentage', 'fixed'], true)) {
            throw new Exception('Invalid discount type.');
        }
        if ($discountValue <= 0) {
            throw new Exception('Discount value must be greater than zero.');
        }
        if ($startDate === '' || $endDate === '') {
            throw new Exception('Start date and end date are required.');
        }
        if ($startDate > $endDate) {
            throw new Exception('End date must be the same or later than the start date.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new Exception('Invalid status.');
        }

        $bannerName = null;
        if (!empty($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['banner'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                throw new Exception('Only JPG, PNG, GIF, and WEBP banners are allowed.');
            }
            $bannerName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $bannerName);
        }

        if ($action === 'add') {
            $stmt = $pdo->prepare('INSERT INTO promos (title, description, discount_type, discount_value, start_date, end_date, banner, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $description, $discountType, $discountValue, $startDate, $endDate, $bannerName, $status]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            exit;
        }

        if ($action === 'edit') {
            if ($id <= 0) {
                throw new Exception('Promo ID is required.');
            }

            if ($bannerName) {
                $stmt = $pdo->prepare('SELECT banner FROM promos WHERE id = ?');
                $stmt->execute([$id]);
                $oldBanner = $stmt->fetchColumn();
            }

            $sql = 'UPDATE promos SET title = ?, description = ?, discount_type = ?, discount_value = ?, start_date = ?, end_date = ?, status = ?';
            $params = [$title, $description, $discountType, $discountValue, $startDate, $endDate, $status];
            if ($bannerName) {
                $sql .= ', banner = ?';
                $params[] = $bannerName;
            }
            $sql .= ' WHERE id = ?';
            $params[] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if (!empty($oldBanner) && $bannerName) {
                @unlink($uploadDir . '/' . basename($oldBanner));
            }

            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
