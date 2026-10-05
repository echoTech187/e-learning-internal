<?php
define('FCPATH', __DIR__ . '/public/');
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';

$db = \Config\Database::connect();

$db->query("CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `trx_id` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
)");

$db->query("CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-info-circle',
  `icon_color` varchar(20) DEFAULT '#000000',
  `title` varchar(255) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
)");

$db->query("TRUNCATE TABLE transactions");
$db->query("TRUNCATE TABLE activity_logs");

$db->query("INSERT INTO `transactions` (`user_id`, `course_id`, `trx_id`, `amount`, `payment_method`, `status`, `created_at`, `updated_at`) VALUES (1, 1, 'TRX-458905840958490', 550000.00, 'Bank Transfer (BCA)', 'paid', NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 1 HOUR)");

$db->query("INSERT INTO `activity_logs` (`icon`, `icon_color`, `title`, `description`, `created_at`) VALUES ('fab fa-discord', '#000000', 'Community forum', 'New discussion created in Laravel group', NOW() - INTERVAL 1 HOUR), ('fab fa-instagram', '#f97316', 'Instagram comment', 'New comment on promotional post', NOW() - INTERVAL 3 HOUR)");

echo "Database seeded via CI4\n";
