CREATE DATABASE IF NOT EXISTS `foodpanda_clone`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `foodpanda_clone`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(150) NOT NULL,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `contact_number` VARCHAR(30) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','staff') NOT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `image` VARCHAR(255) DEFAULT NULL,
  `available` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `customer_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `contact_number` VARCHAR(30) NOT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `landmark` VARCHAR(255) DEFAULT NULL,
  `order_notes` TEXT DEFAULT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `payment_status` VARCHAR(50) NOT NULL DEFAULT 'Paid',
  `receipt_image` VARCHAR(255) DEFAULT NULL,
  `receipt_status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `tracking_code` VARCHAR(50) DEFAULT NULL,
  `delivery_proof_image` VARCHAR(255) DEFAULT NULL,
  `assigned_rider_id` INT DEFAULT NULL,
  `delivery_number` VARCHAR(50) DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_order_items_orders`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `sales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `sale_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sales_orders`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `shop_name` VARCHAR(150) DEFAULT 'Bean Rooted Cafe',
  `logo` VARCHAR(255) DEFAULT NULL,
  `business_hours` VARCHAR(255) DEFAULT 'Mon-Sun 8:00 AM - 10:00 PM',
  `receipt_header` TEXT DEFAULT NULL,
  `receipt_footer` TEXT DEFAULT NULL
);

INSERT INTO `users` (
  `full_name`, `username`, `email`, `contact_number`, `password`, `role`, `status`
) VALUES
  ('Admin User', 'admin', 'admin@example.com', '09170000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
  ('Staff User', 'staff', 'staff@example.com', '09170000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active')
ON DUPLICATE KEY UPDATE `id` = `id`;

INSERT INTO `categories` (`name`) VALUES
  ('Pastries'),
  ('Espresso Classics'),
  ('Iced Espresso'),
  ('Matcha & Hojicha Tea'),
  ('Blended (Frappes)'),
  ('Sparkling Refreshers'),
  ('Snacks'),
  ('Rice Meals')
ON DUPLICATE KEY UPDATE `id` = `id`;

INSERT INTO `settings` (`id`, `shop_name`, `business_hours`, `receipt_header`, `receipt_footer`) VALUES
  (1, 'Bean Rooted Cafe', 'Mon-Sun 8:00 AM - 10:00 PM', 'Bean Rooted Cafe', 'Thank you for your order!')
ON DUPLICATE KEY UPDATE `id` = `id`;
