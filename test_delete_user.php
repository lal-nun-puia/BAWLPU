<?php
require 'db.php';

try {
    // Get a user with bookings
    $stmt = $pdo->prepare("SELECT u.id, u.name, COUNT(b.id) as booking_count FROM users u LEFT JOIN bookings b ON u.id = b.nurse_id OR u.id = b.client_id WHERE u.role IN ('Nurse', 'Client') GROUP BY u.id HAVING booking_count > 0 LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "No user with bookings found to test deletion.\n";
        exit;
    }

    echo "Testing deletion of user ID: {$user['id']} ({$user['name']}) with {$user['booking_count']} bookings.\n";

    // Attempt to delete the user
    $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $deleteStmt->execute([$user['id']]);

    echo "User deleted successfully. Checking if bookings were cascaded...\n";

    // Check if bookings were deleted
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE nurse_id = ? OR client_id = ?");
    $checkStmt->execute([$user['id'], $user['id']]);
    $remainingBookings = $checkStmt->fetchColumn();

    if ($remainingBookings == 0) {
        echo "Success: All associated bookings were automatically deleted via CASCADE.\n";
    } else {
        echo "Error: {$remainingBookings} bookings still exist after user deletion.\n";
    }

} catch (Exception $e) {
    echo "Error during test: " . $e->getMessage() . "\n";
}
?>
