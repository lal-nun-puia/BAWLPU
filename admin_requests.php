<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
  header('Location: login.php');
  exit;
}

try {
  $stmt = $pdo->query("SELECT cr.id, cr.client_id, cr.service_type, cr.patient_name, cr.phone, cr.status, cr.created_at, u.name AS client_name
                       FROM client_requests cr JOIN users u ON cr.client_id=u.id ORDER BY cr.id DESC");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  // Fallback when legacy table is missing the status column
  $stmt = $pdo->query("SELECT cr.id, cr.client_id, cr.service_type, cr.patient_name, cr.phone, cr.created_at, u.name AS client_name
                       FROM client_requests cr JOIN users u ON cr.client_id=u.id ORDER BY cr.id DESC");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as &$r) { $r['status'] = 'Pending'; }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>All Requests</title>
  <link rel="stylesheet" href="assets/styles.css">
  <script src="assets/theme.js"></script>
</head>
<body>
<?php include 'partials/nav.php'; ?>
<div class="container" style="max-width:1100px;margin:80px auto 20px;">
  <h2>All Client Requests</h2>
  <table class="table">
    <thead><tr><th>ID</th><th>Client</th><th>Service</th><th>Patient</th><th>Phone</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['client_name']) ?></td>
        <td><?= htmlspecialchars($r['service_type']) ?></td>
        <td><?= htmlspecialchars($r['patient_name']) ?></td>
        <td><?= htmlspecialchars($r['phone']) ?></td>
        <td><?= htmlspecialchars($r['status']) ?></td>
        <td><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
        <td><?= htmlspecialchars($r['status']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>
