<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'Client') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$nurse_id = isset($input['nurse_id']) ? (int)$input['nurse_id'] : 0;
$booking_date = trim($input['booking_date'] ?? '');
$booking_time = trim($input['booking_time'] ?? '');
$notes = trim($input['notes'] ?? '');

if ($nurse_id <= 0 || $booking_date === '' || $booking_time === '') {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Validate nurse exists
    $s = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'Nurse' AND approval_status = 'Approved'");
    $s->execute([$nurse_id]);
    if (!$s->fetchColumn()) {
        echo json_encode(['error' => 'Nurse not found']);
        exit;
    }

    $client_id = (int)$_SESSION['user']['user_id'];
    $stmt = $pdo->prepare(
        "INSERT INTO bookings (client_id, nurse_id, booking_date, booking_time, notes, status, created_at)
         VALUES (?,?,?,?,?, 'Pending', NOW())"
    );
    $stmt->execute([$client_id, $nurse_id, $booking_date, $booking_time, $notes]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
exit;
?>
