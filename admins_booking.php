<?php
session_start();
require_once "db.php";

if(!isset($_SESSION['user_role']) || $_SESSION['user_role']!="Admin"){
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query("SELECT b.*, c.name AS client_name, n.name AS nurse_name
     FROM bookings b
     JOIN users c ON b.client_id=c.id
     JOIN users n ON b.nurse_id=n.id
     ORDER BY b.id DESC");
$data=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>All Bookings</title>
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
<style>
body{font-family:Poppins;padding:20px;}
table{width:100%;border-collapse:collapse;background:white;}
td,th{border:1px solid #ccc;padding:8px;}
th{background:#333;color:#fff;}
</style>
</head>
<body>
<?php include 'partials/nav.php'; ?>
<div class="container" style="max-width:1000px;margin:80px auto 20px;">
<h2>All Bookings</h2>
<table class="table">
<thead><tr><th>ID</th><th>Client</th><th>Nurse</th><th>Status</th><th>Booked At</th></tr></thead>
<tbody>
<?php foreach($data as $b){ ?>
<tr>
<td><?= (int)$b['id'] ?></td>
<td><?= htmlspecialchars($b['client_name']) ?></td>
<td><?= htmlspecialchars($b['nurse_name']) ?></td>
<td><?= htmlspecialchars($b['status'] ?? '') ?></td>
<td><?= htmlspecialchars($b['created_at']) ?></td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
</body>
</html>
