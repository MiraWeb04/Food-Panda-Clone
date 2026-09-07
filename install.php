<?php
$host = '127.0.0.1';
$db   = 'foodpanda_clone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = new PDO($dsn, $user, $pass, $opt);

$sql = [];
$sql[] = "CREATE DATABASE IF NOT EXISTS `foodpanda_clone` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
$sql[] = "USE `foodpanda_clone`";
$sql[] = "CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  contact_number VARCHAR(30) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','staff') NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$sql[] = "CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
)";
$sql[] = "CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  image VARCHAR(255) DEFAULT NULL,
  available TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$sql[] = "CREATE TABLE IF NOT EXISTS promos (
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
)";
$sql[] = "CREATE TABLE IF NOT EXISTS orders (
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
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$sql[] = "CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_name VARCHAR(150) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)";
$sql[] = "CREATE TABLE IF NOT EXISTS sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)";
$sql[] = "CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  shop_name VARCHAR(150) DEFAULT 'Bean Rooted Cafe',
  logo VARCHAR(255) DEFAULT NULL,
  business_hours VARCHAR(255) DEFAULT 'Mon-Sun 8:00 AM - 10:00 PM',
  receipt_header TEXT DEFAULT NULL,
  receipt_footer TEXT DEFAULT NULL
)";

try {
    foreach ($sql as $statement) {
        $pdo->exec($statement);
    }

    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS email VARCHAR(150) DEFAULT NULL");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS landmark VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(50) NOT NULL DEFAULT 'Paid'");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS receipt_image VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS receipt_status VARCHAR(50) NOT NULL DEFAULT 'Pending'");

    $pdo->exec("INSERT INTO users (full_name, username, email, contact_number, password, role, status) VALUES ('Admin User', 'admin', 'admin@example.com', '09170000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active') ON DUPLICATE KEY UPDATE id=id");
    $pdo->exec("INSERT INTO users (full_name, username, email, contact_number, password, role, status) VALUES ('Staff User', 'staff', 'staff@example.com', '09170000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active') ON DUPLICATE KEY UPDATE id=id");
    $pdo->exec("INSERT INTO categories (name) VALUES ('Pastries'), ('Espresso Classics'), ('Iced Espresso'), ('Matcha & Hojicha Tea'), ('Blended (Frappes)'), ('Sparkling Refreshers'), ('Snacks'), ('Rice Meals') ON DUPLICATE KEY UPDATE id=id");
    $pdo->exec("INSERT INTO settings (id, shop_name, business_hours, receipt_header, receipt_footer) VALUES (1, 'Bean Rooted Cafe', 'Mon-Sun 8:00 AM - 10:00 PM', 'Bean Rooted Cafe', 'Thank you for your order!') ON DUPLICATE KEY UPDATE id=id");

    echo "Database initialized successfully.\n";
} catch (PDOException $e) {
    echo 'Initialization failed: ' . $e->getMessage();
}
