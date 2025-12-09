<?php
require 'db.php';
session_start();
$_SESSION['user_id'] = 11;
$_SESSION['user_role'] = 'Nurse';

// Simulate POST to apply_job.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['request_id'] = 2; // From test_check.php
$_POST['date'] = '2025-11-15';
$_POST['time'] = '17:21:00';

include 'apply_job.php';
?>
