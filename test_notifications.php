<?php
require 'db.php';
require 'send_notification.php';

// Test notification sending
$user_id = 1; // Assuming user exists
$result = sendNotification($user_id, 'Test Notification', 'This is a test message', 'system');
echo 'Send notification result: ' . ($result ? 'Success' : 'Failed') . PHP_EOL;

// Test unread count
$count = getUnreadNotificationsCount($user_id);
echo 'Unread count: ' . $count . PHP_EOL;

// Check if notification was inserted
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);
if ($notification) {
    echo 'Last notification: ' . $notification['title'] . ' - ' . $notification['message'] . PHP_EOL;
} else {
    echo 'No notifications found' . PHP_EOL;
}
?>
