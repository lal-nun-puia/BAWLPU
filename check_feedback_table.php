<?php
require 'db.php';
$stmt = $pdo->query('SHOW CREATE TABLE feedback');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo $result['Create Table'] . PHP_EOL;
?>
