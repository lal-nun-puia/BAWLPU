<?php
require 'db.php';

try {
    // Create reviews table
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        reviewer_id INT NOT NULL,
        reviewee_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_review (booking_id, reviewer_id)
    )";

    $pdo->exec($sql);
    echo "Reviews table created successfully!\n";

    // Add average_rating column to users table
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('average_rating', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00");
        echo "Added average_rating column to users table\n";
    }

    // Add review_count column to users table
    if (!in_array('review_count', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN review_count INT DEFAULT 0");
        echo "Added review_count column to users table\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
