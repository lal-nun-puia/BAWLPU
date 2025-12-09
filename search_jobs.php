<?php
session_start();
require_once 'db.php';

$logged_in = isset($_SESSION['user']);
if (!$logged_in || $_SESSION['user']['role'] !== 'Nurse') {
    header('Location: login.php');
    exit();
}

$service = $_GET['type'] ?? '';
$allowed = ['Babysitting','ElderlyCare','PatientCare'];
$params = [];
$sql = "SELECT cr.*, u.name AS client_name FROM client_requests cr JOIN users u ON cr.client_id = u.id";
if ($service && in_array($service, $allowed)) {
    $sql .= " WHERE cr.service_type = ?";
    $params[] = $service;
}
$sql .= " ORDER BY cr.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Jobs - Nurses</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body{font-family:Poppins, sans-serif;background:#eaf7f6;color:#004d40;padding:20px}
.container{max-width:1000px;margin:0 auto}
.card{background:#fff;padding:16px;border-radius:10px;margin-bottom:12px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
.badge{display:inline-block;padding:6px 10px;border-radius:8px;background:#00796b;color:#fff;font-weight:600;margin-left:8px}
button{background:#00796b;color:#fff;border:none;padding:8px 12px;border-radius:8px;cursor:pointer}
</style>
</head>
<body>
<div class="container">
  <h2>Available Requests <?php if($service) echo " - ".htmlspecialchars($service); ?></h2>
  <button onclick="window.history.back()" class="back-btn">Back</button>

  <?php if(count($requests)===0): ?>
    <div class="card">No requests found.</div>
  <?php else: foreach($requests as $r): ?>
    <div class="card">
      <div>
        <strong><?php echo htmlspecialchars($r['patient_name']); ?></strong>
        <span class="badge"><?php echo htmlspecialchars($r['service_type']); ?></span>
      </div>
      <div style="margin-top:8px">
        <strong>Client:</strong> <?php echo htmlspecialchars($r['client_name']); ?> &nbsp; 
        <strong>Phone:</strong> <?php echo htmlspecialchars($r['phone']); ?>
      </div>
      <div style="margin-top:8px">
        <strong>Address:</strong> <?php echo htmlspecialchars($r['address']); ?>
      </div>
      <div style="margin-top:8px">
        <strong>Notes:</strong><br><?php echo nl2br(htmlspecialchars($r['notes'])); ?>
      </div>
      <div style="margin-top:10px">
        <button onclick="window.location.href='booking.php?client_id=<?php echo $r['client_id']; ?>&request_id=<?php echo $r['id']; ?>'">Book this client</button>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>
</body>
</html>

