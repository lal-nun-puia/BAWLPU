<?php
require_once 'db.php';

// Simulate nurse session
$nurse_id = 2; // Assume nurse id 2 exists

// Fetch applied bookings for this nurse (direct bookings and applications)
$sql = "SELECT b.*, u.name AS client_name, u.phone AS client_phone, u.address AS client_address,
               cr.patient_name, cr.phone AS request_phone, cr.address AS request_address, cr.notes AS request_notes, cr.service_type
        FROM bookings b
        JOIN users u ON b.client_id = u.id
        LEFT JOIN client_requests cr ON b.request_id = cr.id
        WHERE b.nurse_id = ? AND b.status = 'Applied'
        ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nurse_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Bookings for nurse $nurse_id:\n";
foreach($bookings as $b) {
    echo "ID: {$b['id']}, Client: {$b['client_name']}, Request ID: " . ($b['request_id'] ?: 'NULL') . "\n";
    if($b['request_id']) {
        echo "  Patient: {$b['patient_name']}, Service: {$b['service_type']}, Phone: {$b['request_phone']}, Address: {$b['request_address']}, Notes: {$b['request_notes']}\n";
    } else {
        echo "  Date: {$b['date']}, Time: {$b['time']}, Phone: {$b['client_phone']}, Address: {$b['client_address']}, Notes: {$b['notes']}\n";
    }
    echo "\n";
}
?>
