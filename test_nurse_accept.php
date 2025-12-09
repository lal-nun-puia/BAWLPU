<?php
// Simulate nurse accepting a booking
session_start();
require_once 'db.php';

// Simulate nurse login (assuming nurse ID 2 exists)
$_SESSION['user'] = ['user_id' => 2, 'role' => 'Nurse'];

$input = ['booking_id' => 9, 'action' => 'accept']; // Accept the direct booking we just created

echo "Simulating nurse accepting booking...\n";
echo "Nurse ID: {$_SESSION['user']['user_id']}\n";
echo "Booking ID: {$input['booking_id']}\n";
echo "Action: {$input['action']}\n";

$booking_id = intval($input['booking_id']);
$action = $input['action'];

if (!$booking_id || !in_array($action, ['accept', 'reject'])) {
    echo "ERROR: Invalid parameters\n";
    exit;
}

$nurse_id = $_SESSION['user']['user_id'];

try {
    // Verify the booking belongs to this nurse
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND nurse_id = ? AND status = 'Applied'");
    $stmt->execute([$booking_id, $nurse_id]);
    if (!$stmt->fetch()) {
        echo "ERROR: Booking not found or already processed\n";
        exit;
    }

    if ($action === 'accept') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Accepted' WHERE id = ?");
        $stmt->execute([$booking_id]);
        echo "Booking accepted successfully!\n";
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Rejected' WHERE id = ?");
        $stmt->execute([$booking_id]);
        echo "Booking rejected successfully!\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
