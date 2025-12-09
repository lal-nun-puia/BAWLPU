 <?php
session_start();
require_once 'db.php';

$logged_in = isset($_SESSION['user_id']);
$user_id = $logged_in ? $_SESSION['user_id'] : null;
$user_role = $logged_in ? $_SESSION['user_role'] : null;

if (!$logged_in) {
    header('Location: login.php');
    exit;
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
$update_msg = "";
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $skills = trim($_POST['skills'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $salary = intval($_POST['salary'] ?? 0);
    $experience = trim($_POST['experience'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if(empty($name) || empty($email) || empty($phone) || empty($address)){
        $update_msg = "All required fields must be filled.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $update_msg = "Invalid email format.";
    } elseif(!preg_match("/^[0-9]{10}$/", $phone)){
        $update_msg = "Phone must be 10 digits.";
    } elseif($user_role == 'Nurse' && (empty($skills) || empty($service) || $salary <= 0 || empty($experience) || empty($location))){
        $update_msg = "All nurse-specific fields are required for Nurse role.";
    } else {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if($stmt->rowCount() > 0){
            $update_msg = "Email already in use by another account.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, address=?, skills=?, service=?, salary=?, experience=?, location=?, bio=? WHERE id=?");
            $stmt->execute([$name, $email, $phone, $address, $skills, $service, $salary, $experience, $location, $bio, $user_id]);
            $update_msg = "Profile updated successfully!";
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - Nurse & Healthworker Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: var(--bg);
    color: var(--text);
    line-height: 1.6;
    transition: background 0.3s ease, color 0.3s ease;
}
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 18px 26px;
    background: var(--card);
    color: var(--text);
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: var(--shadow);
    border-bottom: 1px solid rgba(0,0,0,0.06);
}
header h1 {
    font-size: 26px;
    font-weight: 700;
    flex: 1;
    margin: 0;
}
.theme-toggle-btn {
    border: 1px solid var(--primary);
    border-radius: 999px;
    padding: 10px 18px;
    background: var(--card);
    color: var(--primary);
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s ease;
}
.theme-toggle-btn:hover {
    background: var(--primary);
    color: #fff;
    transform: translateY(-1px);
}
.hamburger {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,0.08);
    background: var(--card);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: var(--text);
    cursor: pointer;
    transition: transform 0.3s;
}
.hamburger:hover { transform: scale(1.1); }
nav {
    position: fixed;
    left: -300px;
    top: 0;
    width: 280px;
    height: 100%;
    background: var(--card);
    transition: left 0.4s ease;
    padding-top: 80px;
    display: flex;
    flex-direction: column;
    z-index: 200;
    box-shadow: 2px 0 12px rgba(0,0,0,0.12);
    border-right: 1px solid rgba(0,0,0,0.06);
}
nav a {
    color: var(--text);
    text-decoration: none;
    padding: 18px 25px;
    font-size: 17px;
    font-weight: 500;
    transition: all 0.3s;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    position: relative;
}
nav a:hover {
    background: rgba(0,0,0,0.04);
    transform: translateX(10px);
}
nav a.notifications::after {
    content: attr(data-count);
    position: absolute;
    top: 10px;
    right: 10px;
    background: var(--danger);
    color: #fff;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
    font-weight: bold;
    display: none;
}
nav a.notifications.show-badge::after {
    display: block;
}
nav.active { left: 0; }
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.4);
    display: none;
    z-index: 150;
    backdrop-filter: blur(5px);
}

.profile-section,
.form-section,
.feedback-section {
    max-width: 850px;
    margin: 60px auto;
    background: var(--card);
    padding: 36px;
    border-radius: 24px;
    box-shadow: 0 12px 35px rgba(0,0,0,0.12);
    backdrop-filter: blur(12px);
}
.profile-section { margin-top: 110px; }
.feedback-section { margin-bottom: 80px; }
.profile-section h2,
.form-section h3,
.feedback-section h3 {
    color: var(--primary);
    text-align: center;
    margin-bottom: 24px;
}
.profile-section p,
.feedback-section p {
    margin-bottom: 14px;
    font-size: 17px;
    color: var(--text);
}
.profile-section strong {
    color: var(--text);
}

.edit-btn {
    background: linear-gradient(90deg, var(--primary) 0%, var(--primary-700) 100%);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 12px;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    margin-top: 20px;
}
.edit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.form-section form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.form-section input[type="text"],
.form-section input[type="email"],
.form-section input[type="number"],
.form-section textarea {
    padding: 12px 18px;
    border: 2px solid var(--primary);
    border-radius: 12px;
    font-size: 16px;
    font-family: 'Poppins', sans-serif;
    transition: border-color 0.3s, box-shadow 0.3s;
    resize: vertical;
    background: var(--card);
    color: var(--text);
}
.form-section input:focus,
.form-section textarea:focus {
    border-color: var(--primary-700);
    box-shadow: 0 0 0 3px rgba(0,0,0,0.08);
    outline: none;
}
.form-section textarea { min-height: 90px; }
.form-section button {
    background: linear-gradient(90deg, var(--primary) 0%, var(--primary-700) 100%);
    color: white;
    padding: 14px;
    border: none;
    border-radius: 12px;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
}
.form-section button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}
.message {
    margin-top: 20px;
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
    text-align: center;
    color: var(--text);
    background: linear-gradient(90deg, rgba(0,0,0,0.04) 0%, rgba(0,0,0,0.06) 100%);
    border: 1px solid rgba(0,0,0,0.08);
    background: linear-gradient(90deg, color-mix(in srgb, var(--primary) 18%, transparent) 0%, color-mix(in srgb, var(--primary-700) 22%, transparent) 100%);
    border: 1px solid color-mix(in srgb, var(--primary) 40%, transparent);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.feedback-list {
    background: var(--card);
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}
.feedback-item {
    background: linear-gradient(135deg, rgba(34,197,94,0.12) 0%, rgba(34,197,94,0.2) 100%);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    color: var(--text);
}
.feedback-item p {
    margin: 5px 0;
}
.rating {
    color: #facc15;
    font-size: 16px;
}

@media (max-width: 768px) {
    header { padding: 15px 20px; flex-wrap: wrap; }
    header h1 { font-size: 22px; }
    .theme-toggle-btn { width: 100%; order: 3; margin-top: 10px; text-align: center; }
    nav { width: 250px; }
    .profile-section,
    .form-section,
    .feedback-section { margin: 40px 20px; padding: 24px; }
    .profile-section { margin-top: 80px; }
}
</style>
</head>
<body>

<header>
<div class="hamburger" onclick="toggleNav()">☰</div>
<h1>Nurse & Healthworker Service Portal</h1>
<button id="themeToggle" type="button" class="theme-toggle-btn">Dark Mode</button>
</header>

<div class="overlay" id="overlay" onclick="closeNav()"></div>

<?php
require_once 'send_notification.php';
$unread_count = getUnreadNotificationsCount($user_id);
?>

<nav id="navMenu">
    <a href="index.php">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="profile.php">Profile</a>
    <a href="notifications.php" class="notifications <?php echo $unread_count > 0 ? 'show-badge' : ''; ?>" data-count="<?php echo $unread_count; ?>">Notifications</a>
    <?php if($user_role == 'Client'): ?>
        <a href="booking.php">Bookings</a>
        <a href="search_nurse.php">Search Nurses</a>
        <a href="feedback.php">Feedback</a>
    <?php elseif($user_role == 'Nurse'): ?>
        <a href="nurse_bookings.php">Applied Jobs</a>
        <a href="search_jobs.php">Search Jobs</a>
        <a href="feedback.php">Feedback</a>
    <?php endif; ?>
    <a href="logout.php">Logout</a>
</nav>

<section class="profile-section">
<h2>Your Profile</h2>
<p><strong>Name:</strong> <?php echo htmlspecialchars($user_data['name']); ?></p>
<p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
<p><strong>Phone:</strong> <?php echo htmlspecialchars($user_data['phone']); ?></p>
<p><strong>Address:</strong> <?php echo htmlspecialchars($user_data['address']); ?></p>
<p><strong>Role:</strong> <?php echo htmlspecialchars($user_data['role']); ?></p>
<?php if($user_role == "Nurse"): ?>
<p><strong>Skills:</strong> <?php echo htmlspecialchars($user_data['skills']); ?></p>
<p><strong>Service:</strong> <?php echo htmlspecialchars($user_data['service']); ?></p>
<p><strong>Salary:</strong> ₹<?php echo htmlspecialchars($user_data['salary']); ?> per day</p>
<p><strong>Experience:</strong> <?php echo htmlspecialchars($user_data['experience']); ?> years</p>
<p><strong>Location:</strong> <?php echo htmlspecialchars($user_data['location']); ?></p>
<p><strong>Bio:</strong> <?php echo htmlspecialchars($user_data['bio']); ?></p>
<p><strong>Reviews:</strong> <?php echo htmlspecialchars($user_data['review_count']); ?> reviews</p>
<?php endif; ?>

<?php if($user_role == 'Nurse'): ?>
<!-- Feedback Section for Nurses -->
<section class="feedback-section">
<h3>Your Feedback</h3>
<p>Reviews from your clients.</p>
<div class="feedback-list">
<?php
$stmt = $pdo->prepare("SELECT f.rating, f.review, f.is_anonymous, f.created_at, u.name as client_name FROM feedback f JOIN users u ON f.client_id = u.id WHERE f.nurse_id = ? ORDER BY f.created_at DESC");
$stmt->execute([$user_id]);
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
if($feedbacks) {
    foreach($feedbacks as $fb) {
        echo "<div class='feedback-item'>";
        $client_label = $fb['is_anonymous'] ? 'Anonymous' : $fb['client_name'];
        echo "<p><strong>Client:</strong> " . htmlspecialchars($client_label) . "</p>";
        $rating_num = intval(substr($fb['rating'], 0, 1));
        echo "<p><strong>Rating:</strong> <span class='rating'>" . str_repeat('★', $rating_num) . "</span></p>";
        echo "<p><strong>Review:</strong> {$fb['review']}</p>";
        echo "<p><strong>Date:</strong> {$fb['created_at']}</p>";
        echo "</div>";
    }
} else {
    echo "<p>No feedback received yet.</p>";
}
?>
</div>
</section>
<?php endif; ?>

<button class="edit-btn" onclick="showEditForm()">Edit Profile</button>
</section>

<section class="form-section" id="editForm" style="display: none;">
<h3>Edit Your Profile</h3>
<form method="POST" action="">
<input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
<input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
<input type="text" name="phone" placeholder="Phone (10 digits)" value="<?php echo htmlspecialchars($user_data['phone']); ?>" pattern="[0-9]{10}" title="Phone number must be exactly 10 digits" required oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0,10)">
<input type="text" name="address" placeholder="Address" value="<?php echo htmlspecialchars($user_data['address']); ?>" required>
<?php if($user_role == "Nurse"): ?>
<input type="text" name="skills" placeholder="Skills" value="<?php echo htmlspecialchars($user_data['skills']); ?>" required>
<input type="text" name="service" placeholder="Service Type" value="<?php echo htmlspecialchars($user_data['service']); ?>" required>
<input type="number" name="salary" placeholder="Salary per Day" value="<?php echo htmlspecialchars($user_data['salary']); ?>" required>
<input type="number" name="experience" placeholder="Experience in Years" value="<?php echo htmlspecialchars($user_data['experience']); ?>" required>
<input type="text" name="location" placeholder="Location" value="<?php echo htmlspecialchars($user_data['location']); ?>" required>
<textarea name="bio" placeholder="Short Bio"><?php echo htmlspecialchars($user_data['bio']); ?></textarea>
<?php endif; ?>
<button type="submit" name="update_profile">Update Profile</button>
</form>
<?php if(isset($update_msg) && $update_msg) echo "<p class='message'>$update_msg</p>"; ?>
</section>

<script>
const nav = document.getElementById('navMenu');
const overlay = document.getElementById('overlay');
function toggleNav(){ nav.classList.toggle('active'); overlay.style.display=(nav.classList.contains('active')?'block':'none'); }
function closeNav(){ nav.classList.remove('active'); overlay.style.display='none'; }
function showEditForm(){ document.getElementById('editForm').style.display='block'; window.scrollTo(0, document.getElementById('editForm').offsetTop); }

(function(){
  const toggle = document.getElementById('themeToggle');
  if(!toggle) return;
  const root = document.documentElement;
  const updateLabel = () => {
    toggle.textContent = root.classList.contains('theme-dark') ? 'Light Mode' : 'Dark Mode';
  };
  updateLabel();
  toggle.addEventListener('click', function(){
    const isDark = root.classList.toggle('theme-dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateLabel();
  });
})();
</script>

</body>
</html>
