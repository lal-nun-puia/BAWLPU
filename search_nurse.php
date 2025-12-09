<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'Client') {
    header('Location: login.php');
    exit();
}

$client_id = $_SESSION['user']['user_id'];

// Handle search
$search_skill = trim($_GET['skill'] ?? '');
$search_location = trim($_GET['location'] ?? '');

// Fetch nurses based on search criteria
$sql = "SELECT u.*, COALESCE(u.average_rating, 0) as avg_rating, COALESCE(u.review_count, 0) as review_count FROM users u WHERE u.role = 'Nurse' AND u.approval_status = 'Approved'";
$params = [];

if ($search_skill !== '') {
    $sql .= " AND skills LIKE ?";
    $params[] = '%' . $search_skill . '%';
}
if ($search_location !== '') {
    $sql .= " AND location LIKE ?";
    $params[] = '%' . $search_location . '%';
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$nurses = $stmt->fetchAll(PDO::FETCH_ASSOC);
function buildCertUrl($base, $relative) {
    $trimmed = ltrim($relative, '/\\');
    return $trimmed;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Nurses - Nurse & Healthworker Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
<style>
body { font-family:'Poppins',sans-serif; margin:0; background:var(--bg); color:var(--text); }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 24px; background:var(--card); color:var(--text); position:sticky; top:0; z-index:100; box-shadow:var(--shadow); border-bottom:1px solid rgba(0,0,0,0.06); }
header h1 { font-size:24px; margin:0; }
.back-btn { background:var(--primary); color:white; border:none; padding:10px 16px; border-radius:10px; cursor:pointer; font-size:14px; margin:16px; }
.back-btn:hover { background:var(--primary-700); }
.container { max-width:900px; margin:24px auto; padding:0 20px; }
.search-form { background:var(--card); border-radius:15px; padding:20px; margin-bottom:20px; box-shadow:var(--shadow); border:1px solid rgba(0,0,0,0.04); }
.search-form form { display:flex; gap:10px; flex-wrap:wrap; }
.search-form input { padding:10px; border-radius:8px; border:1px solid var(--primary); background:var(--card); color:var(--text); flex:1; min-width:180px; }
.search-form button { padding:10px 14px; border-radius:8px; border:none; background:var(--primary); color:#fff; cursor:pointer; font-weight:600; }
.search-form button:hover { background:var(--primary-700); }
.card { background:var(--card); border-radius:15px; padding:20px; margin-bottom:15px; box-shadow:var(--shadow); border:1px solid rgba(0,0,0,0.04); }
.card h3 { margin:0 0 10px 0; display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.card p { margin:5px 0; color:var(--text); }
.book-btn { background:var(--primary); color:#fff; border:none; padding:8px 16px; border-radius:8px; cursor:pointer; font-size:14px; }
.book-btn:hover { background:var(--primary-700); }
footer { text-align:center; padding:20px; background:var(--card); color:var(--text); margin-top:40px; border-top:1px solid rgba(0,0,0,0.06); }
</style>
</head>
<body>

<header>
<h1>Search Nurses</h1>
</header>

<button class="back-btn" onclick="window.location.href='index.php'">Back to Home</button>

<div class="container">
<div class="search-form">
<form method="GET">
<input type="text" name="skill" placeholder="Search by skill" value="<?= htmlspecialchars($search_skill) ?>">
<input type="text" name="location" placeholder="Search by location" value="<?= htmlspecialchars($search_location) ?>">
<button type="submit">Search</button>
</form>
</div>

<?php if($nurses): ?>
    <?php foreach($nurses as $nurse): ?>
        <div class="card">
            <h3><?= htmlspecialchars($nurse['name']) ?> (<?= htmlspecialchars($nurse['service']) ?>)
                <?php if($nurse['avg_rating'] > 0): ?>
                    <span style="color:#ffa000; font-size:16px;">
                        ⭐ <?= number_format($nurse['avg_rating'], 1) ?> (<?= $nurse['review_count'] ?> reviews)
                    </span>
                <?php endif; ?>
            </h3>
            <p><strong>Skills:</strong> <?= htmlspecialchars($nurse['skills']) ?></p>
            <p><strong>Salary:</strong> ₹<?= htmlspecialchars($nurse['salary']) ?> per day</p>
            <p><strong>Experience:</strong> <?= htmlspecialchars($nurse['experience']) ?> years</p>
            <p><strong>Location:</strong> <?= htmlspecialchars($nurse['location']) ?></p>
            <p><strong>Bio:</strong> <?= htmlspecialchars($nurse['bio']) ?></p>
            <button class="book-btn" onclick="window.location.href='booking_form.php?nurse_id=<?= $nurse['id'] ?>'">Book Now</button>
            <button class="book-btn" onclick="viewComments(<?= $nurse['id'] ?>, '<?= htmlspecialchars($nurse['name']) ?>')">View Comments</button>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No nurses found matching your criteria.</p>
<?php endif; ?>
</div>

<footer>
<p>&copy; <?= date('Y') ?> Nurse & Healthworker Service Portal. All rights reserved.</p>
</footer>

<script>
function viewComments(nurseId, nurseName) {
    // Fetch comments via AJAX
    fetch('get_reviews.php?nurse_id=' + nurseId)
    .then(response => response.json())
    .then(data => {
        let comments = data.reviews.map(r => `<div><strong>${r.client_name}:</strong> ⭐${r.rating}/5 - ${r.review_text || 'No comment'} (${r.created_at})</div>`).join('');
        if (!comments) comments = 'No reviews yet.';
        alert(`Reviews for ${nurseName}:\n\n${comments}`);
    })
    .catch(() => alert('Error loading reviews'));
}
</script>

</body>
</html>
