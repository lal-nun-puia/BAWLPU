<?php
require 'db.php';

try {
    // Drop the existing bookings table
    $pdo->exec("DROP TABLE IF EXISTS bookings");

    // Create the new bookings table as per the provided CREATE TABLE
    $pdo->exec("
        CREATE TABLE bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            nurse_id INT NOT NULL,
            date DATE NOT NULL,
            time TIME NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES users(id),
            FOREIGN KEY (nurse_id) REFERENCES users(id)
        )
    ");

    echo "Bookings table altered successfully. Note: All existing booking data has been lost.";
} catch (Exception $e) {
    echo "Error altering table: " . $e->getMessage();
}
?>
