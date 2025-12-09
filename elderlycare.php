<?php
session_start();
require_once 'db.php';

$logged_in = isset($_SESSION['user_id']);
if (!$logged_in || $_SESSION['user_role'] !== 'Nurse') {
    header('Location: login.php');
    exit();
}

$nurse_id = $_SESSION['user_id'];

$hasNurseCol = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM client_requests LIKE 'nurse_id'");
    $hasNurseCol = (bool)$col->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $hasNurseCol = false;
}
$sql = "SELECT cr.*, u.name AS client_name FROM client_requests cr JOIN users u ON cr.client_id = u.id WHERE cr.service_type = 'ElderlyCare'";
if ($hasNurseCol) {
    $sql .= " AND (cr.nurse_id IS NULL OR cr.nurse_id = '')";
}
$sql .= " ORDER BY cr.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Elderly Care Jobs - Nurses</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
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
  <h2>Available Elderly Care Requests</h2>
  <button onclick="window.history.back()" class="back-btn">Back</button>

  <?php if(count($requests)===0): ?>
    <div class="card">No elderly care requests available.</div>
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
      <?php 
        $items_display = '';
        if (isset($r['items_json']) && $r['items_json']) {
            $decoded = json_decode($r['items_json'], true);
            if (is_array($decoded) && count($decoded)>0) {
                $rows = [];
                foreach ($decoded as $it) {
                    $n = htmlspecialchars($it['name'] ?? 'Item');
                    $q = (int)($it['qty'] ?? 0);
                    $p = number_format((float)($it['price'] ?? 0), 2);
                    $rows[] = "$n x$q @ &#8377;$p";
                }
                $items_display = implode(', ', $rows);
            }
        }
      ?>
      <?php if($items_display): ?>
      <div style="margin-top:8px">
        <strong>Requested Items:</strong> <?php echo $items_display; ?>
      </div>
      <?php endif; ?>
      <?php if(isset($r['estimated_cost']) && $r['estimated_cost'] !== null): ?>
      <div style="margin-top:4px">
        <strong>Estimated Cost:</strong> &#8377;<?php echo number_format((float)$r['estimated_cost'],2); ?>
      </div>
      <?php endif; ?>
      <div style="margin-top:10px">
        <form method="POST" action="apply_job.php">
          <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
          <label>Date: <input type="date" name="date" required min="<?= date('Y-m-d') ?>"></label><br>
          <label>Time: <input type="time" name="time" required></label><br>
          <button type="submit">Apply for Job</button>
        </form>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>
</body>
</html>
