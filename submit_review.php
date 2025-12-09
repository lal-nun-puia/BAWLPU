<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];
$booking_id = intval($_POST['booking_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$review_text = trim($_POST['review_text'] ?? '');

if (!$booking_id || $rating < 1 || $rating > 5) {
    echo "<script>alert('Invalid review data'); window.location='my_booking.php';</script>";
    exit();
}

// Verify the booking belongs to the user and is completed
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND (client_id = ? OR nurse_id = ?) AND status = 'Accepted'");
$stmt->execute([$booking_id, $user['user_id'], $user['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    echo "<script>alert('Booking not found or not eligible for review'); window.location='my_booking.php';</script>";
    exit();
}

// Determine reviewer and reviewee
$reviewer_id = $user['user_id'];
$reviewee_id = ($user['role'] == 'Client') ? $booking['nurse_id'] : $booking['client_id'];

// Check if review already exists
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ? AND reviewer_id = ?");
$stmt->execute([$booking_id, $reviewer_id]);
if ($stmt->fetch()) {
    echo "<script>alert('You have already reviewed this booking'); window.location='my_booking.php';</script>";
    exit();
}

// Insert review
$stmt = $pdo->prepare("INSERT INTO reviews (booking_id, reviewer_id, reviewee_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$booking_id, $reviewer_id, $reviewee_id, $rating, $review_text]);

// Update user's average rating and review count
$stmt = $pdo->prepare("
    UPDATE users SET
    average_rating = (SELECT AVG(rating) FROM reviews WHERE reviewee_id = users.id),
    review_count = (SELECT COUNT(*) FROM reviews WHERE reviewee_id = users.id)
    WHERE id = ?
");
$stmt->execute([$reviewee_id]);

echo "<script>alert('Review submitted successfully!'); window.location='my_booking.php';</script>";
?>
