<?php
session_start();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['user_id']) && isset($data['user_email'])) {
    $_SESSION['user_id'] = $data['user_id'];
    $_SESSION['user_email'] = $data['user_email'];
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>