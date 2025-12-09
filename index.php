 <?php
session_start();
require_once 'db.php'; // Make sure db.php defines $pdo as PDO connection

$logged_in = isset($_SESSION['user_id']);
$user_id = $logged_in ? $_SESSION['user_id'] : null;
$user_role = $logged_in ? $_SESSION['user_role'] : null;
$user_name = $logged_in ? $_SESSION['user_name'] : null;

// Fetch logged-in user's data if logged in
if($logged_in){
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch applied jobs count for nurses (fallback if status column doesn't exist)
    if($user_role == 'Nurse'){
        $hasStatus = false;
        try {
            $colCheck = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'status'");
            $hasStatus = (bool)$colCheck->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $hasStatus = false;
        }

        $sql = $hasStatus
            ? "SELECT COUNT(*) FROM bookings WHERE nurse_id = ? AND status = 'Applied'"
            : "SELECT COUNT(*) FROM bookings WHERE nurse_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $applied_jobs_count = (int)$stmt->fetchColumn();
    }
}

// Handle registration
$register_msg = "";
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $role = $_POST['role'];
    $skills = trim($_POST['skills'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $salary = intval($_POST['salary'] ?? 0);
    $experience = trim($_POST['experience'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if(empty($name) || empty($email) || empty($password) || empty($phone) || empty($address) || empty($role)){
        $register_msg = "All required fields must be filled.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $register_msg = "Invalid email format.";
    } elseif(strlen($password) < 6){
        $register_msg = "Password must be at least 6 characters.";
    } elseif(!preg_match("/^[0-9]{10}$/", $phone)){
        $register_msg = "Phone must be 10 digits.";
    } elseif($role == 'Nurse' && (empty($skills) || empty($service) || $salary <= 0 || empty($experience) || empty($location))){
        $register_msg = "All nurse-specific fields are required for Nurse role.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        if($stmt->rowCount() > 0){
            $register_msg = "Email already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, role, skills, service, salary, experience, location, bio) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name,$email,$hashed_password,$phone,$address,$role,$skills,$service,$salary,$experience,$location,$bio]);
            $register_msg = "Registration successful! Please login.";
        }
    }
}

// Load active services for homepage cards (admin-controlled via admin_services.php)
$active_services = [];
try {
    $stmt = $pdo->query("SELECT name, slug, COALESCE(base_price,0) AS base_price FROM services WHERE active = 1 ORDER BY id DESC");
    $active_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* ignore on homepage */ }

if (!function_exists('normalize_service_key')) {
    function normalize_service_key($value) {
        return strtolower(preg_replace('/[^a-z0-9]/', '', (string)$value));
    }
}

$default_services = [
    'ElderlyCare' => [
        'name' => 'Elderly Care',
        'img' => 'elderlycare.jpg',
        'key' => normalize_service_key('Elderly Care')
    ],
    'PatientCare' => [
        'name' => 'Patient Care',
        'img' => 'patientcare.jpg',
        'key' => normalize_service_key('Patient Care')
    ],
    'Babysitting' => [
        'name' => 'Babysitting',
        'img' => 'babysitting.jpg',
        'key' => normalize_service_key('Babysitting')
    ],
    'LabTesting' => [
        'name' => 'Lab Testing',
        'img' => 'labtech.jpg',
        'key' => normalize_service_key('Lab Testing')
    ],
];

$active_service_map = [];
foreach ($active_services as $srv) {
    $active_service_map[$srv['slug']] = $srv;
}

$service_counts = [];
try {
    $stmt = $pdo->query("SELECT service, COUNT(*) AS total FROM users WHERE role = 'Nurse' AND (approval_status IS NULL OR approval_status = 'Approved') GROUP BY service");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = normalize_service_key($row['service'] ?? '');
        if ($key) {
            $service_counts[$key] = (int)$row['total'];
        }
    }
} catch (Exception $e) { /* ignore counts */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BAWLPU</title>
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
    padding: 20px 30px;
    background: var(--bg);
    color: var(--text);
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    border-bottom: 1px solid rgba(0,0,0,0.06);
}
header .brand { display:flex; align-items:center; gap:12px; }
header .header-actions { margin-left:auto; display:flex; align-items:center; gap:10px; }
header h1 {
    font-size: 28px;
    font-weight: 700;
}
.hamburger {
    width: 50px;
    height: 50px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: transparent;
    border-radius: 50%;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    padding: 10px;
}
.hamburger:hover {
    transform: scale(1.1);
    box-shadow: 0 0 20px rgba(255,255,255,0.5);
}
.hamburger span {
    display: block;
    height: 3px;
    width: 100%;
    background: var(--text);
    border-radius: 2px;
    transition: all 0.3s;
    margin-bottom: 6px;
}
.hamburger span:last-child {
    margin-bottom: 0;
}
.hamburger.active span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}
.hamburger.active span:nth-child(2) {
    opacity: 0;
}
.hamburger.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}
nav {
    position: fixed;
    left: -300px;
    top: 0;
    width: 280px;
    height: 100%;
    background: var(--bg);
    transition: left 0.4s ease;
    padding-top: 80px;
    display: flex;
    flex-direction: column;
    z-index: 200;
    box-shadow: 2px 0 10px rgba(0,0,0,0.15);
}
    nav a {
        color: var(--text);
        text-decoration: none;
        padding: 12px 20px;
        font-size: 15px;
        font-weight: 500;
        line-height: 1.2;
        display: flex;
        align-items: center;
        transition: all 0.3s;
        border-bottom: 1px solid rgba(0,0,0,0.08);
        position: relative;
    }
