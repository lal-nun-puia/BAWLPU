<?php
require 'db.php';

echo "=== BOOKING DETAILS ===\n";
$stmt = $pdo->query("SELECT b.*, cr.service_type, u.name AS client_name, n.name AS nurse_name
                     FROM bookings b
                     LEFT JOIN client_requests cr ON b.request_id = cr.id
                     LEFT JOIN users u ON b.client_id = u.id
                     LEFT JOIN users n ON b.nurse_id = n.id
                     ORDER BY b.id");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($bookings as $b) {
    $type = $b['request_id'] ? 'Request-based' : 'Direct';
    echo "ID: {$b['id']}, Type: $type, Client: {$b['client_name']}, Nurse: {$b['nurse_name']}, Status: {$b['status']}\n";
    if ($b['request_id']) {
        echo "  Service: {$b['service_type']}\n";
    } else {
        echo "  Date: {$b['date']}, Time: {$b['time']}\n";
    }
}
?>
