<?php
session_start();
require_once 'db.php';

$logged_in = isset($_SESSION['user_id']);
$user_role = $logged_in ? $_SESSION['user_role'] : null;

// Only clients can book nurses
if (!$logged_in || $user_role != 'Client') {
    header('Location: login.php');
    exit();
}

$nurse_id = isset($_GET['nurse_id']) ? intval($_GET['nurse_id']) : 0;

// Fetch nurse details if nurse_id provided
$nurse = null;
if ($nurse_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'Nurse' AND approval_status = 'Approved'");
    $stmt->execute([$nurse_id]);
    $nurse = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$nurse) {
        $nurse_id = 0; // invalid nurse_id
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Book Nurse - Nurse & Healthworker Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
<style>
body { font-family:'Poppins', sans-serif; margin:0; background:#e0f7fa; color:#004d40; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 30px; background:#00796b; color:#fff; position:sticky; top:0; z-index:100; }
header h1 { font-size:24px; }
.container { max-width: 600px; margin: 40px auto; background: rgba(255,255,255,0.9); padding: 25px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
h2 { color: #00796b; margin-bottom: 20px; text-align: center; }
form { display: flex; flex-direction: column; gap: 15px; }
label { font-weight: 600; }
input[type="date"], input[type="time"], textarea {
    padding: 10px 15px;
    border: 1px solid #00796b;
    border-radius: 8px;
    font-size: 16px;
    font-family: 'Poppins', sans-serif;
    resize: vertical;
}
textarea { min-height: 80px; }
button {
    background-color: #00796b;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 10px;
    font-size: 18px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
button:hover { background-color: #004d40; }
.message { margin-top: 15px; padding: 10px; border-radius: 8px; font-weight: 600; text-align: center; color: #004d40; background-color: #a5d6a7; }
.back-btn { background:#004d40; color:white; border:none; padding:10px 20px; border-radius:10px; cursor:pointer; font-size:14px; margin-bottom:20px; }
.back-btn:hover { background:#00796b; }
footer { text-align:center; padding:20px; background:#00796b; color:#fff; margin-top:40px; }
</style>
</head>
<body>

<header>
<h1>Book Nurse</h1>
</header>

<div class="container">
<button class="back-btn" onclick="window.location.href='index.php'">Back to Home</button>
<button class="back-btn" onclick="window.location.href='search_nurse.php'">Back to Search</button>

<?php if (!$nurse_id): ?>
    <p class="message">Invalid nurse selected. Please go back and select a valid nurse.</p>
<?php else: ?>
    <h2>Booking for <?php echo htmlspecialchars($nurse['name']); ?></h2>
    <form id="bookingForm">
        <input type="hidden" name="nurse_id" value="<?php echo $nurse_id; ?>" />
        <label for="booking_date">Date:</label>
        <input type="date" id="booking_date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>" />
        <label for="booking_time">Time:</label>
        <input type="time" id="booking_time" name="booking_time" required />
        <label for="notes">Notes (optional):</label>
        <textarea id="notes" name="notes" placeholder="Additional details..."></textarea>
        <button type="submit">Book Now</button>
    </form>
    <p id="responseMessage" class="message" style="display:none;"></p>
<?php endif; ?>
</div>

<script>
document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const data = {
        nurse_id: form.nurse_id.value,
        booking_date: form.booking_date.value,
        booking_time: form.booking_time.value,
        notes: form.notes.value
    };
    fetch('booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(response => response.json())
    .then(result => {
        const msgEl = document.getElementById('responseMessage');
        if (result.success) {
            msgEl.style.color = 'green';
            msgEl.textContent = 'Booking successful!';
            msgEl.style.display = 'block';
            form.reset();
        } else {
            msgEl.style.color = 'red';
            msgEl.textContent = result.error || 'Booking failed.';
            msgEl.style.display = 'block';
        }
    }).catch(() => {
        const msgEl = document.getElementById('responseMessage');
        msgEl.style.color = 'red';
        msgEl.textContent = 'Server error. Please try again later.';
        msgEl.style.display = 'block';
    });
});
</script>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Nurse & Healthworker Service Portal. Developed with care for healthcare professionals.</p>
</footer>

</body>
</html>
