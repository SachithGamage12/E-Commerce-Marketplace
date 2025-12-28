<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['ad_id'])) {
    echo json_encode(['ad_id' => $_SESSION['ad_id']]);
    unset($_SESSION['ad_id']); // Clear the session variable after use
} else {
    echo json_encode(['error' => 'No ad ID found in session']);
}
?>