nav a:hover {
    background: rgba(0,0,0,0.06);
    transform: translateX(10px);
}
/* Navigation sections */
nav .nav-section { padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.08); margin-top: 6px; }
nav .nav-section:first-child { border-top: 0; margin-top: 0; padding-top: 0; }
nav h3 {
    margin: 8px 0 4px;
    padding: 6px 20px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--muted);
    opacity: .9;
}
.badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #ff5722;
    color: #fff;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
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

.hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 80px 20px;
    background: linear-gradient(135deg, rgba(17,24,39,0.06) 0%, rgba(2,132,199,0.12) 100%);
    border-radius: 0 0 50px 50px;
    margin-bottom: 50px;
}
/* Search bar */
/* search bar is now in nav (partials/nav.php) */
.hero h2 {
    font-size: 52px;
    margin-bottom: 20px;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}
.hero .services {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
    max-width: 1000px;
    margin-top: 40px;
}
.service-card {
    background: var(--card);
    border-radius: 25px;
    height: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    cursor: pointer;
    transition: all 0.4s ease;
    text-decoration: none;
    color: var(--text);
    overflow: hidden;
    position: relative;
}
.service-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(14,165,233,0.08) 0%, rgba(3,105,161,0.08) 100%);
    opacity: 0;
    transition: opacity 0.4s;
}
.service-card:hover::before { opacity: 1; }
.service-card img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 20px;
    transition: transform 0.4s;
}
.service-card:hover img { transform: scale(1.1); }
.service-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
}
.service-card p {
    font-size: 20px;
    font-weight: 600;
    z-index: 1;
    position: relative;
}
.service-meta {
    z-index: 1;
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 2px;
    align-items: center;
    font-size: 14px;
    color: var(--muted);
}
.service-meta .count {
    font-weight: 600;
    color: var(--primary);
}
.price-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(0,0,0,0.6);
    color: #fff;
    padding: 6px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.services-offered {
    max-width: 1200px;
    margin: 60px auto;
    padding: 40px 20px;
}
.services-offered h2 {
    text-align: center;
    font-size: 42px;
    color: var(--primary);
    margin-bottom: 40px;
    font-weight: 700;
}
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}
.service-detail {
    background: var(--card);
    padding: 30px;
    border-radius: 20px;
    box-shadow: var(--shadow);
    transition: transform 0.3s;
}

