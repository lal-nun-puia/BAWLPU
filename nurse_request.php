<?php
session_start();
require_once "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Nurse'){
    header("Location: login.php");
    exit();
}

$stmt = $pdo->query("SELECT r.*, u.name AS client_name 
                     FROM client_requests r 
                     JOIN users u ON r.client_id=u.user_id
                     WHERE r.status='Pending'
                     ORDER BY r.id DESC");
$req = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<title>Available Requests</title>
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
<style>
body{font-family:Poppins;background:#fff;padding:20px;}
table{width:100%;border-collapse:collapse;background:white;}
th,td{padding:10px;border:1px solid #ddd;}
th{background:#00796b;color:white;}
a.btn{padding:6px 10px;background:#00796b;color:white;border-radius:5px;text-decoration:none;}
</style>
</head>
<body>
<h2>Client Requests</h2>
<table>
<tr><th>Client</th><th>Service</th><th>Name</th><th>Phone</th><th>Action</th></tr>

<?php foreach($req as $r): ?>
<tr>
<td><?= $r['client_name'] ?></td>
<td><?= $r['service_type'] ?></td>
<td><?= $r['name'] ?></td>
<td><?= $r['phone'] ?></td>
<td><a class="btn" href="accept_job.php?id=<?= $r['id'] ?>">Accept</a></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
