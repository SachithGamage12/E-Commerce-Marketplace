<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$servername = "localhost";
$dbname = 'markets_markethub';
$username = 'markets_root';
$password = 'Sun123flower@';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$ad_id = $data['ad_id'];
$sender_id = $data['sender_id'];
$receiver_id = $data['receiver_id'];
$message = $data['message'];

if (empty($ad_id) || empty($sender_id) || empty($receiver_id) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO messages (ad_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $ad_id, $sender_id, $receiver_id, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}

$stmt->close();
$conn->close();
?>