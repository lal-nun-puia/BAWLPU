<?php
require_once 'db.php';

try {
    // Update client_requests where date is null, set to current date
    $stmt = $pdo->prepare("UPDATE client_requests SET date = CURDATE() WHERE date IS NULL");
    $stmt->execute();
    echo "Updated " . $stmt->rowCount() . " records with null date to current date.\n";

    // Update client_requests where time is null, set to '00:00:00'
    $stmt = $pdo->prepare("UPDATE client_requests SET time = '00:00:00' WHERE time IS NULL");
    $stmt->execute();
    echo "Updated " . $stmt->rowCount() . " records with null time to '00:00:00'.\n";

    echo "Fix completed successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
