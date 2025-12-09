<?php
session_start();
require_once "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$booking_id = $_GET['id'];

// Verify booking belongs to user
$column = ($user_role == 'Nurse') ? 'nurse_id' : 'client_id';
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id=? AND $column=?");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$booking){
    echo "<script>alert('Booking not found'); window.location='index.php';</script>";
    exit();
}

// Cancel booking: set status to Cancelled, reset request to Pending
$stmt = $pdo->prepare("UPDATE bookings SET status='Cancelled' WHERE id=?");
$stmt->execute([$booking_id]);

$stmt2 = $pdo->prepare("UPDATE client_requests SET status='Pending', nurse_id=NULL WHERE id=?");
$stmt2->execute([$booking['request_id']]);

$message = ($user_role == 'Nurse') ? 'Application cancelled' : 'Booking cancelled';
echo "<script>alert('$message'); window.location='index.php';</script>";
?>
