<?php
// Admin Dashboard - Bean Rooted Cafe
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

// ensure user is logged in
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// redirect non-admins to their dashboards
$role = $_SESSION['role'] ?? '';
if ($role !== 'admin') {
    if ($role === 'staff') header('Location: /staff/dashboard.php');
    elseif ($role === 'rider') header('Location: /rider/dashboard.php');
    else header('Location: /login.php');
    exit;
}

// Fetch statistics using PDO ($pdo from config/database.php)
try {
    $q = $pdo->query("SELECT COUNT(*) FROM orders");
    $totalOrders = (int)$q->fetchColumn();

    $statuses = ['pending','preparing','out for delivery','delivered'];
    // counts
    $pending = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE LOWER(status) = 'pending'")->fetchColumn();
    $preparing = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE LOWER(status) = 'preparing'")->fetchColumn();
    $out_for_delivery = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE LOWER(status) LIKE '%out%'")->fetchColumn();
    $delivered = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE LOWER(status) = 'delivered'")->fetchColumn();

    $revenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders")->fetchColumn();
    $todaySales = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

    // best selling products
    $stmt = $pdo->query("SELECT product_name, SUM(quantity) AS qty FROM order_items GROUP BY product_name ORDER BY qty DESC LIMIT 5");
    $bestProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // recent orders
    $stmt = $pdo->query("SELECT id, order_number, customer_name, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 10");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // daily sales (last 7 days)
    $stmt = $pdo->query("SELECT DATE(created_at) AS day, COALESCE(SUM(total_amount),0) AS total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY day ORDER BY day ASC");
    $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // weekly sales (last 8 weeks)
    $stmt = $pdo->query("SELECT YEAR(created_at) AS yr, WEEK(created_at,1) AS wk, COALESCE(SUM(total_amount),0) AS total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK) GROUP BY yr,wk ORDER BY yr ASC, wk ASC");
    $weeklyRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $weekly = [];
    foreach ($weeklyRaw as $r) {
        $label = $r['yr'].'-W'.str_pad($r['wk'],2,'0',STR_PAD_LEFT);
        $weekly[] = ['label'=>$label,'total'=>(float)$r['total']];
    }

    // monthly sales (last 6 months)
    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COALESCE(SUM(total_amount),0) AS total FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month ASC");
    $monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $totalOrders = $pending = $preparing = $out_for_delivery = $delivered = 0;
    $revenue = $todaySales = 0.0;
    $bestProducts = [];
    $recentOrders = [];
    $daily = $weekly = $monthly = [];
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | Bean Rooted Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  </head>
  <body>
    <div class="d-flex admin-root">
      <aside class="admin-sidebar">
        <div class="brand px-3 py-4">
          <img src="/images/bean_rooted_logo.png" alt="logo" class="sidebar-logo">
          <h4>Bean Rooted</h4>
        </div>
        <nav class="nav flex-column p-2">
          <a class="nav-link active" href="dashboard.php">Dashboard</a>
          <a class="nav-link text-muted" href="modules/products.php">Products</a>
          <a class="nav-link text-muted" href="modules/categories.php">Categories</a>
          <a class="nav-link text-muted" href="modules/promos.php">Promos</a>
          <a class="nav-link text-muted" href="#">Orders</a>
          <a class="nav-link text-muted" href="#">Staff</a>
          <a class="nav-link text-muted" href="#">Riders</a>
          <a class="nav-link text-muted" href="#">Sales Reports</a>
          <a class="nav-link text-muted" href="#">Settings</a>
          <a class="nav-link text-danger mt-auto" href="logout.php">Logout</a>
        </nav>
      </aside>
      <main class="admin-main p-4">
        <header class="d-flex justify-content-between align-items-center mb-4">
          <h2>Dashboard</h2>
          <div>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?> — <small class="text-muted">Administrator</small></div>
        </header>

        <div class="row g-3">
          <div class="col-12 col-md-4 col-lg-3">
            <div class="card stat-card">
              <div class="card-body">
                <h6>Total Orders</h6>
                <h3><?php echo $totalOrders; ?></h3>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card">
              <div class="card-body">
                <h6>Pending</h6>
                <h4><?php echo $pending; ?></h4>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card">
              <div class="card-body">
                <h6>Preparing</h6>
                <h4><?php echo $preparing; ?></h4>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card">
              <div class="card-body">
                <h6>Out for Delivery</h6>
                <h4><?php echo $out_for_delivery; ?></h4>
              </div>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card stat-card">
              <div class="card-body">
                <h6>Delivered</h6>
                <h4><?php echo $delivered; ?></h4>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
              <div class="card-body">
                <h6>Total Sales</h6>
                <h3 class="text-success">₱ <?php echo number_format($revenue,2); ?></h3>
                <small class="text-muted">Today's sales: ₱ <?php echo number_format($todaySales,2); ?></small>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
              <div class="card-body">
                <h6>Best Selling</h6>
                <ul class="list-unstyled mb-0">
                  <?php foreach ($bestProducts as $bp): ?>
                    <li><?php echo htmlspecialchars($bp['product_name']); ?> <span class="badge bg-secondary float-end"><?php echo $bp['qty']; ?></span></li>
                  <?php endforeach; ?>
                  <?php if (empty($bestProducts)) echo '<li class="text-muted">No sales yet</li>'; ?>
                </ul>
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-8">
            <div class="card">
              <div class="card-body">
                <h6>Sales (Daily)</h6>
                <canvas id="chart-daily" height="120"></canvas>
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <div class="card">
              <div class="card-body">
                <h6>Recent Orders</h6>
                <div class="list-group">
                  <?php foreach ($recentOrders as $ord): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <div><strong><?php echo htmlspecialchars($ord['order_number'] ?? ''); ?></strong></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($ord['customer_name'] ?? ''); ?> • <?php echo date('M j, H:i', strtotime($ord['created_at'] ?? '')); ?></div>
                      </div>
                      <div class="text-end">
                        <div>₱ <?php echo number_format($ord['total_amount'],2); ?></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($ord['status']); ?></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                  <?php if (empty($recentOrders)) echo '<div class="list-group-item text-muted">No recent orders</div>'; ?>
                </div>
              </div>
            </div>
          </div>

        </div>
      </main>
    </div>

    <script>
      const dailyLabels = <?php echo json_encode(array_column($daily, 'day')); ?>;
      const dailyData = <?php echo json_encode(array_map(function($r){ return (float)$r['total']; }, $daily)); ?>;

      const ctx = document.getElementById('chart-daily').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: dailyLabels,
          datasets: [{
            label: 'Sales',
            data: dailyData,
            borderColor: '#5D7348',
            backgroundColor: 'rgba(93,115,72,0.08)',
            tension: 0.3,
            fill: true
          }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
      });
    </script>
  </body>
</html>
