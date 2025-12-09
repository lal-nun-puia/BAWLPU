<?php
require 'db.php';

echo "Services:\n";
$stmt = $pdo->query("SELECT * FROM services");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
    echo $s['slug'] . ' - active: ' . $s['active'] . "\n";
}

echo "\nService items for Babysitting:\n";
$stmt = $pdo->prepare("SELECT * FROM service_items WHERE service_slug = ?");
$stmt->execute(['Babysitting']);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(empty($items)) {
    echo "No items\n";
} else {
    foreach($items as $i) {
        echo $i['name'] . ' - ' . $i['price'] . "\n";
    }
}

echo "\nService items for ElderlyCare:\n";
$stmt->execute(['ElderlyCare']);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(empty($items)) {
    echo "No items\n";
} else {
    foreach($items as $i) {
        echo $i['name'] . ' - ' . $i['price'] . "\n";
    }
}

echo "\nService items for PatientCare:\n";
$stmt->execute(['PatientCare']);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(empty($items)) {
    echo "No items\n";
} else {
    foreach($items as $i) {
        echo $i['name'] . ' - ' . $i['price'] . "\n";
    }
}
?>
