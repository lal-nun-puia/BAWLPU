<?php
// Simulate client cancelling a booking
session_start();
require_once 'db.php';

// Simulate client login
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'Client';

$booking_id = 5; // Direct booking to cancel

echo "Simulating client cancelling booking...\n";
echo "Client ID: {$_SESSION['user_id']}\n";
echo "Booking ID: $booking_id\n";

$user_id = $_SESSION['user_id'];

try {
    // Check if booking exists and belongs to user
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id=? AND (nurse_id=? OR client_id=?)");
    $stmt->execute([$booking_id, $user_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo "ERROR: Booking not found\n";
        exit;
    }

    // Cancel booking: set status to Cancelled
    $stmt = $pdo->prepare("UPDATE bookings SET status='Cancelled' WHERE id=?");
    $stmt->execute([$booking_id]);
    echo "Booking cancelled successfully!\n";

    // If it's a request-based booking, reset the request to Pending
    if ($booking['request_id']) {
        $stmt = $pdo->prepare("UPDATE client_requests SET status='Pending', nurse_id=NULL WHERE id=?");
        $stmt->execute([$booking['request_id']]);
        echo "Request reset to Pending\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
