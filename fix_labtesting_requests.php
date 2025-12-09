<?php
// Run this once to fix LabTesting requests visibility:
// php -f fix_labtesting_requests.php
//
// - Adds 'LabTesting' to client_requests.service_type enum (or converts from VARCHAR) safely.
// - Repairs existing rows that were saved with an empty service_type due to missing enum value.

require_once 'db.php';

function say($m){ echo $m, PHP_EOL; }

try {
    // Update empty/NULL service_type rows to LabTesting (these were likely failed inserts)
    $stmt = $pdo->prepare("UPDATE client_requests SET service_type = 'LabTesting' WHERE service_type IS NULL OR service_type = ''");
    $stmt->execute();
    say("> Updated blank service_type rows to 'LabTesting' (affected: ".$stmt->rowCount().")");

    // Ensure enum includes LabTesting
    $col = $pdo->query("SHOW COLUMNS FROM client_requests LIKE 'service_type'")->fetch(PDO::FETCH_ASSOC);
    $type = strtolower($col['Type'] ?? '');
    $needsAlter = false;
    if ($type === '') {
        $needsAlter = true;
    } elseif (strpos($type, 'enum(') === 0) {
        if (stripos($type, 'labtesting') === false) $needsAlter = true;
    } elseif (strpos($type, 'varchar') === 0) {
        $needsAlter = true; // normalize to enum with LabTesting
    }

    if ($needsAlter) {
        $pdo->exec("ALTER TABLE client_requests MODIFY service_type ENUM('Babysitting','ElderlyCare','PatientCare','LabTesting') NOT NULL");
        say("> Altered service_type to include 'LabTesting'.");
    } else {
        say("> service_type already supports 'LabTesting'; no alter needed.");
    }

    say("Done.");
} catch (Exception $e) {
    say("Error: ".$e->getMessage());
    exit(1);
}
