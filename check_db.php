<?php
require 'db.php';

try {
    $stmt = $pdo->query('DESCRIBE bookings');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Bookings table structure:\n";
    foreach($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
