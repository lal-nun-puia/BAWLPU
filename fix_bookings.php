 <?php
require_once 'db.php';

try {
    // Check and add missing columns to bookings table
    $columns = $pdo->query("DESCRIBE bookings")->fetchAll(PDO::FETCH_COLUMN, 0);

    if (!in_array('date', $columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN date DATE");
        echo "Added 'date' column to bookings table.\n";
    }

    if (!in_array('time', $columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN time TIME");
        echo "Added 'time' column to bookings table.\n";
    }

    if (!in_array('notes', $columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN notes TEXT");
        echo "Added 'notes' column to bookings table.\n";
    }

    echo "Bookings table updated successfully!";
} catch (Exception $e) {
    echo "Error updating bookings table: " . $e->getMessage();
}
?>
