<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'Admin') {
  header('Location: login.php');
  exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';

// Counts
$totalClients = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='Client'")->fetchColumn();
$totalNurses  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='Nurse' AND approval_status='Approved'")->fetchColumn();
$pendingNurses = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='Nurse' AND approval_status='Pending'")->fetchColumn();
$totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRequests = (int)$pdo->query("SELECT COUNT(*) FROM client_requests")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="assets/styles.css">
  <script src="assets/theme.js"></script>
</head>
<body>

<?php include 'partials/nav.php'; ?>

<div class="container" style="max-width:1100px;margin:80px auto 20px;">
  <h2>Welcome, <?php echo htmlspecialchars($adminName); ?></h2>

  <div class="grid grid-4 mt-3">
    <div class="card">
      <h3>Total Clients</h3>
      <h1><?php echo $totalClients; ?></h1>
    </div>
    <div class="card">
      <h3>Approved Nurses</h3>
      <h1><?php echo $totalNurses; ?></h1>
    </div>
    <div class="card">
      <h3>Total Bookings</h3>
      <h1><?php echo $totalBookings; ?></h1>
    </div>
    <div class="card">
      <h3>Total Requests</h3>
      <h1><?php echo $totalRequests; ?></h1>
    </div>
  </div>

  <div class="mt-3 flex">
    <a class="btn btn-primary" href="view_users.php">Manage Users</a>
    <a class="btn btn-primary" href="admins_booking.php">All Bookings</a>
    <a class="btn btn-primary" href="admin_requests.php">All Requests</a>
  </div>
</div>

<script src="assets/nav.js"></script>
</body>
</html>
  <div class="grid grid-4 mt-3">
    <div class="card">
      <h3>Pending Nurse Reviews</h3>
      <h1><?php echo $pendingNurses; ?></h1>
    </div>
  </div>
