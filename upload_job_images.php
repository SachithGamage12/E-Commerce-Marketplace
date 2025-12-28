<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = 'localhost';
$dbname = 'markets_markethub';
$username = 'markets_root';
$password = 'Sun123flower@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Database connection failed: ' . $e->getMessage()]]);
    exit;
}

// Initialize response
$response = ['success' => false, 'errors' => []];

// Check if job_id is provided
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
if ($job_id <= 0) {
    $response['errors'][] = 'Invalid job ID.';
    echo json_encode($response);
    exit;
}

// Check if files are uploaded
if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
    $response['errors'][] = 'No images uploaded.';
    echo json_encode($response);
    exit;
}

// Directory to store images
$upload_dir = 'Uploads/job_images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Allowed file types
$allowed_types = ['image/jpeg', 'image/png'];

$uploaded_images = [];

try {
    $pdo->beginTransaction();

    foreach ($_FILES['images']['name'] as $key => $name) {
        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
            $response['errors'][] = "Upload error for file $name.";
            continue;
        }

        $tmp_name = $_FILES['images']['tmp_name'][$key];
        $type = $_FILES['images']['type'][$key];

        // Validate file type
        if (!in_array($type, $allowed_types)) {
            $response['errors'][] = "$name: Only JPG and PNG files are allowed.";
            continue;
        }

        // Generate unique file name
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $filename = uniqid('job_' . $job_id . '_') . '.' . $ext;
        $destination = $upload_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($tmp_name, $destination)) {
            // Insert into job_images table
            $stmt = $pdo->prepare("INSERT INTO job_images (job_id, image_path) VALUES (?, ?)");
            $stmt->execute([$job_id, $destination]);
            $uploaded_images[] = $filename;
        } else {
            $response['errors'][] = "Failed to upload $name.";
        }
    }

    if (empty($response['errors']) && !empty($uploaded_images)) {
        $pdo->commit();
        $response['success'] = true;
        $response['message'] = 'Images uploaded successfully.';
    } else {
        $pdo->rollBack();
        if (empty($response['errors'])) {
            $response['errors'][] = 'No images were uploaded successfully.';
        }
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    $response['errors'][] = 'Database error: ' . $e->getMessage();
    error_log('Image upload database error: ' . $e->getMessage());
}

http_response_code($response['success'] ? 200 : 400);
echo json_encode($response);
exit;
?>