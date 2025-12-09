<?php
// Seed Babysitting items with INR prices into service_items
require 'db.php';

try {
    // Ensure services row exists for Babysitting
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        base_price DECIMAL(10,2) DEFAULT 0.00,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $stmt = $pdo->prepare("INSERT IGNORE INTO services (name, slug, base_price, active) VALUES ('Babysitting','Babysitting',0,1)");
    $stmt->execute();

    // Ensure service_items table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_slug VARCHAR(100) NOT NULL,
        name VARCHAR(150) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (service_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $items = [
        ['Full Day Babysitting (8 hours)', 800],
        ['Half Day Babysitting (4 hours)', 400],
        ['Evening Babysitting (2 hours)', 250],
        ['Overnight Babysitting', 1200],
        ['Meal Preparation for Child', 150],
        ['Homework Assistance', 200],
        ['Playtime and Activities', 100],
        ['Diaper Changing and Hygiene', 100],
        ['Bedtime Routine', 150],
        ['Emergency Care', 300],
    ];

    // Insert if not existing for Babysitting
    $exists = $pdo->prepare('SELECT COUNT(*) FROM service_items WHERE service_slug=? AND name=?');
    $ins = $pdo->prepare('INSERT INTO service_items (service_slug, name, price, active) VALUES (?,?,?,1)');
    $added = 0; $skipped = 0;
    foreach ($items as [$name,$price]) {
        $exists->execute(['Babysitting',$name]);
        if ((int)$exists->fetchColumn() === 0) {
            $ins->execute(['Babysitting',$name,$price]);
            $added++;
        } else {
            $skipped++;
        }
    }
    echo "Seed complete. Added: $added, Skipped: $skipped\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
?>
