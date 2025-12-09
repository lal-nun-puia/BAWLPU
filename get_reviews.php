<?php
require_once 'db.php';

$nurse_id = intval($_GET['nurse_id'] ?? 0);

if (!$nurse_id) {
    echo json_encode(['reviews' => []]);
    exit();
}

$sql = "SELECT rating, review_text, 'Anonymous' AS client_name, created_at
        FROM reviews
        WHERE reviewee_id = ?
        ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$nurse_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['reviews' => $reviews]);
?>
