<?php
require 'db.php';
session_start();
$_SESSION['user'] = ['user_id' => 1, 'role' => 'Client'];
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'Client';

// Simulate POST to booking.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$input = json_decode('{"nurse_id":"11","booking_date":"2023-12-01","booking_time":"10:00","notes":"Test booking"}', true);
$nurse_id = intval($input['nurse_id'] ?? 0);
$booking_date = trim($input['booking_date'] ?? '');
$booking_time = trim($input['booking_time'] ?? '');
$notes = trim($input['notes'] ?? '');
$client_id = $_SESSION['user']['user_id'];

if (!$nurse_id || !$booking_date || !$booking_time) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO bookings (request_id, nurse_id, client_id, date, time, notes, status) VALUES (NULL, ?, ?, ?, ?, ?, 'Applied')");
    $stmt->execute([$nurse_id, $client_id, $booking_date, $booking_time, $notes]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
