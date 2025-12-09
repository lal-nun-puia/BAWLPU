<?php
require 'db.php';
$result = $pdo->query('SELECT COUNT(*) as count FROM bookings')->fetch(PDO::FETCH_ASSOC);
echo 'Total bookings: ' . $result['count'] . PHP_EOL;
$result = $pdo->query('SELECT COUNT(*) as count FROM bookings WHERE request_id IS NULL')->fetch(PDO::FETCH_ASSOC);
echo 'Direct bookings: ' . $result['count'] . PHP_EOL;
$result = $pdo->query('SELECT COUNT(*) as count FROM bookings WHERE request_id IS NOT NULL')->fetch(PDO::FETCH_ASSOC);
echo 'Request-based bookings: ' . $result['count'] . PHP_EOL;
?>
