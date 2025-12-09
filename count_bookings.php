<?php
require 'db.php';
$stmt = $pdo->query('SELECT COUNT(*) FROM bookings');
echo 'Total bookings: ' . $stmt->fetchColumn() . PHP_EOL;
?>
