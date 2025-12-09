<?php
// setup_database.php — One-click database setup and sanity fixer
// Run via CMD: php -f C:\xampp\htdocs\nurse\setup_database.php

require_once 'db.php';

function hasTable(PDO $pdo, string $name): bool {
    try { $pdo->query("DESCRIBE `{$name}`"); return true; } catch (Exception $e) { return false; }
}
function hasColumn(PDO $pdo, string $table, string $col): bool {
    try {
        $cols = $pdo->query("DESCRIBE `{$table}`")->fetchAll(PDO::FETCH_COLUMN, 0);
        return in_array($col, $cols, true);
    } catch (Exception $e) { return false; }
}
function say($msg){ echo $msg, PHP_EOL; }

say('=== Nurse Portal DB Setup ===');

// USERS
say('> Ensuring users table...');
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    role ENUM('Client','Nurse','Admin') NOT NULL DEFAULT 'Client',
    approval_status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    skills TEXT NULL,
    service VARCHAR(100) NULL,
    salary INT DEFAULT 0,
    experience VARCHAR(50) NULL,
    location VARCHAR(150) NULL,
    bio TEXT NULL,
    certificate_path VARCHAR(255) DEFAULT NULL,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (!hasColumn($pdo,'users','approval_status')) {
    $pdo->exec("ALTER TABLE users ADD COLUMN approval_status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending' AFTER role");
}
if (!hasColumn($pdo,'users','certificate_path')) {
    $pdo->exec("ALTER TABLE users ADD COLUMN certificate_path VARCHAR(255) DEFAULT NULL AFTER bio");
}
if (!hasColumn($pdo,'users','average_rating')) $pdo->exec("ALTER TABLE users ADD COLUMN average_rating DECIMAL(3,2) DEFAULT 0.00");
if (!hasColumn($pdo,'users','review_count'))  $pdo->exec("ALTER TABLE users ADD COLUMN review_count INT DEFAULT 0");
$pdo->exec("UPDATE users SET approval_status='Approved' WHERE approval_status IS NULL OR (approval_status='Pending' AND role <> 'Nurse')");
if (!hasColumn($pdo,'users','role'))          $pdo->exec("ALTER TABLE users ADD COLUMN `role` ENUM('Client','Nurse','Admin') NOT NULL DEFAULT 'Client'");

// CLIENT REQUESTS
say('> Ensuring client_requests table...');
$pdo->exec("CREATE TABLE IF NOT EXISTS client_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    service_type ENUM('Babysitting','ElderlyCare','PatientCare','LabTesting') NOT NULL,
    patient_name VARCHAR(120),
    age VARCHAR(20),
    address VARCHAR(255),
    phone VARCHAR(30),
    notes TEXT,
    date DATE NULL,
    time TIME NULL,
    status ENUM('Pending','Applied','Accepted','Cancelled') DEFAULT 'Pending',
    nurse_id INT NULL,
    items_json TEXT NULL,
    estimated_cost DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_service (service_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
foreach ([['status', "ALTER TABLE client_requests ADD COLUMN status ENUM('Pending','Applied','Accepted','Cancelled') DEFAULT 'Pending'"],
          ['nurse_id', "ALTER TABLE client_requests ADD COLUMN nurse_id INT NULL"],
          ['date',     "ALTER TABLE client_requests ADD COLUMN date DATE NULL"],
          ['time',     "ALTER TABLE client_requests ADD COLUMN time TIME NULL"],
          ['items_json',"ALTER TABLE client_requests ADD COLUMN items_json TEXT NULL"],
          ['estimated_cost',"ALTER TABLE client_requests ADD COLUMN estimated_cost DECIMAL(10,2) NULL"]] as $pair){
    if (!hasColumn($pdo,'client_requests',$pair[0])) { $pdo->exec($pair[1]); }
}
// Normalize service_type to include LabTesting and fix blank rows caused by missing enum value
try {
    $col = $pdo->query("SHOW COLUMNS FROM client_requests LIKE 'service_type'")->fetch(PDO::FETCH_ASSOC);
    $type = strtolower($col['Type'] ?? '');
    $needsAlter = false;
    if ($type === '') {
        $needsAlter = true;
    } elseif (strpos($type, 'enum(') === 0) {
        if (stripos($type, 'labtesting') === false) $needsAlter = true;
    } elseif (strpos($type, 'varchar') === 0) {
        $needsAlter = true;
    }
    if ($needsAlter) {
        $pdo->exec("UPDATE client_requests SET service_type='LabTesting' WHERE service_type IS NULL OR service_type=''");
        $pdo->exec("ALTER TABLE client_requests MODIFY service_type ENUM('Babysitting','ElderlyCare','PatientCare','LabTesting') NOT NULL");
    }
} catch (Exception $e) { /* ignore */ }

// BOOKINGS
say('> Ensuring bookings table...');
$pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NULL,
    nurse_id INT NOT NULL,
    client_id INT NOT NULL,
    date DATE,
    time TIME,
    notes TEXT,
    status ENUM('Applied','Accepted','Rejected','Cancelled') DEFAULT 'Applied',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_nurse (nurse_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (!hasColumn($pdo,'bookings','status'))     $pdo->exec("ALTER TABLE bookings ADD COLUMN status ENUM('Applied','Accepted','Rejected','Cancelled') DEFAULT 'Applied'");
if (!hasColumn($pdo,'bookings','request_id')) $pdo->exec("ALTER TABLE bookings ADD COLUMN request_id INT NULL");

// REVIEWS
say('> Ensuring reviews table...');
$pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT NOT NULL,
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reviewee (reviewee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// FEEDBACK
say('> Ensuring feedback table...');
$pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nurse_id INT NOT NULL,
    client_id INT NOT NULL,
    rating VARCHAR(20) NOT NULL,
    review TEXT NOT NULL,
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nurse (nurse_id),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (!hasColumn($pdo,'feedback','is_anonymous')) {
    $pdo->exec("ALTER TABLE feedback ADD COLUMN is_anonymous TINYINT(1) NOT NULL DEFAULT 0 AFTER review");
}

// SERVICES
say('> Ensuring services table...');
$pdo->exec("CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    base_price DECIMAL(10,2) DEFAULT 0.00,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// SERVICE ITEMS
say('> Ensuring service_items table...');
$pdo->exec("CREATE TABLE IF NOT EXISTS service_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_slug VARCHAR(100) NOT NULL,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (service_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// NOTIFICATIONS
say('> Ensuring notifications table...');
$pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('booking','request','system','payment') DEFAULT 'system',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed default services if empty
say('> Seeding base services (if empty)...');
$count = (int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
if ($count === 0) {
    $ins = $pdo->prepare("INSERT INTO services (name,slug,base_price,active) VALUES (?,?,?,1)");
    foreach ([
        ['Elderly Care','ElderlyCare', 0],
        ['Patient Care','PatientCare', 0],
        ['Babysitting','Babysitting', 0],
        ['Lab Testing','LabTesting', 0],
    ] as $row) { $ins->execute($row); }
    say('  - Seeded default services');
} else {
    say('  - Services already present');
}

say('=== Done. You can now run seeders if needed. ===');

?>
