<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Nurse') {
    header('Location: login.php');
    exit();
}

$nurse_id = $_SESSION['user_id'];

// Fetch applied/accepted bookings
$sql = "SELECT b.*, cr.patient_name, cr.service_type, cr.address, cr.phone, cr.notes, cr.date, cr.time, u.name AS client_name
        FROM bookings b
        JOIN client_requests cr ON b.request_id = cr.id
        JOIN users u ON b.client_id = u.id
        WHERE b.nurse_id = ? AND b.status IN ('Applied', 'Accepted')
        ORDER BY b.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nurse_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch reviews received
$sql_reviews = "SELECT rating, review_text, created_at
                FROM reviews
                WHERE reviewee_id = ?
                ORDER BY created_at DESC";
$stmt_reviews = $pdo->prepare($sql_reviews);
$stmt_reviews->execute([$nurse_id]);
$reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings - Nurse & Healthworker Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
<style>
body { font-family:'Poppins',sans-serif; margin:0; background:#e0f7fa; color:#004d40; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 30px; background:#00796b; color:#fff; position:sticky; top:0; z-index:100; }
header h1 { font-size:24px; }
.back-btn { background:#004d40; color:white; border:none; padding:10px 20px; border-radius:10px; cursor:pointer; font-size:14px; margin:20px; }
.back-btn:hover { background:#00796b; }
.container { max-width:900px; margin:20px auto; padding:0 20px; }
.card { background:#fff; border-radius:15px; padding:20px; margin-bottom:15px; box-shadow:0 8px 20px rgba(0,0,0,0.15); }
.card h3 { margin:0 0 10px 0; }
.card p { margin:5px 0; }
.cancel-btn { background:#d32f2f; color:white; border:none; padding:8px 16px; border-radius:8px; cursor:pointer; font-size:14px; margin-top:10px; }
.cancel-btn:hover { background:#b71c1c; }
.accept-btn { background:#4caf50; color:white; border:none; padding:8px 16px; border-radius:8px; cursor:pointer; font-size:14px; margin-top:10px; }
.accept-btn:hover { background:#45a049; }
footer { text-align:center; padding:20px; background:#00796b; color:#fff; margin-top:40px; }
</style>
</head>
<body>

<header>
<h1>My Bookings</h1>
</header>

<button class="back-btn" onclick="window.location.href='index.php'">Back to Home</button>

<div class="container">
<h2>My Applications & Bookings</h2>
<?php if($bookings): ?>
    <?php foreach($bookings as $b): ?>
        <div class="card">
            <h3><?= htmlspecialchars($b['patient_name']) ?> (<?= htmlspecialchars($b['service_type']) ?>)</h3>
            <p><strong>Client:</strong> <?= htmlspecialchars($b['client_name']) ?></p>
            <p><strong>Date:</strong> <?= htmlspecialchars($b['date']) ?></p>
            <p><strong>Time:</strong> <?= htmlspecialchars($b['time']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($b['phone']) ?></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($b['address']) ?></p>
            <p><strong>Notes:</strong> <?= htmlspecialchars($b['notes']) ?: 'N/A' ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($b['status']) ?></p>
            <?php if($b['status'] == 'Applied'): ?>
                <button class="accept-btn" onclick="window.location.href='edit_application.php?id=<?= $b['id'] ?>'">Edit Application</button>
                <button class="cancel-btn" onclick="if(confirm('Cancel this application?')) window.location.href='cancel_booking.php?id=<?= $b['id'] ?>'">Cancel Application</button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No bookings yet.</p>
<?php endif; ?>

<h2>My Reviews</h2>
<?php if($reviews): ?>
    <?php foreach($reviews as $r): ?>
        <div class="card">
            <p><strong>Client:</strong> Anonymous</p>
            <p><strong>Rating:</strong> ⭐ <?= htmlspecialchars($r['rating']) ?>/5</p>
            <p><strong>Comment:</strong> <?= htmlspecialchars($r['review_text']) ?: 'No comment' ?></p>
            <p><strong>Date:</strong> <?= htmlspecialchars($r['created_at']) ?></p>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No reviews yet.</p>
<?php endif; ?>
</div>

<footer>
<p>&copy; <?= date('Y') ?> Nurse & Healthworker Service Portal. All rights reserved.</p>
</footer>

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
</script>

</body>
</html>