/* Price list */
.price-list { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
.price-list table { width: 100%; border-collapse: collapse; background: var(--card); border-radius: 12px; overflow: hidden; }
.price-list th, .price-list td { padding: 12px 14px; border-bottom: 1px solid rgba(0,0,0,0.06); text-align: left; }
.price-list th { background: var(--primary); color: #fff; }
.price-list tr:last-child td { border-bottom: 0; }
.service-detail:hover { transform: translateY(-5px); }
.service-detail h3 {
    color: var(--primary);
    font-size: 24px;
    margin-bottom: 15px;
    font-weight: 600;
}
.service-detail ul {
    color: var(--text);
    font-size: 16px;
    line-height: 1.7;
    padding-left: 20px;
}
.service-detail li {
    margin-bottom: 8px;
}

.profile-section {
    max-width: 800px;
    margin: 50px auto;
    background: var(--card);
    padding: 30px;
    border-radius: 25px;
    box-shadow: var(--shadow);
}
.profile-section h3 {
    color: var(--primary);
    margin-bottom: 20px;
    font-size: 28px;
}
.profile-section p {
    margin-bottom: 10px;
    font-size: 16px;
}

.form-section {
    max-width: 550px;
    margin: 50px auto;
    background: var(--card);
    padding: 30px;
    border-radius: 20px;
    box-shadow: var(--shadow);
}
.form-section h3 {
    color: var(--primary);
    margin-bottom: 25px;
    text-align: center;
    font-size: 28px;
    font-weight: 600;
}
.form-section form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.form-section input[type="text"],
.form-section input[type="email"],
.form-section input[type="password"],
.form-section input[type="number"],
.form-section select,
.form-section textarea {
    padding: 12px 18px;
    border: 2px solid var(--primary);
    border-radius: 12px;
    font-size: 16px;
    font-family: 'Poppins', sans-serif;
    transition: border-color 0.3s, box-shadow 0.3s;
    resize: vertical;
}
.form-section input:focus,
.form-section select:focus,
.form-section textarea:focus {
    border-color: var(--primary-700);
    box-shadow: 0 0 10px rgba(0,121,107,0.3);
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
    background: linear-gradient(90deg, #a5d6a7 0%, #81c784 100%);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.stats {
    max-width: 1200px;
    margin: 60px auto;
    padding: 40px 20px;
    text-align: center;
}
.stats h2 {
    font-size: 42px;
    color: var(--primary);
    margin-bottom: 40px;
    font-weight: 700;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
}
.stat-item {
    background: var(--card);
    padding: 30px;
    border-radius: 20px;
    box-shadow: var(--shadow);
    transition: transform 0.3s;
}
.stat-item:hover { transform: translateY(-5px); }
.stat-item h3 {
    font-size: 48px;
    color: var(--primary);
    margin-bottom: 10px;
    font-weight: 700;
}
.stat-item p {
    color: var(--text);
    font-size: 16px;
    font-weight: 600;
}

.testimonials {
    max-width: 1200px;
    margin: 60px auto;
    padding: 40px 20px;
    background: rgba(255,255,255,0.9);
    border-radius: 30px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
}
.testimonials h2 {
    text-align: center;
    font-size: 42px;
    color: var(--primary);
    margin-bottom: 40px;
    font-weight: 700;
}
.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}
.testimonial {
    background: var(--card);
    padding: 30px;
    border-radius: 20px;
    box-shadow: var(--shadow);
    transition: transform 0.3s;
}
.testimonial:hover { transform: translateY(-5px); }
.testimonial p {
    color: var(--text);
    font-size: 16px;
    line-height: 1.7;
    margin-bottom: 15px;
    font-style: italic;
}
.testimonial cite {
    color: var(--primary);
    font-weight: 600;
    font-size: 14px;
}
.rating {
    color: #ffc107;
    font-size: 18px;
    margin-top: 10px;
}

.cta {
    max-width: 800px;
    margin: 60px auto;
    padding: 60px 20px;
    text-align: center;
    background: var(--card);
    color: var(--text);
    border-radius: 30px;
    box-shadow: var(--shadow);
}
.cta h2 {
    font-size: 42px;
    margin-bottom: 20px;
    font-weight: 700;
}
.cta p {
    font-size: 18px;
    margin-bottom: 30px;
    opacity: 0.9;
}
.cta-button {
    display: inline-block;
    padding: 15px 30px;
    background: var(--card);
    color: var(--text);
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    font-size: 18px;
    margin: 0 10px;
    transition: all 0.3s ease;
    box-shadow: var(--shadow);
    border: 2px solid var(--primary);
}
.cta-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.3);
}
.cta-button.secondary {
    background: transparent;
    border: 2px solid #fff;
    color: #fff;
}
.cta-button.secondary:hover {
    background: #fff;
    color: var(--primary);
}

