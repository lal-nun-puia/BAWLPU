<?php
session_start();
require_once 'db.php';

$logged_in = isset($_SESSION['user_id']);
$user_id = $logged_in ? $_SESSION['user_id'] : null;
$user_role = $logged_in ? $_SESSION['user_role'] : null;
$user_name = $logged_in ? $_SESSION['user_name'] : null;

if (!$logged_in) {
    header('Location: login.php');
    exit;
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $nurse_id = intval($_POST['nurse_id'] ?? 0);
    $rating_num = intval($_POST['rating'] ?? 0);
    $review = trim($_POST['review'] ?? '');
    $profile_type = $_POST['profile_type'] ?? 'name';
    $profile_type = in_array($profile_type, ['name', 'anonymous'], true) ? $profile_type : 'name';
    $is_anonymous = $profile_type === 'anonymous' ? 1 : 0;

    $rating_map = [
        1 => '1-Poor',
        2 => '2-Fair',
        3 => '3-Good',
        4 => '4-Very Good',
        5 => '5-Excellent'
    ];

    if ($nurse_id <= 0 || !isset($rating_map[$rating_num]) || empty($review)) {
        $message = "All fields are required and rating must be between 1 and 5.";
    } else {
        try {
            // Check if nurse exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'Nurse' AND approval_status = 'Approved'");
            $stmt->execute([$nurse_id]);
            if (!$stmt->fetch()) {
                $message = "Selected nurse not found.";
            } else {
                // Insert feedback
                $stmt = $pdo->prepare("INSERT INTO feedback (nurse_id, client_id, rating, review, is_anonymous) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nurse_id, $user_id, $rating_map[$rating_num], $review, $is_anonymous]);

                // Update review count for the nurse
                $stmt = $pdo->prepare("UPDATE users SET review_count = review_count + 1 WHERE id = ?");
                $stmt->execute([$nurse_id]);

                $message = "Feedback submitted successfully!";
            }
        } catch (Exception $e) {
            $message = "Error submitting feedback. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback - Nurse & Healthworker Portal</title>
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
}
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: var(--card);
    color: var(--text);
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: var(--shadow);
    border-bottom: 1px solid rgba(0,0,0,0.06);
}
header h1 {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
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
    transition: transform 0.3s, background 0.3s;
}
.hamburger:hover { transform: scale(1.05); }
.theme-toggle-btn {
    border: 1px solid var(--primary);
    border-radius: 999px;
    padding: 10px 16px;
    background: var(--card);
    color: var(--primary);
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s ease, color 0.3s ease;
}
.theme-toggle-btn:hover {
    background: var(--primary);
    color: #fff;
    transform: translateY(-1px);
}
.header-actions { display:flex; gap:10px; align-items:center; margin-left:auto; }
.back-btn {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 10px 16px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.2s ease, transform 0.1s ease;
}
.back-btn:hover { background: var(--primary-700); transform: translateY(-1px); }
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
    overflow-y: auto;
    padding-bottom: 24px;
}
nav a {
    color: var(--text);
    text-decoration: none;
    padding: 18px 25px;
    font-size: 18px;
    font-weight: 500;
    transition: all 0.3s;
    border-bottom: 1px solid rgba(0,0,0,0.06);
}
nav a:hover {
    background: rgba(0,0,0,0.04);
    transform: translateX(10px);
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

.feedback-section {
    max-width: 800px;
    margin: 100px auto 50px;
    background: var(--card);
    padding: 40px;
    border-radius: 25px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(0,0,0,0.06);
}
.feedback-section h2 {
    color: var(--primary);
    margin-bottom: 30px;
    font-size: 32px;
    text-align: center;
}
.feedback-section p {
    margin-bottom: 20px;
    font-size: 18px;
}

.form-section {
    background: var(--card);
    padding: 30px;
    border-radius: 20px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(0,0,0,0.06);
    margin-bottom: 30px;
}
.form-section h3 {
    color: var(--primary);
    margin-bottom: 25px;
    text-align: center;
    font-size: 24px;
}
.form-section form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.form-section select,
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
.form-section select:focus,
.form-section input:focus,
.form-section textarea:focus {
    border-color: var(--primary-700);
    box-shadow: 0 0 0 3px rgba(0,0,0,0.06);
    outline: none;
}
.form-section textarea { min-height: 120px; }
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
    background: linear-gradient(90deg, var(--primary-700) 0%, var(--primary) 100%);
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
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

.feedback-list {
    background: var(--card);
    padding: 30px;
    border-radius: 20px;
    box-shadow: var(--shadow);
    border: 1px solid rgba(0,0,0,0.06);
}
.feedback-list h3 {
    color: var(--primary);
    margin-bottom: 20px;
    font-size: 24px;
}
.feedback-item {
    background: linear-gradient(135deg, color-mix(in srgb, var(--primary) 8%, transparent) 0%, color-mix(in srgb, var(--primary-700) 10%, transparent) 100%);
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}
.feedback-item p {
    margin: 5px 0;
    color: var(--text);
}
.rating {
    color: #ffc107;
    font-size: 18px;
}

@media (max-width: 768px) {
    header { padding: 15px 20px; }
    header h1 { font-size: 24px; }
    nav { width: 250px; }
    .feedback-section { margin: 80px 20px 30px; padding: 20px; }
    .form-section { padding: 20px; }
}
</style>
</head>
<body>

<header>
<div class="hamburger" onclick="toggleNav()">☰</div>
<h1>Nurse & Healthworker Service Portal</h1>
<div class="header-actions">
  <button type="button" class="back-btn" onclick="window.history.back()">Back</button>
  <button id="themeToggle" type="button" class="theme-toggle-btn">Dark Mode</button>
</div>
</header>

<div class="overlay" id="overlay" onclick="closeNav()"></div>

<nav id="navMenu">
    <a href="index.php">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="profile.php">Profile</a>
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

<section class="feedback-section">
<?php if($user_role == 'Client'): ?>
<h2>Submit Feedback</h2>
<p>Share your experience with the nurses you've worked with.</p>

<div class="form-section">
<h3>Give Feedback</h3>
<form method="POST" action="">
<select name="nurse_id" required>
<option value="">Select a Nurse</option>
<?php
// Fetch nurses that the client has booked
$stmt = $pdo->prepare("SELECT DISTINCT u.id, u.name FROM users u JOIN bookings b ON u.id = b.nurse_id WHERE b.client_id = ? AND b.status = 'Accepted' AND u.approval_status = 'Approved'");
$stmt->execute([$user_id]);
$nurses = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($nurses as $nurse) {
    echo "<option value='{$nurse['id']}'>{$nurse['name']}</option>";
}
?>
</select>
<label for="rating">Rating (1-5):</label>
<input type="number" name="rating" min="1" max="5" required>
<label for="profile_type">Profile Type:</label>
<select name="profile_type" id="profile_type" required>
    <?php $selected_profile = $_POST['profile_type'] ?? 'name'; ?>
    <option value="name" <?= $selected_profile === 'name' ? 'selected' : '' ?>>Show my name</option>
    <option value="anonymous" <?= $selected_profile === 'anonymous' ? 'selected' : '' ?>>Anonymous</option>
</select>
<textarea name="review" placeholder="Write your review here..." required></textarea>
<button type="submit" name="submit_feedback">Submit Feedback</button>
</form>
<?php if($message) echo "<p class='message'>$message</p>"; ?>
</div>

<?php elseif($user_role == 'Nurse'): ?>
<h2>Your Feedback</h2>
<p>View feedback from your clients.</p>

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
        $rating_text = substr($fb['rating'], 2); // Extract the rating word (e.g., "Excellent")
        echo "<p><strong>Rating:</strong> {$rating_text}</p>";
        echo "<p><strong>Review:</strong> {$fb['review']}</p>";
        echo "<p><strong>Date:</strong> {$fb['created_at']}</p>";
        echo "</div>";
    }
} else {
    echo "<p>No feedback received yet.</p>";
}
?>
</div>

<?php endif; ?>
</section>

<script>
const nav = document.getElementById('navMenu');
const overlay = document.getElementById('overlay');
function toggleNav(){ nav.classList.toggle('active'); overlay.style.display=(nav.classList.contains('active')?'block':'none'); }
function closeNav(){ nav.classList.remove('active'); overlay.style.display='none'; }

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


