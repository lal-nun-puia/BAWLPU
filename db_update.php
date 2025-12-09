<?php
require_once 'db.php';

try {
    // Check and add missing columns to bookings table
    $columns = $pdo->query("DESCRIBE bookings")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('status', $columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN status ENUM('Applied','Accepted','Rejected','Cancelled') DEFAULT 'Applied'");
    }
    if (!in_array('request_id', $columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN request_id INT");
        $pdo->exec("ALTER TABLE bookings ADD CONSTRAINT fk_request_id FOREIGN KEY (request_id) REFERENCES client_requests(id) ON DELETE CASCADE");
    }

    // Check and add missing columns to client_requests table
    $columns = $pdo->query("DESCRIBE client_requests")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('status', $columns)) {
        $pdo->exec("ALTER TABLE client_requests ADD COLUMN status ENUM('Pending','Accepted','Cancelled') DEFAULT 'Pending'");
    }
    if (!in_array('nurse_id', $columns)) {
        $pdo->exec("ALTER TABLE client_requests ADD COLUMN nurse_id INT");
        $pdo->exec("ALTER TABLE client_requests ADD CONSTRAINT fk_nurse_id FOREIGN KEY (nurse_id) REFERENCES users(id) ON DELETE SET NULL");
    }

    echo "Database updated successfully!";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
?><?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nurse_portal";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

