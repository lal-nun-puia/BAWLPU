<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'Client') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: booking.php');
    exit();
}

$booking_id = intval($_GET['id']);
$client_id = $_SESSION['user']['user_id'];

try {
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND client_id = ? AND status IN ('Cancelled', 'Accepted')");
    $stmt->execute([$booking_id, $client_id]);

    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Booking removed from history.'); window.location='booking.php';</script>";
    } else {
        echo "<script>alert('Unable to remove booking.'); window.location='booking.php';</script>";
    }
} catch (Exception $e) {
    echo "<script>alert('Error removing booking.'); window.location='booking.php';</script>";
}
?>
