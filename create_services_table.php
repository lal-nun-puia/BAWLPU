<?php
require 'db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        base_price DECIMAL(10,2) DEFAULT 0.00,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed defaults if empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare("INSERT INTO services (name, slug, base_price, active) VALUES (?,?,?,1)");
        foreach ([
            ['Elderly Care','ElderlyCare', 0],
            ['Patient Care','PatientCare', 0],
            ['Babysitting','Babysitting', 0],
            ['Lab Testing','LabTesting', 0],
        ] as $row) { $stmt->execute($row); }
    }

    echo "Services table ready\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

