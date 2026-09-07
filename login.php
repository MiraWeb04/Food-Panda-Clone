<?php
session_start();
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Please provide username and password.']);
        exit;
    }

    try {
        // First check users table (admin/staff)
        $stmt = $pdo->prepare('SELECT id, username, full_name, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $role = null;
        $identity = null;

        if ($user) {
            $identity = $user;
            $role = $user['role'];
        } else {
            // Check riders table
            $stmt = $pdo->prepare('SELECT id, username, full_name, password FROM riders WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r) {
                $identity = $r;
                $role = 'rider';
            }
        }

        if (!$identity) {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
            exit;
        }

        if (!password_verify($password, $identity['password'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
            exit;
        }

        // Successful login: set session
        $_SESSION['user_id'] = $identity['id'];
        $_SESSION['username'] = $identity['username'];
        $_SESSION['full_name'] = $identity['full_name'] ?? '';
        $_SESSION['role'] = $role;

        // choose redirect
        if ($role === 'admin') {
          $redirect = '/admin_panel/dashboard.php';
        } elseif ($role === 'staff') {
            $redirect = '/staff/dashboard.php';
        } else {
            $redirect = '/rider/dashboard.php';
        }

        echo json_encode(['success' => true, 'redirect' => $redirect]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}

// If GET, show the login page (render HTML)
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Bean Rooted Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      :root{--cream:#F5EFE6;--olive:#5D7348;--charcoal:#2E2E2E;--gold:#C9A34B}
      body{background:linear-gradient(180deg,#fff 0%,#f7f3ee 100%);color:var(--charcoal)}
      .login-card{max-width:900px;margin:3rem auto;box-shadow:0 8px 30px rgba(46,46,46,.08);border-radius:.75rem;overflow:hidden}
      .brand-panel{background:linear-gradient(180deg,var(--olive),#47613a);color:#fff;padding:2.5rem}
      .brand-panel h1{font-size:1.6rem;margin-bottom:.25rem}
      .form-panel{padding:2.5rem;background:var(--cream)}
      .btn-primary{background:var(--olive);border-color:var(--olive)}
      .text-muted-small{font-size:.9rem;color:#666}
      .error{color:#b02a37;margin-top:.5rem}
    </style>
  </head>
  <body>
    <div class="login-card d-flex">
      <div class="brand-panel col-12 col-md-5">
        <div class="d-flex align-items-center gap-3">
          <img src="images/bean_rooted_logo.png" alt="logo" style="width:56px;height:56px;border-radius:8px;object-fit:cover">
          <div>
            <h1>Bean Rooted Cafe</h1>
            <p class="text-muted-small">Manage orders and deliveries — sign in to continue.</p>
          </div>
        </div>
        <div class="mt-4">
          <p class="small">Login with your username and password. Your role (Admin, Staff, Rider) determines which dashboard you'll be redirected to.</p>
        </div>
      </div>
      <div class="form-panel col-12 col-md-7">
        <h2 class="mb-3">Sign in</h2>
        <form id="login-form" novalidate>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
          </div>
          <div id="error" class="error" role="alert" aria-live="polite" hidden></div>
          <div class="d-flex justify-content-between align-items-center mt-4">
            <button class="btn btn-primary" type="submit">Sign in</button>
            <a href="index.html" class="text-decoration-none">Back to shop</a>
          </div>
        </form>
      </div>
    </div>

    <script>
      const form = document.getElementById('login-form');
      const err = document.getElementById('error');
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        err.hidden = true;
        const fd = new FormData(form);
        const res = await fetch(window.location.href, { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
          err.textContent = data.message || 'Login failed';
          err.hidden = false;
          return;
        }
        window.location.href = data.redirect;
      });
    </script>
  </body>
</html>
