<?php
session_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');
error_reporting(E_ALL);

header('Content-Type: application/json');

if (isset($_SESSION['job_id'])) {
    echo json_encode(['success' => true, 'job_id' => $_SESSION['job_id']]);
} else {
    echo json_encode(['success' => false, 'error' => 'No job ID found']);
}
exit;