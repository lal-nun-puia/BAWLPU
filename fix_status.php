<?php
require 'db.php';
$pdo->exec('UPDATE bookings SET status = "Applied" WHERE status IS NULL OR status = ""');
echo 'Updated null statuses to Applied' . PHP_EOL;
?>
