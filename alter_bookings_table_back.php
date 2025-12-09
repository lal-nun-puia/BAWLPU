<?php
require 'db.php';

try {
    // Add request_id column
    $pdo->exec("ALTER TABLE bookings ADD COLUMN request_id INT NULL");

    // Add status column
    $pdo->exec("ALTER TABLE bookings ADD COLUMN status ENUM('Applied','Accepted','Cancelled') DEFAULT 'Applied'");

    // Add foreign key for request_id
    $pdo->exec("ALTER TABLE bookings ADD CONSTRAINT fk_request_id FOREIGN KEY (request_id) REFERENCES client_requests(id) ON DELETE CASCADE");

    echo "Bookings table updated to support both direct bookings and request applications.";
} catch (Exception $e) {
    echo "Error updating table: " . $e->getMessage();
}
?>
