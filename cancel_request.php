<?php
session_start();
require_once "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Client'){
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];
$client_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("UPDATE client_requests SET status='Cancelled' WHERE id=? AND client_id=?");
$stmt->execute([$id, $client_id]);

header("Location: my_request.php");
exit();
?>