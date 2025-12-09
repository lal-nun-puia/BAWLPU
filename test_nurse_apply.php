<?php
// Simulate nurse applying to a request
session_start();
require_once 'db.php';

// Simulate nurse login (assuming nurse ID 2 exists)
$_SESSION['user_id'] = 2;
$_SESSION['user_role'] = 'Nurse';

// Simulate POST data for applying to request ID 5 (ElderlyCare, Pending)
$_POST['request_id'] = 5;

// Include apply_job.php logic
$request_id = intval($_POST['request_id']);
$nurse_id = $_SESSION['user_id'];

echo "Simulating nurse application...\n";
echo "Nurse ID: $nurse_id\n";
echo "Request ID: $request_id\n";

// Check if request exists and is pending
$stmt = $pdo->prepare("SELECT client_id FROM client_requests WHERE id = ? AND status = 'Pending'");
$stmt->execute([$request_id]);
$req = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$req) {
    echo "ERROR: Request not found or already taken\n";
    exit;
}
$client_id = $req['client_id'];
echo "Client ID: $client_id\n";

// Check if already applied
$stmt = $pdo->prepare("SELECT id FROM bookings WHERE request_id = ? AND nurse_id = ?");
$stmt->execute([$request_id, $nurse_id]);
if ($stmt->rowCount() > 0) {
    echo "ERROR: Already applied for this job\n";
    exit;
}

// Insert booking
$stmt = $pdo->prepare("INSERT INTO bookings (request_id, nurse_id, client_id, status) VALUES (?, ?, ?, 'Applied')");
$stmt->execute([$request_id, $nurse_id, $client_id]);
echo "Booking inserted\n";

// Update request status
$stmt = $pdo->prepare("UPDATE client_requests SET status = 'Applied', nurse_id = ? WHERE id = ?");
$stmt->execute([$nurse_id, $request_id]);
echo "Request status updated to Applied\n";

echo "Application successful!\n";
?>
