<?php
require 'db.php';
session_start();
$_SESSION['user_id'] = 11;
$_SESSION['user_role'] = 'Nurse';

// Simulate GET to accept_job.php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'accept';
$_GET['booking_id'] = 1; // Assume first booking

include 'accept_job.php';
?>
