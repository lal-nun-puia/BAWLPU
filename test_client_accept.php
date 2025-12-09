<?php
// Simulate client accepting nurse application to request
session_start();
require_once 'db.php';

// Simulate client login (client ID 1 owns the request)
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'Client';

$booking_id = 4; // The request-based booking for client 1, status Applied

echo "Simulating client accepting nurse application...\n";
echo "Client ID: {$_SESSION['user_id']}\n";
echo "Booking ID: $booking_id\n";

$client_id = $_SESSION['user_id'];

try {
    // Verify the booking belongs to this client
    $stmt = $pdo->prepare("SELECT b.*, cr.id AS request_id FROM bookings b
                           JOIN client_requests cr ON b.request_id = cr.id
                           WHERE b.id = ? AND cr.client_id = ? AND b.status = 'Applied'");
    $stmt->execute([$booking_id, $client_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo "ERROR: Booking not found or already processed\n";
        exit();
    }

    // Accept the booking
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'Accepted' WHERE id = ?");
    $stmt->execute([$booking_id]);
    echo "Booking status updated to Accepted\n";

    // Update request status
    $stmt = $pdo->prepare("UPDATE client_requests SET status = 'Accepted' WHERE id = ?");
    $stmt->execute([$booking['request_id']]);
    echo "Request status updated to Accepted\n";

    echo "Client acceptance successful!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
