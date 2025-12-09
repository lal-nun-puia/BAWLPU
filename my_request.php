<?php
session_start();
require_once "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Client'){
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT cr.*, b.id AS booking_id, u.name AS nurse_name
                       FROM client_requests cr
                       LEFT JOIN bookings b ON cr.id = b.request_id
                       LEFT JOIN users u ON b.nurse_id = u.id
                       WHERE cr.client_id = ? ORDER BY cr.id DESC");
$stmt->execute([$client_id]);
$requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<title>My Requests</title>
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
<style>
body{font-family:Poppins;background:#fafafa;padding:20px;}
.container{max-width:800px;margin:auto;}
table{width:100%;border-collapse:collapse;background:white;}
th,td{border:1px solid #ddd;padding:10px;text-align:left;}
th{background:#00796b;color:white;}
a.btn{padding:6px 10px;background:red;color:white;text-decoration:none;border-radius:5px;}
</style>
</head>
<body>
<div class="container">
<h2>My Requests</h2>
<button onclick="window.history.back()" style="background:#004d40; color:white; border:none; padding:10px 20px; border-radius:10px; cursor:pointer; font-size:14px; margin-bottom:20px;">Back</button>
<table>
<tr>
<th>Service</th><th>Name</th><th>Age</th><th>Phone</th><th>Status</th><th>Nurse Applied</th><th>Action</th>
</tr>

<?php foreach($requests as $r): ?>
<tr>
<td><?= $r['service_type']; ?></td>
<td><?= $r['name']; ?></td>
<td><?= $r['age']; ?></td>
<td><?= $r['phone']; ?></td>
<td><?= $r['status']; ?></td>
<td><?= $r['nurse_name'] ?: 'None'; ?></td>
<td>
<?php if($r['status']=="Pending"): ?>
<a class="btn" href="cancel_request.php?id=<?= $r['id']; ?>">Cancel</a>
<?php elseif($r['status']=="Applied" && $r['booking_id']): ?>
<a class="btn" href="accept_request.php?id=<?= $r['booking_id']; ?>" style="background:green;">Accept Application</a>
<a class="btn" href="reject_request.php?id=<?= $r['booking_id']; ?>" style="background:orange;">Reject</a>
<?php elseif($r['status']=="Accepted" && $r['booking_id']): ?>
<a class="btn" href="#" onclick="showReviewForm(<?= $r['booking_id']; ?>, '<?= htmlspecialchars($r['nurse_name']); ?>')" style="background:blue;">Leave Review</a>
<?php else: ?> -
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- Review Modal -->
<div id="reviewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div style="background:#fff; margin:10% auto; padding:20px; border-radius:10px; width:90%; max-width:500px;">
        <span style="float:right; font-size:28px; cursor:pointer;" onclick="closeReviewModal()">&times;</span>
        <h2>Leave a Review</h2>
        <form id="reviewForm" action="submit_review.php" method="POST">
            <input type="hidden" name="booking_id" id="reviewBookingId">
            <p>Reviewing: <span id="reviewNurseName"></span></p>
            <label for="rating">Rating:</label><br>
            <select name="rating" id="rating" required>
                <option value="">Select rating</option>
                <option value="5">⭐⭐⭐⭐⭐ (5 stars)</option>
                <option value="4">⭐⭐⭐⭐ (4 stars)</option>
                <option value="3">⭐⭐⭐ (3 stars)</option>
                <option value="2">⭐⭐ (2 stars)</option>
                <option value="1">⭐ (1 star)</option>
            </select><br><br>
            <label for="review_text">Review (optional):</label><br>
            <textarea name="review_text" id="review_text" rows="4" cols="50" placeholder="Share your experience..."></textarea><br><br>
            <button type="submit">Submit Review</button>
        </form>
    </div>
</div>

<script>
function showReviewForm(bookingId, nurseName) {
    document.getElementById('reviewBookingId').value = bookingId;
    document.getElementById('reviewNurseName').textContent = nurseName;
    document.getElementById('reviewModal').style.display = 'block';
}

function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
    document.getElementById('reviewForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('reviewModal');
    if (event.target == modal) {
        closeReviewModal();
    }
}
</script>
</body>
</html>
