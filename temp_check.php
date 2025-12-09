<?php
require 'db.php';
$stmt = $pdo->query('SHOW CREATE TABLE bookings');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo $result['Create Table'] . PHP_EOL;
?>
