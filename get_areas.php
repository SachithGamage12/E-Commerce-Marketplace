<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'markets_markethub';
$username = 'markets_root';;
$password = 'Sun123flower@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['district_name'])) {
        $district_name = $_POST['district_name'];

        // Get district ID
        $stmt = $pdo->prepare("SELECT id FROM districts WHERE name = ?");
        $stmt->execute([$district_name]);
        $district = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($district) {
            // Fetch areas for the district
            $stmt = $pdo->prepare("SELECT name FROM areas WHERE district_id = ? ORDER BY name");
            $stmt->execute([$district['id']]);
            $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['areas' => $areas]);
        } else {
            echo json_encode(['error' => 'District not found']);
        }
    } else {
        echo json_encode(['error' => 'Invalid request']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>