.faq {
    max-width: 1200px;
    margin: 60px auto;
    padding: 40px 20px;
}
.faq h2 {
    text-align: center;
    font-size: 42px;
    color: var(--primary);
    margin-bottom: 40px;
    font-weight: 700;
}
.faq-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}
.faq-item {
    background: var(--card);
    padding: 30px;
    border-radius: 20px;
    box-shadow: var(--shadow);
    transition: transform 0.3s;
}
.faq-item:hover { transform: translateY(-5px); }
.faq-item h3 {
    color: var(--primary);
    font-size: 20px;
    margin-bottom: 15px;
    font-weight: 600;
}
.faq-item p {
    color: var(--text);
    font-size: 16px;
    line-height: 1.7;
}

@media (max-width: 768px) {
    .hero h2 { font-size: 36px; }
    .hero .services { grid-template-columns: 1fr; gap: 20px; }
    .service-card { height: 240px; }
    .services-offered { margin: 30px 10px; padding: 20px; }
    .services-grid { grid-template-columns: 1fr; }
    .stats { margin: 30px 10px; padding: 20px; }
    .stats-grid { grid-template-columns: 1fr; }
    .testimonials { margin: 30px 10px; padding: 20px; }
    .testimonials-grid { grid-template-columns: 1fr; }
    .cta { margin: 30px 10px; padding: 40px 20px; }
    .cta h2 { font-size: 32px; }
    .cta-button { display: block; margin: 10px 0; }
    .faq { margin: 30px 10px; padding: 20px; }
    .faq-grid { grid-template-columns: 1fr; }
    header { padding: 15px 20px; }
    header h1 { font-size: 24px; }
    nav { width: 250px; }
}
</style>
</head>
<body>

<?php include 'partials/nav.php'; ?>

<section class="hero">
<?php if($user_role == 'Nurse'): ?>
<h2>Find Available Jobs in Your Area</h2>
<div class="services">
    <a href="elderlycare.php" class="service-card">
        <img src="elderlycare.jpg" alt="Elderly Care Jobs">
        <p>Elderly Care Jobs</p>
    </a>

    <a href="patientcare.php" class="service-card">
        <img src="patientcare.jpg" alt="Patient Care Jobs">
        <p>Patient Care Jobs</p>
    </a>

    <a href="babysitting.php" class="service-card">
        <img src="babysitting.jpg" alt="Babysitting Jobs">
        <p>Babysitting Jobs</p>
    </a>
    <a href="labtesting.php" class="service-card">
        <img src="labtech.jpg" alt="Lab Testing Jobs">
        <p>Lab Testing Jobs</p>
    </a>
</div>
<?php else: ?>
<h2>Connecting You with Trusted Nurses and Health Workers</h2>
<div class="services">
    <?php 
        $shownSlugs = [];
        foreach ($default_services as $slug => $meta):
            $active = $active_service_map[$slug] ?? null;
            $count = $service_counts[$meta['key']] ?? 0;
            $countText = $count > 0 ? $count . ' caregivers available' : '';
    ?>
    <a href="service.php?type=<?= htmlspecialchars($slug); ?>" class="service-card">
        <img src="<?= htmlspecialchars($meta['img']); ?>" alt="<?= htmlspecialchars($meta['name']); ?>">
        <p><?= htmlspecialchars($meta['name']); ?></p>
        <div class="service-meta">
            <span class="count"><?= htmlspecialchars($countText); ?></span>
        </div>
    </a>
    <?php 
        $shownSlugs[] = $slug;
        endforeach; 
    ?>

    <?php foreach ($active_services as $s):
        if (in_array($s['slug'], $shownSlugs, true)) continue;
        $key = normalize_service_key($s['slug']);
        $count = $service_counts[$key] ?? 0;
        $countText = $count > 0 ? $count . ' caregivers available' : '';
        $img = $default_services[$s['slug']]['img'] ?? 'patientcare.jpg';
    ?>
    <a href="service.php?type=<?= htmlspecialchars($s['slug']); ?>" class="service-card">
        <img src="<?= htmlspecialchars($img); ?>" alt="<?= htmlspecialchars($s['name']); ?>">
        <p><?= htmlspecialchars($s['name']); ?></p>
        <div class="service-meta">
            <span class="count"><?= htmlspecialchars($countText); ?></span>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<section class="section services-offered">
