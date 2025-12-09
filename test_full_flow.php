<?php
require 'db.php';

// Test 1: Check table structures
echo "=== TABLE STRUCTURES ===\n";
$tables = ['bookings', 'client_requests', 'users'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "$table columns: " . implode(', ', array_column($columns, 'Field')) . "\n";
    } catch (Exception $e) {
        echo "Error describing $table: " . $e->getMessage() . "\n";
    }
}

// Test 2: Check counts
echo "\n=== COUNTS ===\n";
$queries = [
    'Total users' => 'SELECT COUNT(*) FROM users',
    'Nurses' => 'SELECT COUNT(*) FROM users WHERE role="Nurse"',
    'Clients' => 'SELECT COUNT(*) FROM users WHERE role="Client"',
    'Total requests' => 'SELECT COUNT(*) FROM client_requests',
    'Pending requests' => 'SELECT COUNT(*) FROM client_requests WHERE status="Pending"',
    'Applied requests' => 'SELECT COUNT(*) FROM client_requests WHERE status="Applied"',
    'Accepted requests' => 'SELECT COUNT(*) FROM client_requests WHERE status="Accepted"',
    'Total bookings' => 'SELECT COUNT(*) FROM bookings',
    'Applied bookings' => 'SELECT COUNT(*) FROM bookings WHERE status="Applied"',
    'Accepted bookings' => 'SELECT COUNT(*) FROM bookings WHERE status="Accepted"',
    'Direct bookings (request_id NULL)' => 'SELECT COUNT(*) FROM bookings WHERE request_id IS NULL',
    'Request-based bookings' => 'SELECT COUNT(*) FROM bookings WHERE request_id IS NOT NULL'
];

foreach ($queries as $label => $query) {
    try {
        $result = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);
        echo "$label: " . $result['COUNT(*)'] . "\n";
    } catch (Exception $e) {
        echo "Error in $label: " . $e->getMessage() . "\n";
    }
}

// Test 3: Sample data
echo "\n=== SAMPLE DATA ===\n";
try {
    $stmt = $pdo->query("SELECT id, name, role FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Users:\n";
    foreach ($users as $user) {
        echo "  {$user['id']}: {$user['name']} ({$user['role']})\n";
    }
} catch (Exception $e) {
    echo "Error fetching users: " . $e->getMessage() . "\n";
}

try {
    $stmt = $pdo->query("SELECT id, service_type, status FROM client_requests LIMIT 5");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nRequests:\n";
    foreach ($requests as $req) {
        echo "  {$req['id']}: {$req['service_type']} ({$req['status']})\n";
    }
} catch (Exception $e) {
    echo "Error fetching requests: " . $e->getMessage() . "\n";
}

try {
    $stmt = $pdo->query("SELECT id, request_id, status FROM bookings LIMIT 5");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nBookings:\n";
    foreach ($bookings as $book) {
        $type = $book['request_id'] ? 'Request-based' : 'Direct';
        echo "  {$book['id']}: $type ({$book['status']})\n";
    }
} catch (Exception $e) {
    echo "Error fetching bookings: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
