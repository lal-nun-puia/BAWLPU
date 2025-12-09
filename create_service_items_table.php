<?php
require 'db.php';

try {
    // Create service_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_slug VARCHAR(100) NOT NULL,
        name VARCHAR(150) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (service_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add pricing capture columns to client_requests
    $columns = $pdo->query('DESCRIBE client_requests')->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('items_json', $columns)) {
        $pdo->exec("ALTER TABLE client_requests ADD COLUMN items_json TEXT NULL");
        echo "Added items_json to client_requests\n";
    }
    if (!in_array('estimated_cost', $columns)) {
        $pdo->exec("ALTER TABLE client_requests ADD COLUMN estimated_cost DECIMAL(10,2) NULL");
        echo "Added estimated_cost to client_requests\n";
    }

    echo "service_items ready\n";
} catch (Exception $e) {
    echo "Error: ".$e->getMessage()."\n";
}
?>

