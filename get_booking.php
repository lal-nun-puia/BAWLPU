<?php
// get_booking.php - For nurses to view applied jobs
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'Nurse') {
    header('Location: login.php');
    exit();
}

$nurse_id = $_SESSION['user']['user_id'];

// Fetch applied bookings for this nurse (direct bookings and applications)
$hasStatus = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'status'");
    $hasStatus = (bool)$col->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $hasStatus = false;
}
$hasRequestId = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'request_id'");
    $hasRequestId = (bool)$col->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $hasRequestId = false;
}
$sql = "SELECT b.*, u.name AS client_name, u.phone AS client_phone, u.address AS client_address";
if ($hasRequestId) {
    $sql .= ", cr.patient_name, cr.phone AS request_phone, cr.address AS request_address, cr.notes AS request_notes, cr.service_type";
}
$sql .= " FROM bookings b
        JOIN users u ON b.client_id = u.id";
if ($hasRequestId) {
    $sql .= " LEFT JOIN client_requests cr ON b.request_id = cr.id";
}
$sql .= " WHERE b.nurse_id = ?";
if ($hasStatus) {
    $sql .= " AND b.status = 'Applied'";
}
$sql .= " ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nurse_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Applied Jobs - Nurse & Healthworker Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
<style>
body { font-family:'Poppins',sans-serif; margin:0; background:var(--bg); color:var(--text); }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 24px; background:var(--card); color:var(--text); position:sticky; top:0; z-index:100; box-shadow:var(--shadow); border-bottom:1px solid rgba(0,0,0,0.06); }
header h1 { font-size:24px; margin:0; }
.header-actions { display:flex; gap:10px; align-items:center; }
.nav-btn { background:var(--primary); color:white; border:none; padding:10px 16px; border-radius:10px; cursor:pointer; font-size:14px; }
.nav-btn:hover { background:var(--primary-700); }
.theme-toggle-btn { border:1px solid var(--primary); border-radius:999px; padding:10px 16px; background:var(--card); color:var(--primary); font-weight:600; cursor:pointer; transition:background 0.2s ease, color 0.2s ease; }
.theme-toggle-btn:hover { background:var(--primary); color:#fff; }
.container { max-width:900px; margin:24px auto; padding:0 20px; }
.card { background:var(--card); border-radius:15px; padding:20px; margin-bottom:15px; box-shadow:var(--shadow); border:1px solid rgba(0,0,0,0.04); }
.card h3 { margin:0 0 10px 0; }
.card p { margin:5px 0; }
.accept-btn { background:var(--accent); color:white; border:none; padding:8px 16px; border-radius:8px; cursor:pointer; font-size:14px; margin-top:10px; }
.accept-btn:hover { filter:brightness(0.95); }
.reject-btn { background:var(--warning); color:#0f172a; border:none; padding:8px 16px; border-radius:8px; cursor:pointer; font-size:14px; margin-top:10px; margin-left:10px; }
.reject-btn:hover { filter:brightness(0.95); }
footer { text-align:center; padding:20px; background:var(--card); color:var(--text); margin-top:40px; border-top:1px solid rgba(0,0,0,0.06); }
</style>
</head>
<body>

<header>
<h1>Applied Jobs</h1>
<div class="header-actions">
  <button class="nav-btn" onclick="window.location.href='index.php'">Home</button>
  <button class="nav-btn" onclick="window.history.back()">Back</button>
  <button id="themeToggle" type="button" class="theme-toggle-btn">Dark Mode</button>
</div>
</header>

<div class="container">
<?php if($bookings): ?>
    <?php foreach($bookings as $b): ?>
        <div class="card">
            <h3>Client: <?php echo htmlspecialchars($b['client_name']); ?></h3>
            <?php if($b['request_id']): // Nurse application ?>
                <p><strong>Patient:</strong> <?php echo htmlspecialchars($b['patient_name']); ?></p>
                <p><strong>Service:</strong> <?php echo htmlspecialchars($b['service_type']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($b['request_phone']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($b['request_address']); ?></p>
                <p><strong>Notes:</strong> <?php echo htmlspecialchars($b['request_notes'] ?: 'N/A'); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($b['status'] ?: 'Applied'); ?></p>
            <?php else: // Direct booking ?>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($b['date']); ?></p>
                <p><strong>Time:</strong> <?php echo htmlspecialchars($b['time']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($b['client_phone']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($b['client_address']); ?></p>
                <p><strong>Notes:</strong> <?php echo htmlspecialchars($b['notes'] ?: 'N/A'); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($b['status'] ?: 'Applied'); ?></p>
                <button class="accept-btn" onclick="acceptJob(<?php echo $b['id']; ?>)">Accept</button>
                <button class="reject-btn" onclick="rejectJob(<?php echo $b['id']; ?>)">Reject</button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No applied jobs yet.</p>
<?php endif; ?>
</div>

<script>
function acceptJob(bookingId) {
    if (confirm('Accept this job?')) {
        fetch('accept_job.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ booking_id: bookingId, action: 'accept' })
        }).then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Job accepted!');
                location.reload();
            } else {
                alert('Error: ' + (result.error || 'Unknown error'));
            }
        }).catch(() => alert('Server error'));
    }
}

function rejectJob(bookingId) {
    if (confirm('Reject this job?')) {
        fetch('accept_job.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ booking_id: bookingId, action: 'reject' })
        }).then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Job rejected!');
                location.reload();
            } else {
                alert('Error: ' + (result.error || 'Unknown error'));
            }
        }).catch(() => alert('Server error'));
    }
}

(function(){
  const toggle = document.getElementById('themeToggle');
  if(!toggle) return;
  const root = document.documentElement;
  const saved = localStorage.getItem('theme');
  if (saved === 'dark') { root.classList.add('theme-dark'); }
  const updateLabel = () => {
    toggle.textContent = root.classList.contains('theme-dark') ? 'Light Mode' : 'Dark Mode';
  };
  updateLabel();
  toggle.addEventListener('click', function(){
    const isDark = root.classList.toggle('theme-dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateLabel();
  });
})();
</script>

<footer>
<p>&copy; <?php echo date('Y'); ?> Nurse & Healthworker Service Portal. All rights reserved.</p>
</footer>

</body>
</html>
