<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'Client') {
    header('Location: login.php');
    exit();
}

$client_id = $_SESSION['user']['user_id'];

// Handle booking submission via POST (from booking_form.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = trim($input['action'] ?? 'create');

    // Update existing booking date/time
    if ($action === 'update') {
        $booking_id = intval($input['booking_id'] ?? 0);
        $booking_date = trim($input['booking_date'] ?? '');
        $booking_time = trim($input['booking_time'] ?? '');

        if (!$booking_id || !$booking_date || !$booking_time) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE bookings SET date = ?, time = ? WHERE id = ? AND client_id = ? AND status = 'Applied'");
            $stmt->execute([$booking_date, $booking_time, $booking_id, $client_id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Unable to update (check status)']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        exit;
    }

    // Create booking (default)
    $nurse_id = intval($input['nurse_id'] ?? 0);
    $booking_date = trim($input['booking_date'] ?? '');
    $booking_time = trim($input['booking_time'] ?? '');
    $notes = trim($input['notes'] ?? '');

    if (!$nurse_id || !$booking_date || !$booking_time) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO bookings (request_id, nurse_id, client_id, date, time, notes, status) VALUES (NULL, ?, ?, ?, ?, ?, 'Applied')");
        $stmt->execute([$nurse_id, $client_id, $booking_date, $booking_time, $notes]);
        $booking_id = $pdo->lastInsertId();

        // Send notification to nurse
        require_once 'send_notification.php';
        sendNotification($nurse_id, 'New Booking Request', 'You have a new booking request from a client. Please check your bookings.', 'booking');

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// Fetch bookings with nurse info
$sql = "SELECT b.*, u.name AS nurse_name, u.service AS nurse_service
        FROM bookings b
        JOIN users u ON b.nurse_id = u.id
        WHERE b.client_id = ? AND u.role = 'Nurse'
        ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$client_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings - Nurse & Healthworker Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/theme.js"></script>
<style>
body { font-family:'Poppins',sans-serif; margin:0; background:var(--bg); color:var(--text); }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 24px; background:var(--card); color:var(--text); position:sticky; top:0; z-index:100; box-shadow:var(--shadow); border-bottom:1px solid rgba(0,0,0,0.06); }
header h1 { font-size:24px; margin:0; }
.header-actions { display:flex; gap:10px; }
.nav-btn { background:var(--primary); color:white; border:none; padding:10px 16px; border-radius:10px; cursor:pointer; font-size:14px; }
.nav-btn:hover { background:var(--primary-700); }
.container { max-width:900px; margin:24px auto; padding:0 20px; }
.card { background:var(--card); border-radius:15px; padding:20px; margin-bottom:15px; box-shadow:var(--shadow); border:1px solid rgba(0,0,0,0.04); }
.card h3 { margin:0 0 10px 0; }
.card p { margin:5px 0; }
.cancel-btn { background:var(--danger); color:white; border:none; padding:8px 16px; border-radius:8px; cursor:pointer; font-size:14px; margin-top:10px; }
.cancel-btn:hover { filter:brightness(0.92); }
.review-btn, .save-btn, .accept-btn, .reject-btn { background:var(--primary); color:white; border:none; padding:8px 16px; border-radius:8px; cursor:pointer; font-size:14px; margin-top:10px; }
.review-btn:hover, .save-btn:hover, .accept-btn:hover, .reject-btn:hover { background:var(--primary-700); }
.accept-btn { background: var(--accent); }
.accept-btn:hover { filter: brightness(0.95); }
.reject-btn { background: var(--warning); color:#0f172a; }
.review-modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; }
.review-modal-content { background:var(--card); margin:10% auto; padding:20px; border-radius:10px; width:90%; max-width:500px; box-shadow:var(--shadow); border:1px solid rgba(0,0,0,0.06); }
.close { float:right; font-size:28px; cursor:pointer; }
.message { margin-top:10px; padding:8px; border-radius:8px; font-weight:600; background:rgba(0,0,0,0.04); color:var(--text); }
.edit-row { display:flex; gap:10px; align-items:center; margin-top:10px; flex-wrap:wrap; }
.edit-row input[type="date"], .edit-row input[type="time"] { padding:8px 12px; border:1px solid var(--primary); border-radius:8px; background:var(--card); color:var(--text); }
footer { text-align:center; padding:20px; background:var(--card); color:var(--text); margin-top:40px; border-top:1px solid rgba(0,0,0,0.06); }
</style>
</head>
<body>

<header>
<h1>My Bookings</h1>
<div class="header-actions">
  <button class="nav-btn" onclick="window.location.href='index.php'">Home</button>
  <button class="nav-btn" onclick="window.history.back()">Back</button>
  
</div>
</header>

<div class="container">
<?php if($bookings): ?>
    <?php foreach($bookings as $b): ?>
        <div class="card">
            <h3><?= htmlspecialchars($b['nurse_name']) ?> (<?= htmlspecialchars($b['nurse_service']) ?>)</h3>
            <p><strong>Date:</strong> <?= htmlspecialchars($b['date']) ?></p>
            <p><strong>Time:</strong> <?= htmlspecialchars($b['time']) ?></p>
            <p><strong>Notes:</strong> <?= htmlspecialchars($b['notes']) ?: 'N/A' ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($b['status'] ?? 'Applied') ?></p>
            <?php if($b['status'] == 'Applied'): ?>
                <div class="edit-row">
                    <label for="date-<?= $b['id'] ?>"><strong>Edit:</strong></label>
                    <input type="date" id="date-<?= $b['id'] ?>" value="<?= htmlspecialchars($b['date']) ?>" min="<?= date('Y-m-d') ?>">
                    <input type="time" id="time-<?= $b['id'] ?>" value="<?= htmlspecialchars($b['time']) ?>">
                    <button class="save-btn" onclick="updateBooking(<?= $b['id'] ?>)">Save Changes</button>
                </div>
                <p id="msg-<?= $b['id'] ?>" class="message" style="display:none;"></p>
                <button class="cancel-btn" onclick="if(confirm('Cancel this booking?')) window.location.href='cancel_booking.php?id=<?= $b['id'] ?>'">Cancel Booking</button>
                <br><br>
                <button class="accept-btn" onclick="if(confirm('Accept this application?')) window.location.href='accept_request.php?id=<?= $b['id'] ?>'">Accept Application</button>
                <button class="reject-btn" onclick="if(confirm('Reject this application?')) window.location.href='reject_request.php?id=<?= $b['id'] ?>'">Reject</button>
            <?php elseif($b['status'] == 'Accepted'): ?>
                <button class="review-btn" onclick="showReviewForm(<?= $b['id'] ?>, '<?= htmlspecialchars($b['nurse_name']) ?>')">Leave Review</button>
                <button class="cancel-btn" onclick="if(confirm('Remove this booking from history?')) window.location.href='remove_booking.php?id=<?= $b['id'] ?>'">Remove from History</button>
            <?php elseif($b['status'] == 'Cancelled'): ?>
                <button class="cancel-btn" onclick="if(confirm('Remove this booking from history?')) window.location.href='remove_booking.php?id=<?= $b['id'] ?>'">Remove from History</button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No bookings yet.</p>
<?php endif; ?>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="review-modal">
    <div class="review-modal-content">
        <span class="close" onclick="closeReviewModal()">&times;</span>
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

function updateBooking(id) {
    const dateEl = document.getElementById('date-' + id);
    const timeEl = document.getElementById('time-' + id);
    const msgEl = document.getElementById('msg-' + id);
    const booking_date = dateEl?.value || '';
    const booking_time = timeEl?.value || '';

    fetch('booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update', booking_id: id, booking_date, booking_time })
    }).then(r => r.json())
     .then(result => {
        msgEl.style.display = 'block';
        if (result.success) {
            msgEl.style.color = 'green';
            msgEl.textContent = 'Booking updated successfully';
        } else {
            msgEl.style.color = 'red';
            msgEl.textContent = result.error || 'Update failed';
        }
     })
     .catch(() => {
        msgEl.style.display = 'block';
        msgEl.style.color = 'red';
        msgEl.textContent = 'Server error. Please try again later.';
     });
}
</script>

<footer>
<p>&copy; <?= date('Y') ?> Nurse & Healthworker Service Portal. All rights reserved.</p>
</footer>

</body>
</html>
