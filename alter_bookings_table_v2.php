<?php
require 'db.php';

try {
    // Drop the existing bookings table
    $pdo->exec("DROP TABLE IF EXISTS bookings");

    // Create the new bookings table with the columns used by the app
    $pdo->exec("
        CREATE TABLE bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id INT NULL,
            client_id INT NOT NULL,
            nurse_id INT NOT NULL,
            date DATE NOT NULL,
            time TIME NOT NULL,
            notes TEXT,
            status ENUM('Applied','Accepted','Rejected','Cancelled') DEFAULT 'Applied',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (request_id) REFERENCES client_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES users(id),
            FOREIGN KEY (nurse_id) REFERENCES users(id)
        )
    ");

    echo "Bookings table recreated with required columns.";
} catch (Exception $e) {
    echo "Error altering table: " . $e->getMessage();
}
?>

