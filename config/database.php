<?php
$host = 'localhost';
$db   = 'foodpanda_clone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$opt = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

function initSqlite(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        contact_number TEXT DEFAULT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS riders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        contact_number TEXT DEFAULT NULL,
        motorcycle_plate_number TEXT DEFAULT NULL,
        status TEXT NOT NULL DEFAULT 'available',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_number TEXT NOT NULL UNIQUE,
        customer_name TEXT NOT NULL,
        email TEXT DEFAULT NULL,
        contact_number TEXT NOT NULL,
        address TEXT DEFAULT NULL,
        landmark TEXT DEFAULT NULL,
        order_notes TEXT DEFAULT NULL,
        payment_method TEXT NOT NULL,
        total_amount NUMERIC(10,2) NOT NULL DEFAULT 0.00,
        status TEXT NOT NULL DEFAULT 'Pending',
        payment_status TEXT NOT NULL DEFAULT 'Paid',
        receipt_image TEXT DEFAULT NULL,
        receipt_status TEXT NOT NULL DEFAULT 'Pending',
        tracking_code TEXT DEFAULT NULL,
        delivery_proof_image TEXT DEFAULT NULL,
        assigned_rider_id INTEGER DEFAULT NULL,
        delivery_number TEXT DEFAULT NULL,
        rejection_reason TEXT DEFAULT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_name TEXT NOT NULL,
        quantity INTEGER NOT NULL DEFAULT 1,
        unit_price NUMERIC(10,2) NOT NULL DEFAULT 0.00,
        line_total NUMERIC(10,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sales (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        amount NUMERIC(10,2) NOT NULL DEFAULT 0.00,
        sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    // seed defaults if empty
    $userCount = (int) ($pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() ?? 0);
    if ($userCount === 0) {
        $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (full_name, username, email, contact_number, password, role, status) VALUES (?, ?, ?, ?, ?, ?, "active")');
        $stmt->execute(['Admin User', 'admin', 'admin@example.com', '09170000000', $defaultPassword, 'admin']);
        $stmt->execute(['Staff User', 'staff', 'staff@example.com', '09170000001', $defaultPassword, 'staff']);
    }

    $riderCount = (int) ($pdo->query('SELECT COUNT(*) FROM riders')->fetchColumn() ?? 0);
    if ($riderCount === 0) {
        $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO riders (full_name, username, password, contact_number, motorcycle_plate_number, status) VALUES (?, ?, ?, ?, ?, "available")');
        $stmt->execute(['Rider One', 'rider', $defaultPassword, '09170000002', 'ABC-1234']);
    }

    $catCount = (int) ($pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() ?? 0);
    if ($catCount === 0) {
        $pdo->exec("INSERT INTO categories (name) VALUES
            ('Pastries'),
            ('Espresso Classics'),
            ('Iced Espresso'),
            ('Matcha & Hojicha Tea'),
            ('Blended (Frappes)'),
            ('Sparkling Refreshers'),
            ('Snacks'),
            ('Rice Meals')");
    }
}

try {
    // Try MySQL first
    $setupDsn = "mysql:host=$host;charset=$charset";
    $setupPdo = new PDO($setupDsn, $user, $pass, $opt);
    $setupPdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $setupPdo = null;

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $opt);

    // create tables (MySQL-specific types kept similar to original)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(150) NOT NULL,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(150) NOT NULL UNIQUE,
        contact_number VARCHAR(30) DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','staff') NOT NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS riders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(150) NOT NULL,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        contact_number VARCHAR(30) DEFAULT NULL,
        motorcycle_plate_number VARCHAR(30) DEFAULT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active'
    )");

    $pdo->exec("ALTER TABLE categories ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') NOT NULL DEFAULT 'active'");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        category VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        image VARCHAR(255) DEFAULT NULL,
        available TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS promos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT DEFAULT NULL,
        discount_type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
        discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        banner VARCHAR(255) DEFAULT NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'inactive',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(50) NOT NULL UNIQUE,
        customer_name VARCHAR(150) NOT NULL,
        email VARCHAR(150) DEFAULT NULL,
        contact_number VARCHAR(30) NOT NULL,
        address VARCHAR(255) DEFAULT NULL,
        landmark VARCHAR(255) DEFAULT NULL,
        order_notes TEXT DEFAULT NULL,
        payment_method VARCHAR(50) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        payment_status VARCHAR(50) NOT NULL DEFAULT 'Paid',
        receipt_image VARCHAR(255) DEFAULT NULL,
        receipt_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
        tracking_code VARCHAR(50) DEFAULT NULL,
        delivery_proof_image VARCHAR(255) DEFAULT NULL,
        assigned_rider_id INT DEFAULT NULL,
        delivery_number VARCHAR(50) DEFAULT NULL,
        rejection_reason TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_name VARCHAR(150) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    // Seed default users/rider when empty
    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount === 0) {
        $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (full_name, username, email, contact_number, password, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')")
            ->execute([
                'Admin User',
                'admin',
                'admin@example.com',
                '09170000000',
                $defaultPassword,
                'admin'
            ]);
        $pdo->prepare("INSERT INTO users (full_name, username, email, contact_number, password, role, status) VALUES (?, ?, ?, ?, ?, ?, 'active')")
            ->execute([
                'Staff User',
                'staff',
                'staff@example.com',
                '09170000001',
                $defaultPassword,
                'staff'
            ]);
    }

    $riderCount = (int) $pdo->query('SELECT COUNT(*) FROM riders')->fetchColumn();
    if ($riderCount === 0) {
        $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO riders (full_name, username, password, contact_number, motorcycle_plate_number, status) VALUES (?, ?, ?, ?, ?, 'available')")
            ->execute([
                'Rider One',
                'rider',
                $defaultPassword,
                '09170000002',
                'ABC-1234'
            ]);
    }

    $catCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    if ($catCount === 0) {
        $pdo->exec("INSERT INTO categories (name) VALUES
            ('Pastries'),
            ('Espresso Classics'),
            ('Iced Espresso'),
            ('Matcha & Hojicha Tea'),
            ('Blended (Frappes)'),
            ('Sparkling Refreshers'),
            ('Snacks'),
            ('Rice Meals')");
    }

    $productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($productCount === 0) {
        $pdo->exec("INSERT INTO products (name, category, description, price, image, available) VALUES
            ('Americano', 'Espresso Classics', 'Rich espresso with crema.', 120.00, '/images/americano.jpg', 1),
            ('Cafe Latte', 'Espresso Classics', 'Smooth steamed milk with espresso.', 145.00, '/images/cafe_latte.jpg', 1),
            ('Iced Americano', 'Iced Espresso', 'Chilled espresso over ice.', 130.00, '/images/iced_americano.jpg', 1),
            ('Iced Latte', 'Iced Espresso', 'Cold milk and espresso over ice.', 150.00, '/images/iced_latte.jpg', 1),
            ('Hot Matcha', 'Matcha & Hojicha Tea', 'Ceremonial green tea latte.', 155.00, '/images/hot_matcha.jpg', 1),
            ('Hot Hojicha', 'Matcha & Hojicha Tea', 'Toasty hojicha latte.', 150.00, '/images/hot_hojicha.jpg', 1),
            ('Biscoff Caramel', 'Pastries', 'Buttery caramel pastry with biscoff.', 110.00, '/images/biscoff_caramel.jpg', 1),
            ('Blueberry Cinnamon Roll', 'Pastries', 'Sweet roll topped with blueberries.', 125.00, '/images/blueberry_cinnamon_roll.jpg', 1),
            ('Beef Bulgogi', 'Rice Meals', 'Marinated beef served with rice.', 220.00, '/images/beef_bulgogi.jpg', 1),
            ('Lumpiang Shanghai', 'Snacks', 'Crispy pork spring rolls.', 95.00, '/images/lumpiang_shanghai.jpg', 1),
            ('Double Cheese Fries', 'Snacks', 'Loaded fries with melted cheese.', 140.00, '/images/double_cheese_fries.jpg', 1),
            ('Golden Maple', 'Pastries', 'Maple-glazed pastry delight.', 105.00, '/images/golden_maple.jpg', 1)");
    }

} catch (PDOException $e) {
    // MySQL is required. Provide clear error for the developer to start XAMPP/MySQL.
    http_response_code(500);
    echo "<h1>Database connection error</h1>";
    echo "<p>Could not connect to MySQL at {$host}. Please ensure MySQL is running (XAMPP) and the credentials in config/database.php are correct.</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}

