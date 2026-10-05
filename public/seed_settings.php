<?php
$conn = new mysqli('mysql', 'root', 'root', 'elearning');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if service_fee column exists
$result = $conn->query("SHOW COLUMNS FROM `platform_settings` LIKE 'service_fee'");
if ($result->num_rows == 0) {
    // Add column
    $conn->query("ALTER TABLE `platform_settings` ADD `service_fee` DECIMAL(10,2) NOT NULL DEFAULT '2000.00'");
    echo "Added service_fee column.\n";
} else {
    echo "service_fee column already exists.\n";
}

// Ensure there is at least one row
$res = $conn->query("SELECT COUNT(*) as count FROM `platform_settings`");
$row = $res->fetch_assoc();
if ($row['count'] == 0) {
    $now = date('Y-m-d H:i:s');
    $conn->query("INSERT INTO `platform_settings` (`platform_name`, `service_fee`, `created_at`, `updated_at`) VALUES ('EduNusa Platform', 2000, '$now', '$now')");
    echo "Inserted default settings row.\n";
}

$conn->close();
