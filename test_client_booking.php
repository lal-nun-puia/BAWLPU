<?php
// Simulate client direct booking
session_start();
require_once 'db.php';

// Simulate client login (assuming client ID 1 exists)
$_SESSION['user'] = ['user_id' => 1, 'role' => 'Client'];
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'Client';

// Simulate JSON POST data for booking nurse ID 2
$input = [
    'nurse_id' => 2,
    'booking_date' => '2024-12-25',
    'booking_time' => '10:00',
    'notes' => 'Test direct booking'
];

echo "Simulating client direct booking...\n";
echo "Client ID: {$_SESSION['user']['user_id']}\n";
echo "Nurse ID: {$input['nurse_id']}\n";
echo "Date: {$input['booking_date']}\n";
echo "Time: {$input['booking_time']}\n";

$nurse_id = intval($input['nurse_id']);
$client_id = $_SESSION['user']['user_id'];
$booking_date = trim($input['booking_date']);
$booking_time = trim($input['booking_time']);
$notes = trim($input['notes']);

if (!$nurse_id || !$booking_date || !$booking_time) {
    echo "ERROR: Missing required fields\n";
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO bookings (request_id, nurse_id, client_id, date, time, notes, status) VALUES (NULL, ?, ?, ?, ?, ?, 'Applied')");
    $stmt->execute([$nurse_id, $client_id, $booking_date, $booking_time, $notes]);
    echo "Direct booking inserted successfully!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
