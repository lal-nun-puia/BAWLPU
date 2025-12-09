<?php
require 'db.php';
$stmt = $pdo->query('SELECT COUNT(*) FROM bookings WHERE nurse_id NOT IN (SELECT id FROM users)');
echo 'Orphaned nurse bookings: ' . $stmt->fetchColumn() . PHP_EOL;
$stmt = $pdo->query('SELECT COUNT(*) FROM bookings WHERE client_id NOT IN (SELECT id FROM users)');
echo 'Orphaned client bookings: ' . $stmt->fetchColumn() . PHP_EOL;
?>
