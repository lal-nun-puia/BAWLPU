<?php
require 'db.php';

$stmt = $pdo->query("SELECT id, name, email, role, service FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Users:\n";
foreach($users as $u) {
    echo $u['id'] . ' - ' . $u['name'] . ' - ' . $u['email'] . ' - ' . $u['role'] . ' - ' . $u['service'] . "\n";
}
?>
