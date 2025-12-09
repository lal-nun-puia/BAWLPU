<?php
session_start();
require_once "db.php";

// ✅ Must be logged in and must be Client
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Client'){
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['user_id'];

// ✅ Handle form submit
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $service = $_POST['service_type'];
    $name = trim($_POST['patient_name']);
    $age = trim($_POST['age']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $notes = trim($_POST['notes']);

    // Use correct column name (patient_name) and include service_type selections (incl. LabTesting)
    $sql = "INSERT INTO client_requests (client_id, service_type, patient_name, age, address, phone, date, time, notes)
            VALUES (?,?,?,?,?,?,?,?,?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$client_id, $service, $name, $age, $address, $phone, $date, $time, $notes]);

    echo "<script>alert('Request submitted successfully!'); window.location='dashboard.php';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Post Care Request</title>
<style>
body{font-family:Poppins;background:#e0f7fa;padding:20px;color:#004d40;}
form{max-width:450px;margin:auto;background:white;padding:25px;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,0.2);}
input,select,textarea{width:100%;padding:10px;margin:8px 0;border:1px solid #00796b;border-radius:8px;}
button{width:100%;background:#00796b;color:#fff;padding:12px;border:none;border-radius:8px;font-size:16px;cursor:pointer;}
button:hover{background:#004d40;}
h2{text-align:center;}
</style>
</head>
<body>

<h2>Post a Care Request</h2>
<button onclick="window.history.back()" style="background:#004d40; color:white; border:none; padding:10px 20px; border-radius:10px; cursor:pointer; font-size:14px; margin-bottom:20px;">Back</button>

<form method="POST">

<label>Service Type</label>
<select name="service_type" required>
<option value="Babysitting">Babysitting</option>
<option value="ElderlyCare">Elderly Care</option>
<option value="PatientCare">Patient Care</option>
<option value="LabTesting">Lab Testing</option>
</select>

<label>Person Name</label>
<input type="text" name="patient_name" required>

<label>Age</label>
<input type="text" name="age">

<label>Address</label>
<input type="text" name="address" required>

<label>Phone</label>
<input type="text" name="phone" required>

<label>Date</label>
<input type="date" name="date" required>

<label>Time</label>
<input type="time" name="time" required>

<label>Notes / Condition</label>
<textarea name="notes"></textarea>

<button type="submit">Submit Request</button>
</form>

</body>
</html>
