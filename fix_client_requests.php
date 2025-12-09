<?php
require_once 'db.php';

try {
    // Check and add missing columns to client_requests table
    $columns = $pdo->query("DESCRIBE client_requests")->fetchAll(PDO::FETCH_COLUMN, 0);

    if (!in_array('date', $columns)) {
        $pdo->exec("ALTER TABLE client_requests ADD COLUMN date DATE");
        echo "Added 'date' column to client_requests table.\n";
    }

    if (!in_array('time', $columns)) {
        $pdo->exec("ALTER TABLE client_requests ADD COLUMN time TIME");
        echo "Added 'time' column to client_requests table.\n";
    }

    echo "client_requests table updated successfully!";
} catch (Exception $e) {
    echo "Error updating client_requests table: " . $e->getMessage();
}
?>
