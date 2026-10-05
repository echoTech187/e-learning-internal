<?php
$conn = new mysqli('mysql', 'root', 'root', 'elearning');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `description` text,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_amount` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_discount` decimal(10,2) DEFAULT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `usage_limit` int(11) NOT NULL DEFAULT '0',
  `used_count` int(11) NOT NULL DEFAULT '0',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$conn->query($sql);

$result = $conn->query("SELECT COUNT(*) as count FROM coupons");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $now = date('Y-m-d H:i:s');
    $nextMonth = date('Y-m-d H:i:s', strtotime('+30 days'));
    $nextWeek = date('Y-m-d H:i:s', strtotime('+7 days'));
    $tomorrow = date('Y-m-d H:i:s', strtotime('+1 days'));

    $conn->query("INSERT INTO coupons (code, description, discount_type, discount_amount, min_purchase, max_discount, valid_from, valid_until, status, created_at, updated_at) VALUES 
    ('EDUNUSA10', 'Diskon 10% untuk semua kursus (Maks Rp 50.000)', 'percentage', 10, 100000, 50000, '$now', '$nextMonth', 'active', '$now', '$now'),
    ('DISKON50K', 'Potongan harga langsung Rp 50.000', 'fixed', 50000, 150000, 50000, '$now', '$nextWeek', 'active', '$now', '$now'),
    ('KILAT20', 'Diskon kilat 20% (Tanpa maksimum diskon)', 'percentage', 20, 200000, NULL, '$now', '$tomorrow', 'active', '$now', '$now')
    ");
    echo "Seeded successfully.\n";
} else {
    echo "Already seeded.\n";
}
$conn->close();
