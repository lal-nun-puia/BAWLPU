<?php
require 'db.php';

try {
    // Drop existing foreign keys
    $pdo->exec("ALTER TABLE bookings DROP FOREIGN KEY bookings_ibfk_1");
    $pdo->exec("ALTER TABLE bookings DROP FOREIGN KEY bookings_ibfk_2");
    $pdo->exec("ALTER TABLE feedback DROP FOREIGN KEY feedback_ibfk_1");
    $pdo->exec("ALTER TABLE feedback DROP FOREIGN KEY feedback_ibfk_2");

    // Add foreign keys with ON DELETE CASCADE
    $pdo->exec("ALTER TABLE bookings ADD CONSTRAINT fk_client_id FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE");
    $pdo->exec("ALTER TABLE bookings ADD CONSTRAINT fk_nurse_id FOREIGN KEY (nurse_id) REFERENCES users(id) ON DELETE CASCADE");
    $pdo->exec("ALTER TABLE feedback ADD CONSTRAINT fk_feedback_nurse_id FOREIGN KEY (nurse_id) REFERENCES users(id) ON DELETE CASCADE");
    $pdo->exec("ALTER TABLE feedback ADD CONSTRAINT fk_feedback_client_id FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE");

    echo "All foreign keys updated successfully with ON DELETE CASCADE.";
} catch (Exception $e) {
    echo "Error updating foreign keys: " . $e->getMessage();
}
?>
