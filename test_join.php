<?php
$db = new \PDO('mysql:host=mysql;dbname=elearning', 'root', 'root');
$stmt = $db->query("SELECT category_id FROM courses LIMIT 1");
$course = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt2 = $db->query("SELECT id, name FROM categories WHERE id = '" . $course['category_id'] . "'");
$cat = $stmt2->fetch(PDO::FETCH_ASSOC);

echo "Course category_id: " . $course['category_id'] . "\n";
echo "Matched category: " . print_r($cat, true) . "\n";
