<?php
// Seed PatientCare and ElderlyCare items with INR prices into service_items
require 'db.php';

function ensureTables(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        base_price DECIMAL(10,2) DEFAULT 0.00,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_slug VARCHAR(100) NOT NULL,
        name VARCHAR(150) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (service_slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function seedService(PDO $pdo, string $serviceName, string $slug, array $items) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO services (name, slug, base_price, active) VALUES (?,?,0,1)");
    $stmt->execute([$serviceName, $slug]);

    $exists = $pdo->prepare('SELECT COUNT(*) FROM service_items WHERE service_slug=? AND name=?');
    $ins = $pdo->prepare('INSERT INTO service_items (service_slug, name, price, active) VALUES (?,?,?,1)');
    $added = 0; $skipped = 0;
    foreach ($items as [$name,$price]) {
        $exists->execute([$slug,$name]);
        if ((int)$exists->fetchColumn() === 0) {
            $ins->execute([$slug,$name,$price]);
            $added++;
        } else {
            $skipped++;
        }
    }
    return [$added,$skipped];
}

try {
    ensureTables($pdo);

    $patientCareItems = [
        ['Vital Signs Monitoring', 300],
        ['Wound Dressing', 450],
        ['IV/Injection Administration', 500],
        ['Post-Operative Care Visit', 800],
        ['Catheter Care', 600],
        ['Nebulization', 250],
        ['Personal Hygiene Support', 350],
        ['Blood Sugar Monitoring', 150],
        ['Blood Pressure Check', 120],
        ['Medication Administration', 300],
    ];

    $elderlyCareItems = [
        ['Daily Assistance Visit', 400],
        ['Medication Reminder', 200],
        ['Mobility Support', 350],
        ['Companionship Session', 300],
        ['Pressure Sore Care', 500],
        ['Physiotherapy Session', 700],
        ['Dementia Care Visit', 900],
        ['Feeding Support', 250],
        ['Bathing & Grooming', 350],
        ['Night Care Visit', 1000],
    ];

    [$pcAdded,$pcSkipped] = seedService($pdo, 'Patient Care', 'PatientCare', $patientCareItems);
    [$ecAdded,$ecSkipped] = seedService($pdo, 'Elderly Care', 'ElderlyCare', $elderlyCareItems);

    echo "PatientCare - Added: $pcAdded, Skipped: $pcSkipped\n";
    echo "ElderlyCare - Added: $ecAdded, Skipped: $ecSkipped\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
?>

