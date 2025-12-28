<?php
session_start();

// Database connection
$servername = "localhost";
$dbname = 'markets_markethub';
$username = 'markets_root';
$password = 'Sun123flower@';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;
$seller_id = isset($_GET['seller_id']) ? intval($_GET['seller_id']) : 0;

if ($ad_id <= 0 || $seller_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ad ID or seller ID']);
    exit();
}

// Fetch messages for the conversation
$sql = "SELECT m.id, m.sender_id, m.receiver_id, m.message, m.created_at
        FROM messages m
        WHERE m.ad_id = ? 
        AND (
            (m.sender_id = ? AND m.receiver_id = ?) 
            OR (m.sender_id = ? AND m.receiver_id = ?)
        )
        ORDER BY m.created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $ad_id, $current_user_id, $seller_id, $seller_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $row['id'],
        'sender_id' => $row['sender_id'],
        'receiver_id' => $row['receiver_id'],
        'message' => htmlspecialchars($row['message']),
        'created_at' => $row['created_at']
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'messages' => $messages
]);
?>