<?php
session_start();
require_once "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Client'){
    header("Location: login.php");
    exit();
}

$booking_id = $_GET['id'];
$client_id = $_SESSION['user_id'];

try {
    // Verify the booking belongs to this client
    $stmt = $pdo->prepare("SELECT b.*, cr.id AS request_id FROM bookings b
                           JOIN client_requests cr ON b.request_id = cr.id
                           WHERE b.id = ? AND cr.client_id = ? AND b.status = 'Applied'");
    $stmt->execute([$booking_id, $client_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo "<script>alert('Booking not found or already processed'); window.location='my_request.php';</script>";
        exit();
    }

    // Reject the booking
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'Rejected' WHERE id = ?");
    $stmt->execute([$booking_id]);

    // Reset request status to Pending and clear nurse_id
    $stmt = $pdo->prepare("UPDATE client_requests SET status = 'Pending', nurse_id = NULL WHERE id = ?");
    $stmt->execute([$booking['request_id']]);

    echo "<script>alert('Request rejected'); window.location='my_request.php';</script>";
} catch (Exception $e) {
    echo "<script>alert('Error: " . $e->getMessage() . "'); window.location='my_request.php';</script>";
}
?>
