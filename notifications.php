<?php
session_start();
require_once 'db.php';
require_once 'send_notification.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user']['user_id'];
$user_role = $_SESSION['user']['role'];

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = intval($_POST['notification_id']);
    markNotificationAsRead($notification_id, $user_id);
    header('Location: notifications.php');
    exit();
}

// Fetch notifications
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark all as read when viewing
$pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE")->execute([$user_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - Nurse & Healthworker Portal</title>
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
<style>
.container { max-width:900px; margin:20px auto; padding:0 20px; }
.notification-card { background: var(--card); border-radius:15px; padding:20px; margin-bottom:15px; box-shadow: var(--shadow); }
.notification-card.unread { border-left:5px solid var(--primary); }
.notification-card h3 { margin:0 0 10px 0; color: var(--primary); }
.notification-card p { margin:5px 0; color: var(--text); }
.notification-card .date { color:#9ca3af; font-size:14px; }
</style>
</head>
<body>
<?php include 'partials/nav.php'; ?>

<button class="back-btn" onclick="window.location.href='index.php'">Back to Home</button>

<div class="container">
<?php if($notifications): ?>
    <?php foreach($notifications as $n): ?>
        <div class="notification-card <?= !$n['is_read'] ? 'unread' : '' ?>">
            <h3><?= htmlspecialchars($n['title']) ?></h3>
            <p><?= htmlspecialchars($n['message']) ?></p>
            <p class="date">Received: <?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></p>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No notifications yet.</p>
<?php endif; ?>
</div>

<footer class="footer">
<p>&copy; <?= date('Y') ?> BAWLPU. All rights reserved.</p>
</footer>

</body>
</html>
