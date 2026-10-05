<?php
$users = [
    ['Test User', 'ekosuesanto25@gmail.com', 'test123', 'student'],
    ['Eko Susanto', 'ekosuesanto28@gmail.com', 'test123', 'parent'],
    ['EchoAdza', 'echotech187@gmail.com', 'test123', 'student'],
    ['Andi Wijaya', 'andi@example.com', 'password', 'student']
];

$queries = ["SET FOREIGN_KEY_CHECKS = 0;", "TRUNCATE TABLE users;", "SET FOREIGN_KEY_CHECKS = 1;"];
foreach ($users as $u) {
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $hash = password_hash($u[2], PASSWORD_BCRYPT);
    $queries[] = sprintf("INSERT INTO users (id, name, email, password, role) VALUES ('%s', '%s', '%s', '%s', '%s');", $uuid, $u[0], $u[1], $hash, $u[3]);
}
echo implode("\n", $queries);