<h2>Our Care Services - Professional, Compassionate, and Reliable</h2>
<div class="services-grid">
    <div class="service-detail">
        <h3>🏠 Elderly Care</h3>
        <ul>
            <li>✅ Assistance with daily activities</li>
            <li>💊 Medication management</li>
            <li>❤️ Emotional support</li>
        </ul>
    </div>
    <div class="service-detail">
        <h3>🏥 Patient Care</h3>
        <ul>
            <li>🕒 Round-the-clock monitoring</li>
            <li>🩹 Wound care</li>
            <li>📋 Personalized health plans</li>
        </ul>
    </div>
    <div class="service-detail">
        <h3>👶 Babysitting</h3>
        <ul>
            <li>🎉 Engaging activities</li>
            <li>🍽️ Meal preparation</li>
            <li>🚨 Emergency response</li>
        </ul>
    </div>
    <div class="service-detail">
        <h3>💊 Home Health Assistance</h3>
        <ul>
            <li>🏋️ Physical therapy</li>
            <li>🥗 Nutritional guidance</li>
            <li>🩺 Chronic disease management</li>
        </ul>
    </div>
    <div class="service-detail">
        <h3>🩹 Post-Surgery Care</h3>
        <ul>
            <li>💉 Pain management</li>
            <li>🦽 Mobility assistance</li>
            <li>🔄 Rehabilitation</li>
        </ul>
    </div>
    <div class="service-detail">
        <h3>❤️ Chronic Illness Support</h3>
        <ul>
            <li>📅 Regular check-ups</li>
            <li>🏥 Lifestyle counseling</li>
            <li>📊 Condition monitoring</li>
        </ul>
    </div>
</div>
</section>



<section class="section stats">
<h2>Our Impact in Numbers</h2>
<div class="stats-grid">
    <div class="stat-item">
        <h3>500+</h3>
        <p>Certified Nurses & Healthworkers</p>
    </div>
    <div class="stat-item">
        <h3>10,000+</h3>
        <p>Satisfied Clients</p>
    </div>
    <div class="stat-item">
        <h3>5 Years</h3>
        <p>Of Trusted Service</p>
    </div>
    <div class="stat-item">
        <h3>98%</h3>
        <p>Client Satisfaction Rate</p>
    </div>
</div>
</section>

<section class="section testimonials">
<h2>What Our Clients Say</h2>
<div class="testimonials-grid">
    <div class="testimonial">
        <p>"Nurse te biak an nuam in an fel em a , an kut pawh a dam hle ."</p>
        <cite>- Pa Madina, Client</cite>
        <div class="rating">★★★★★</div>
    </div>
    <div class="testimonial">
        <p>"Excellent post-surgery care. The healthworker helped with my recovery and provided great support."</p>
        <cite>- Michael Chen, Client</cite>
        <div class="rating">★★★★★</div>
    </div>
    <div class="testimonial">
        <p>"Trustworthy babysitting service. My kids loved the activities and felt safe throughout."</p>
        <cite>- Emily Davis, Parent</cite>
        <div class="rating">★★★★★</div>
    </div>
</div>
</section>

<section class="cta">
<h2>Ready to Get Started?</h2>
<p>Join thousands of satisfied clients who trust us with their healthcare needs.</p>
<a href="#registration" class="cta-button">Book a Service Now</a>
<a href="login.php" class="cta-button secondary">Login to Your Account</a>
</section>

