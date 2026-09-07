<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/database.php';

function isLoggedIn(?string $role = null): bool {
    if (empty($_SESSION['user_id'])) {
        return false;
    }
    if ($role && ($_SESSION['role'] ?? '') !== $role) {
        return false;
    }
    return true;
}

function requireLogin(?string $role = null): void {
    if (!isLoggedIn($role)) {
        header('Location: /login.php');
        exit;
    }
}

function redirectToDashboard(): void {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin') {
        header('Location: /admin_panel/dashboard.php');
    } elseif ($role === 'staff') {
        header('Location: /staff/dashboard.php');
    } else {
        header('Location: /login.php');
    }
    exit;
}

function logout(): void {
    session_unset();
    session_destroy();
}
