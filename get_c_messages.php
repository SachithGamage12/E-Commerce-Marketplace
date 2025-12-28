<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$servername = "localhost";
$dbname = 'markets_markethub';
$username = 'markets_root';
$password = 'Sun123flower@';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : 0;
$buyer_id = isset($_GET['buyer_id']) ? (int)$_GET['buyer_id'] : 0;
$current_user_id = $_SESSION['user_id'];

if ($ad_id <= 0 || $buyer_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid ad ID or buyer ID']);
    exit();
}

// Fetch messages for the conversation
$sql = "
    SELECT m.id, m.sender_id, m.receiver_id, m.message, m.created_at
    FROM messages m
    WHERE m.ad_id = ? 
    AND (
        (m.sender_id = ? AND m.receiver_id = ?) 
        OR (m.sender_id = ? AND m.receiver_id = ?)
    )
    ORDER BY m.created_at ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $ad_id, $current_user_id, $buyer_id, $buyer_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $row['id'],
        'sender_id' => $row['sender_id'],
        'receiver_id' => $row['receiver_id'],
        'message' => htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'),
        'created_at' => $row['created_at']
    ];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'messages' => $messages]);
?>