<section class="section faq">
<h2>Frequently Asked Questions</h2>
<div class="faq-grid">
    <div class="faq-item">
        <h3>How do I book a nurse?</h3>
        <p>Simply register as a client, search for available nurses, and book through our secure platform.</p>
    </div>
    <div class="faq-item">
        <h3>Are your nurses certified?</h3>
        <p>Yes, all our nurses and healthworkers are fully certified and background-checked.</p>
    </div>
    <div class="faq-item">
        <h3>What services do you offer?</h3>
        <p>We provide elderly care, patient care, babysitting, home health assistance, post-surgery care, and chronic illness support.</p>
    </div>
    <div class="faq-item">
        <h3>How much do services cost?</h3>
        <p>Prices vary based on service type and duration. Contact us for a personalized quote.</p>
    </div>
</div>
</section>

<?php if(!$logged_in): ?>
<section class="form-section" id="registration">
<h3>Register</h3>
<form method="POST" action="">
<input type="text" name="name" placeholder="Full Name" required>
<input type="email" name="email" placeholder="Email" required>
<input type="password" name="password" placeholder="Password" required minlength="6">
<input type="text" name="phone" placeholder="Phone (10 digits)" pattern="[0-9]{10}" required>
<input type="text" name="address" placeholder="Address" required>
<select name="role" required>
<option value="">Select Role</option>
<option value="Client">Client</option>
<option value="Nurse">Nurse & Healthworker</option>
</select>
<input type="text" name="skills" placeholder="Skills (for Nurse)">
<input type="text" name="service" placeholder="Service Type">
<input type="number" name="salary" placeholder="Salary per Day">
<input type="number" name="experience" placeholder="Experience in Years">
<input type="text" name="location" placeholder="Location">
<textarea name="bio" placeholder="Short Bio"></textarea>
<button type="submit" name="register">Create Account</button>
</form>
<?php if(isset($register_msg) && $register_msg) echo "<p class='message'>$register_msg</p>"; ?>
</section>
<?php endif; ?>

<script src="assets/nav.js"></script>
<script>
// nav.js provides toggleNav/closeNav

// Theme toggle with persistence
const themeBtn = document.getElementById('themeToggle');
const rootEl = document.documentElement;
const savedTheme = localStorage.getItem('theme');
if (savedTheme === 'dark') {
  rootEl.classList.add('theme-dark');
}
themeBtn?.addEventListener('click', () => {
  const isDark = rootEl.classList.toggle('theme-dark');
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
});
</script>

<footer style="padding:30px 20px; background:var(--bg); color:var(--text); margin-top:40px; border-top:1px solid rgba(0,0,0,0.06);">
  <div style="max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;align-items:start;">
    <div>
      <h3 style="margin:0 0 8px 0; font-size:18px;">BAWLPU</h3>
      <p style="margin:0; opacity:.9;">Nurse & Healthworker Service Portal</p>
    </div>
    <div>
      <h4 style="margin:0 0 6px 0; font-size:16px;">Address</h4>
      <p style="margin:0; opacity:.9;">BAWLPU</p>
      <p style="margin:0; opacity:.9;">Tanhril, Aizawl, Mizoram, 796007</p>
      <p style="margin:0; opacity:.9;">India</p>
    </div>
    <div>
      <h4 style="margin:0 0 6px 0; font-size:16px;">Contact</h4>
      <p style="margin:0; opacity:.9;">Phone: +91-8794650600</p>
      <p style="margin:0; opacity:.9;">Email: bawlpu@example.com</p>
    </div>
    <div>
      <h4 style="margin:0 0 6px 0; font-size:16px;">Credits</h4>
      <p style="margin:0; opacity:.95;">Developed by Lawmnasangzuala and Jacob Hmingmuanpuia</p>
    </div>
  </div>
  <p style="text-align:center; margin:16px 0 0 0; opacity:.9;">&copy; <?php echo date('Y'); ?> BAWLPU. All rights reserved.</p>
</footer>

</body>
</html>



