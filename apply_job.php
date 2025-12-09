<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Nurse') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = intval($_POST['request_id']);
    $nurse_id = $_SESSION['user_id'];
    $date = trim($_POST['date']);
    $time = trim($_POST['time']);

    // Validate date and time
    if (empty($date) || empty($time)) {
        echo "<script>alert('Date and time are required'); window.location='babysitting.php';</script>";
        exit();
    }
    $scheduledAt = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
    if (!$scheduledAt) {
        echo "<script>alert('Invalid date or time format. Please try again.'); window.location='babysitting.php';</script>";
        exit();
    }
    $scheduledAt->setTime((int)$scheduledAt->format('H'), (int)$scheduledAt->format('i'), 0);
    $now = new DateTime();
    if ($scheduledAt < $now) {
        echo "<script>alert('You cannot apply for a time in the past. Please pick a future date and time.'); window.location='babysitting.php';</script>";
        exit();
    }

    // Check if request exists and is pending
    $stmt = $pdo->prepare("SELECT client_id, notes FROM client_requests WHERE id = ? AND status = 'Pending'");
    $stmt->execute([$request_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$req) {
        echo "<script>alert('Request not found or already taken'); window.location='babysitting.php';</script>";
        exit();
    }
    $client_id = $req['client_id'];

    // Check if already applied
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE request_id = ? AND nurse_id = ?");
    $stmt->execute([$request_id, $nurse_id]);
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Already applied for this job'); window.location='babysitting.php';</script>";
        exit();
    }

    // Insert booking with nurse-provided date, time, notes
    $stmt = $pdo->prepare("INSERT INTO bookings (request_id, nurse_id, client_id, date, time, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'Applied')");
    $stmt->execute([$request_id, $nurse_id, $client_id, $date, $time, $req['notes']]);

    // Send notification to client
    require_once 'send_notification.php';
    sendNotification($client_id, 'New Nurse Application', 'A nurse has applied for your request. Date: ' . $date . ', Time: ' . $time . ', Notes: ' . $req['notes'], 'booking');

    // Update request status
    $stmt = $pdo->prepare("UPDATE client_requests SET status = 'Applied', nurse_id = ? WHERE id = ?");
    $stmt->execute([$nurse_id, $request_id]);

    echo "<script>alert('Application submitted successfully'); window.location='nurse_bookings.php';</script>";
} else {
    header('Location: login.php');
}
?>
