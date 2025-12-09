<?php
session_start();
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
require_once 'db.php';


// Get logged in user info
$user_id = $_SESSION['user']['user_id'];
$user_name = $_SESSION['user']['name'];
$user_role = $_SESSION['user']['role'];

// Fetch all nurses for search/booking/feedback
$nurses_result = $pdo->query("SELECT * FROM users WHERE role='Nurse' AND approval_status='Approved'");

// Handle booking submission
if(isset($_POST['book'])){
    $nurse_id = $_POST['nurse_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $notes = $_POST['notes'];
    $stmt = $pdo->prepare("INSERT INTO bookings (client_id, nurse_id, date, time, notes) VALUES (?,?,?,?,?)");
    $stmt->execute([$user_id,$nurse_id,$date,$time,$notes]);
    $booking_message = "Booking successful!";
}

// Handle feedback submission
if(isset($_POST['feedback'])){
    $nurse_id = $_POST['nurse_id'];
    $rating = $_POST['rating'];
    $review = $_POST['review'];
    $profile_type = $_POST['profile_type'] ?? 'name';
    $profile_type = in_array($profile_type, ['name','anonymous'], true) ? $profile_type : 'name';
    $is_anonymous = $profile_type === 'anonymous' ? 1 : 0;
    $stmt = $pdo->prepare("INSERT INTO feedback (client_id, nurse_id, rating, review, is_anonymous) VALUES (?,?,?,?,?)");
    $stmt->execute([$user_id,$nurse_id,$rating,$review,$is_anonymous]);
    $feedback_message = "Feedback submitted!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Nurse Portal</title>
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
<style>
  body { font-family:'Inter', sans-serif; background:#e0f7fa; padding:20px; }
  header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
  header h2 { color:#00796b; }
  header a { background:#00796b; color:#fff; padding:10px 20px; text-decoration:none; border-radius:8px; }
  header a:hover { background:#004d40; }
  .section { background:rgba(255,255,255,0.25); backdrop-filter:blur(15px); padding:20px; border-radius:16px; margin-bottom:30px; box-shadow:0 8px 20px rgba(0,0,0,0.2); }
  h3 { color:#00796b; margin-bottom:15px; }
  input, select, textarea, button { width:100%; padding:10px; margin-bottom:12px; border-radius:8px; border:none; }
  button { background:#00796b; color:#fff; cursor:pointer; }
  button:hover { background:#004d40; }
  .message { color:green; margin-bottom:12px; }
  .profile-info p { margin-bottom:8px; }
</style>
</head>
<body>

<header>
  <h2>Welcome, <?php echo $user_name; ?> (<?php echo $user_role; ?>)</h2>
  <div>
    <a href="index.php" style="margin-right: 10px;">Home</a>
    <a href="logout.php">Logout</a>
  </div>
</header>

<!-- Profile Section -->
<div class="section profile-info">
  <h3>Your Profile</h3>
  <?php
  $profile_result = $pdo->query("SELECT * FROM users WHERE id=$user_id");
  $profile = $profile_result->fetch(PDO::FETCH_ASSOC);
  echo "<p><strong>Name:</strong> ".$profile['name']."</p>";
  echo "<p><strong>Email:</strong> ".$profile['email']."</p>";
  echo "<p><strong>Phone:</strong> ".$profile['phone']."</p>";
  echo "<p><strong>Address:</strong> ".$profile['address']."</p>";
  if($user_role == 'Nurse'){
      echo "<p><strong>Skills:</strong> ".$profile['skills']."</p>";
      echo "<p><strong>Service:</strong> ".$profile['service']."</p>";
      echo "<p><strong>Salary:</strong> ₹".$profile['salary']."</p>";
      echo "<p><strong>Experience:</strong> ".$profile['experience']." years</p>";
      echo "<p><strong>Location:</strong> ".$profile['location']."</p>";
      echo "<p><strong>Bio:</strong> ".$profile['bio']."</p>";
  }
  ?>
</div>

<!-- Booking Section -->
<?php if($user_role == 'Client'): ?>
<div class="section">
  <h3>Book a Nurse / Healthworker</h3>
  <?php if(isset($booking_message)) echo "<p class='message'>$booking_message</p>"; ?>
  <form method="POST">
    <select name="nurse_id" required>
      <option value="">Select Nurse</option>
      <?php while($nurse = $nurses_result->fetch(PDO::FETCH_ASSOC)): ?>
        <option value="<?php echo $nurse['id']; ?>"><?php echo $nurse['name']; ?> - <?php echo $nurse['skills']; ?></option>
      <?php endwhile; ?>
    </select>
    <input type="date" name="date" required>
    <input type="time" name="time" required>
    <textarea name="notes" placeholder="Additional Notes"></textarea>
    <button type="submit" name="book">Book Now</button>
  </form>
</div>
<?php endif; ?>

<!-- Feedback Section -->
<div class="section">
  <h3>Submit Feedback</h3>
  <?php if(isset($feedback_message)) echo "<p class='message'>$feedback_message</p>"; ?>
  <form method="POST">
    <select name="nurse_id" required>
      <option value="">Select Nurse</option>
      <?php
      $nurses_result = $pdo->query("SELECT * FROM users WHERE role='Nurse' AND approval_status='Approved'");
      while($nurse = $nurses_result->fetch(PDO::FETCH_ASSOC)){
          echo "<option value='".$nurse['id']."'>".$nurse['name']."</option>";
      }
      ?>
    </select>
    <select name="rating" required>
      <option value="">Rate Service</option>
      <option value="1">1 - Poor</option>
      <option value="2">2 - Fair</option>
      <option value="3">3 - Good</option>
      <option value="4">4 - Very Good</option>
      <option value="5">
