<?php
require 'db.php';

echo "=== AVAILABLE REQUESTS FOR NURSES ===\n";
$stmt = $pdo->query("SELECT cr.*, u.name AS client_name FROM client_requests cr JOIN users u ON cr.client_id = u.id WHERE cr.service_type = 'PatientCare' AND cr.nurse_id IS NULL ORDER BY cr.id DESC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($requests) === 0) {
    echo "No available PatientCare requests.\n";
} else {
    foreach ($requests as $r) {
        echo "ID: {$r['id']}, Client: {$r['client_name']}, Status: {$r['status']}\n";
    }
}

echo "\n=== ALL REQUESTS ===\n";
$stmt = $pdo->query("SELECT id, service_type, status, nurse_id FROM client_requests ORDER BY id");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $r) {
    echo "ID: {$r['id']}, Type: {$r['service_type']}, Status: {$r['status']}, Nurse: " . ($r['nurse_id'] ?: 'None') . "\n";
}
?>
