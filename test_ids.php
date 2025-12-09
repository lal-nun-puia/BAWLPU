<?php
require 'db.php';
$result = $pdo->query('SELECT id FROM users WHERE email="nurse@test.com"')->fetch(PDO::FETCH_ASSOC);
echo 'Nurse ID: ' . $result['id'] . PHP_EOL;
$result = $pdo->query('SELECT id FROM users WHERE role="Client" LIMIT 1')->fetch(PDO::FETCH_ASSOC);
echo 'Client ID: ' . $result['id'] . PHP_EOL;
?>
