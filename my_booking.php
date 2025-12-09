<?php
session_start();
require_once "db.php";

if($_SESSION['user_role']!="Client") exit;

$client_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT b.*, u.name AS nurse_name, u.phone AS nurse_phone
    FROM bookings b
    JOIN users u ON b.nurse_id = u.id
    WHERE b.client_id=? ORDER BY b.id DESC");
$stmt->execute([$client_id]);
$data=$stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Booking</title>
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
<style>
body{font-family:Poppins;padding:20px;}
table{width:100%;border-collapse:collapse;background:white;}
td,th{border:1px solid #ccc;padding:8px;}
th{background:#00796b;color:#fff;}
.nav-btn{background:#00796b;color:#fff;border:none;padding:8px 14px;border-radius:8px;cursor:pointer;margin-right:10px}
.nav-btn:hover{background:#004d40}
</style>
</head>
<body>
<div style="margin-bottom:15px;">
    <button class="nav-btn" onclick="window.location.href='index.php'">Home</button>
    <button class="nav-btn" onclick="window.history.back()">Back</button>
    
</div>
<h2>My Nurse Bookings</h2>
<table>
<tr><th>Nurse</th><th>Phone</th><th>Status</th></tr>
<?php foreach($data as $b){ ?>
<tr>
<td><?= $b['nurse_name'] ?></td>
<td><?= $b['nurse_phone'] ?></td>
<td>Confirmed</td>
</tr>
<?php } ?>
</table>
</body>
</html>
