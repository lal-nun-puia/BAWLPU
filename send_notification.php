<?php
require 'db.php';

function sendNotification($user_id, $title, $message, $type = 'system') {
    global $pdo;

    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $message, $type]);
        return true;
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

function getUnreadNotificationsCount($user_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function markNotificationAsRead($notification_id, $user_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
