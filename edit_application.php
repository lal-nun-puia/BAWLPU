<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Nurse') {
    header('Location: login.php');
    exit();
}

$nurse_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_id = intval($_POST['booking_id']);
    $date = trim($_POST['date']);
    $time = trim($_POST['time']);

    if (empty($date) || empty($time)) {
        echo "<script>alert('Date and time are required'); window.location='nurse_bookings.php';</script>";
        exit();
    }

    // Update booking
    $stmt = $pdo->prepare("UPDATE bookings SET date = ?, time = ? WHERE id = ? AND nurse_id = ? AND status = 'Applied'");
    $result = $stmt->execute([$date, $time, $booking_id, $nurse_id]);

    if ($result && $stmt->rowCount() > 0) {
        echo "<script>alert('Application updated successfully'); window.location='nurse_bookings.php';</script>";
    } else {
        echo "<script>alert('Unable to update (check status)'); window.location='nurse_bookings.php';</script>";
    }
    exit();
}

// Get booking details
$booking_id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, cr.patient_name, cr.service_type FROM bookings b JOIN client_requests cr ON b.request_id = cr.id WHERE b.id = ? AND b.nurse_id = ? AND b.status = 'Applied'");
$stmt->execute([$booking_id, $nurse_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: nurse_bookings.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Application - Nurse & Healthworker Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family:'Poppins',sans-serif; margin:0; background:#e0f7fa; color:#004d40; padding:20px; }
.container { max-width:600px; margin:auto; background:#fff; padding:20px; border-radius:15px; box-shadow:0 8px 20px rgba(0,0,0,0.15); }
h2 { text-align:center; }
form { display:flex; flex-direction:column; gap:15px; }
input { padding:10px; border:1px solid #00796b; border-radius:8px; }
button { background:#00796b; color:#fff; border:none; padding:10px; border-radius:8px; cursor:pointer; }
button:hover { background:#004d40; }
</style>
</head>
<body>
<div class="container">
<h2>Edit Application for <?= htmlspecialchars($booking['patient_name']) ?> (<?= htmlspecialchars($booking['service_type']) ?>)</h2>
<form method="POST">
<input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
<label>Date:</label>
<input type="date" name="date" value="<?= htmlspecialchars($booking['date']) ?>" required>
<label>Time:</label>
<input type="time" name="time" value="<?= htmlspecialchars($booking['time']) ?>" required>
<button type="submit">Save Changes</button>
</form>
<button onclick="window.location='nurse_bookings.php'">Cancel</button>
</div>
</body>
</html>
