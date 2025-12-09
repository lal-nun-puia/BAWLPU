<?php
require 'db.php';

$pdo->exec("UPDATE services SET active = 1 WHERE slug IN ('ElderlyCare', 'PatientCare', 'Babysitting', 'LabTesting')");

echo "Services activated.\n";
?>
