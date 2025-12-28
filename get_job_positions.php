<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'markets_markethub';
$username = 'markets_root';
$password = 'Sun123flower@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

    if ($category_id <= 0) {
        echo json_encode(['error' => 'Invalid category ID']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name FROM job_categories WHERE parent_id = ? ORDER BY name");
    $stmt->execute([$category_id]);
    $job_positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['job_positions' => $job_positions]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>