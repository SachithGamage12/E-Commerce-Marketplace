<?php
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'markets_markethub';
$username = 'markets_root';
$password = 'Sun123flower@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id'])) {
    $category_id = (int)$_POST['category_id'];

    try {
        $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE parent_id = ? ORDER BY name");
        $stmt->execute([$category_id]);
        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['subcategories' => $subcategories]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Failed to fetch subcategories: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>