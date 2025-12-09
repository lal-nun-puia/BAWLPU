<?php
session_start();
require_once 'db.php';
require_once 'send_notification.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
  header('Location: login.php');
  exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['send'])) {
  $role = $_POST['role'] ?? '';
  $title = trim($_POST['title'] ?? '');
  $body = trim($_POST['body'] ?? '');
  if ($title === '' || $body === '') {
    $message = 'Title and message are required';
  } else {
    $sql = "SELECT id FROM users" . ($role?" WHERE role=?":"");
    $stmt = $pdo->prepare($sql);
    $stmt->execute($role?[$role]:[]);
    $count=0;
    while($uid = $stmt->fetchColumn()){
      if (sendNotification($uid, $title, $body, 'system')) $count++;
    }
    $message = "Sent to $count users.";
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Broadcast Notifications</title>
  <link rel="stylesheet" href="assets/styles.css">
  <script src="assets/theme.js"></script>
</head>
<body>
<?php include 'partials/nav.php'; ?>
<div class="container" style="max-width:900px;margin:80px auto 20px;">
  <h2>Broadcast Notifications</h2>
  <?php if($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <div class="card">
    <form method="post" class="form-grid">
      <div>
        <label>Audience Role</label>
        <select name="role">
          <option value="">All</option>
          <option value="Client">Clients</option>
          <option value="Nurse">Nurses</option>
          <option value="Admin">Admins</option>
        </select>
      </div>
      <div><label>Title</label><input name="title" required></div>
      <div style="grid-column:1/-1"><label>Message</label><textarea name="body" required></textarea></div>
      <div class="right"><button class="btn btn-primary" name="send" type="submit">Send</button></div>
    </form>
  </div>
</div>
<script src="assets/nav.js"></script>
</body>
</html>

