<?php
// search.php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

$skill = trim($_GET['skill'] ?? '');
$location = trim($_GET['location'] ?? '');

$sql = "SELECT n.nurse_id, u.user_id, u.name, u.email, n.skills, n.service_type, n.salary_per_day, n.experience_years, n.location, n.bio
        FROM nurses n JOIN users u ON n.user_id = u.user_id WHERE 1=1";
$params = [];

if($skill !== ''){
    $sql .= " AND n.skills LIKE ?";
    $params[] = '%'.$skill.'%';
}
if($location !== ''){
    $sql .= " AND n.location LIKE ?";
    $params[] = '%'.$location.'%';
}
$sql .= " ORDER BY n.nurse_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$nurses = $stmt->fetchAll();
echo json_encode(['nurses'=>$nurses]);
exit;
?>
