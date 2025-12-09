<?php
require 'db.php';
$result = $pdo->query('SELECT id, status FROM bookings WHERE status IS NULL OR status = ""')->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
    echo 'Booking ID: ' . $row['id'] . ', Status: ' . $row['status'] . PHP_EOL;
}
?>
