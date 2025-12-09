<?php
require_once 'db.php';

try {
    // Check if test nurse exists
    $stmt = $pdo->query('SELECT id FROM users WHERE email = "nurse@test.com"');
    $nurse = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($nurse) {
        echo 'Test nurse ID: ' . $nurse['id'] . "\n";
    } else {
        echo "Test nurse not found\n";
    }

    // Check client requests
    $stmt = $pdo->query('SELECT id, service_type, status FROM client_requests WHERE service_type = "PatientCare" AND status = "Pending"');
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo 'Pending PatientCare requests: ' . count($requests) . "\n";
    foreach ($requests as $req) {
        echo 'Request ID: ' . $req['id'] . ', Status: ' . $req['status'] . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
