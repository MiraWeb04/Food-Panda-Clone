-- Backup of admin/staff/rider tables: users, riders, admins
-- Generated: 2026-07-13T14:04:40+00:00

-- Table structure for users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `contact_number`, `password`, `role`, `status`, `created_at`) VALUES
('1', 'Admin User', 'admin', 'admin@example.com', '09170000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '2026-07-09 23:42:11'),
('2', 'Staff User', 'staff', 'staff@example.com', '09170000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'active', '2026-07-09 23:42:11');

-- Table structure for riders
DROP TABLE IF EXISTS `riders`;
CREATE TABLE `riders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `motorcycle_plate_number` varchar(50) DEFAULT NULL,
  `status` enum('available','busy','inactive') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `riders` (`id`, `full_name`, `username`, `password`, `contact_number`, `motorcycle_plate_number`, `status`, `created_at`) VALUES
('1', 'Rider One', 'rider', '$2y$12$NDyvtqOvyzxDew9IN8m/9edcwkcp8WqcarlKC68cDxy85jjHxeSeq', '09170000002', 'ABC-1234', 'available', '2026-07-13 20:41:14');

-- Table structure for admins
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `full_name`, `created_at`) VALUES
('1', 'admin', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@coffeeshop.com', 'Administrator', '2026-07-13 20:25:36');

