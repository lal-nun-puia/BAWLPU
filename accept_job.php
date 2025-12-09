<?php
// accept_job.php - Handle accept/reject actions for bookings
session_start();
require_once "db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'Nurse') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$booking_id = intval($input['booking_id'] ?? 0);
$action = $input['action'] ?? '';

if (!$booking_id || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

$nurse_id = $_SESSION['user']['user_id'];

try {
    // Verify the booking belongs to this nurse
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ? AND nurse_id = ? AND status = 'Applied'");
    $stmt->execute([$booking_id, $nurse_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Booking not found or already processed']);
        exit();
    }

    if ($action === 'accept') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Accepted' WHERE id = ?");
        $stmt->execute([$booking_id]);

        // Get client_id for notification
        $stmt = $pdo->prepare("SELECT client_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            require_once 'send_notification.php';
            sendNotification($booking['client_id'], 'Booking Accepted', 'Your booking request has been accepted by the nurse.', 'booking');
        }

        echo json_encode(['success' => true]);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'Rejected' WHERE id = ?");
        $stmt->execute([$booking_id]);

        // Get client_id for notification
        $stmt = $pdo->prepare("SELECT client_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            require_once 'send_notification.php';
            sendNotification($booking['client_id'], 'Booking Rejected', 'Your booking request has been rejected by the nurse.', 'booking');
        }

        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
