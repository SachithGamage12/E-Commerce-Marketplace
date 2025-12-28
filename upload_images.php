<?php
session_start();
header('Content-Type: application/json');

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
    echo json_encode(['success' => false, 'errors' => ['Connection failed: ' . $e->getMessage()]]);
    exit;
}

// Handle media uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [
        'success' => false,
        'errors' => [],
        'image_paths' => [],
        'video_path' => ''
    ];

    // Get ad_id from POST or session
    $ad_id = isset($_POST['ad_id']) ? (int)$_POST['ad_id'] : (isset($_SESSION['ad_id']) ? (int)$_SESSION['ad_id'] : 0);
    if ($ad_id <= 0) {
        $response['errors'][] = 'Invalid or missing ad ID.';
        echo json_encode($response);
        exit;
    }

    // Verify ad exists
    try {
        $stmt = $pdo->prepare("SELECT id FROM ads WHERE id = ?");
        $stmt->execute([$ad_id]);
        if (!$stmt->fetch()) {
            $response['errors'][] = 'Ad does not exist.';
            echo json_encode($response);
            exit;
        }
    } catch (PDOException $e) {
        $response['errors'][] = 'Database error: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }

    // Set up upload directories
    $image_dir = 'uploads/images/';
    $video_dir = 'uploads/videos/';
    $compressed_dir = 'uploads/compressed/';
    
    // Create directories if they don't exist
    foreach ([$image_dir, $video_dir, $compressed_dir] as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $response['errors'][] = "Failed to create directory: $dir";
                error_log("Failed to create directory: $dir");
            }
        }
        
        // Check if directory is writable
        if (!is_writable($dir)) {
            $response['errors'][] = "Directory is not writable: $dir";
            error_log("Directory not writable: $dir");
        }
    }

    // Process uploaded images
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $max_images = 5;
        $uploaded_count = 0;
        
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($uploaded_count >= $max_images) {
                $response['errors'][] = 'Maximum 5 images allowed.';
                break;
            }

            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png'];
                if (!in_array($_FILES['images']['type'][$key], $allowed_types)) {
                    $response['errors'][] = "File $name: Only JPG/PNG allowed.";
                    continue;
                }

                // Check file size (max 10MB)
                if ($_FILES['images']['size'][$key] > 10 * 1024 * 1024) {
                    $response['errors'][] = "File $name: Maximum 10MB allowed.";
                    continue;
                }

                // Generate unique filename
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $original_name = uniqid() . '_original.' . $ext;
                $compressed_name = uniqid() . '_compressed.jpg';
                
                $original_path = $image_dir . $original_name;
                $compressed_path = $compressed_dir . $compressed_name;

                // Move original file
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $original_path)) {
                    // Compress image
                    $compression_result = compressImage($original_path, $compressed_path, 800, 70);
                    
                    if ($compression_result['success']) {
                        // Save compressed image to database
                        try {
                            $stmt = $pdo->prepare("INSERT INTO ad_images (ad_id, image_path, original_path, compressed_path) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$ad_id, $compressed_path, $original_path, $compressed_path]);
                            
                            $response['image_paths'][] = $compressed_path;
                            $uploaded_count++;
                            
                            // Log compression results
                            error_log("Image compressed: {$original_path} -> {$compressed_path}");
                            error_log("Original size: " . filesize($original_path) . " bytes");
                            error_log("Compressed size: " . filesize($compressed_path) . " bytes");
                            error_log("Compression ratio: " . round((1 - filesize($compressed_path) / filesize($original_path)) * 100, 2) . "%");
                            
                        } catch (PDOException $e) {
                            $response['errors'][] = "Failed to save image path: " . $e->getMessage();
                            error_log("Database error for image: " . $e->getMessage());
                        }
                    } else {
                        $response['errors'][] = "Failed to compress image: $name - " . $compression_result['error'];
                        error_log("Compression failed for: $original_path - " . $compression_result['error']);
                    }
                } else {
                    $response['errors'][] = "Failed to move file: $name";
                    error_log("Failed to move file: $name to $original_path");
                }
            } else {
                $error_codes = [
                    UPLOAD_ERR_INI_SIZE => 'File too large (php.ini limit).',
                    UPLOAD_ERR_FORM_SIZE => 'File too large (form limit).',
                    UPLOAD_ERR_PARTIAL => 'File partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
                ];
                $error_code = $_FILES['images']['error'][$key];
                $response['errors'][] = "Upload error for $name: " . ($error_codes[$error_code] ?? 'Unknown error');
            }
        }
    }

    // Process uploaded video
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $name = $_FILES['video']['name'];
        $allowed_types = ['video/mp4', 'video/mov', 'video/avi'];
        
        if (!in_array($_FILES['video']['type'], $allowed_types)) {
            $response['errors'][] = "File $name: Only MP4, MOV, and AVI videos allowed.";
        } elseif ($_FILES['video']['size'] > 50 * 1024 * 1024) {
            $response['errors'][] = "File $name: Maximum 50MB allowed for videos.";
        } else {
            // Generate unique filename
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $video_name = uniqid() . '_video.' . $ext;
            $video_path = $video_dir . $video_name;
            
            // Move video file
            if (move_uploaded_file($_FILES['video']['tmp_name'], $video_path)) {
                try {
                    // Save video to database
                    $stmt = $pdo->prepare("INSERT INTO ad_videos (ad_id, video_path) VALUES (?, ?)");
                    $stmt->execute([$ad_id, $video_path]);
                    
                    $response['video_path'] = $video_path;
                    error_log("Video uploaded: $video_path, Size: " . filesize($video_path) . " bytes");
                    
                } catch (PDOException $e) {
                    $response['errors'][] = "Failed to save video path: " . $e->getMessage();
                    error_log("Database error for video: " . $e->getMessage());
                }
            } else {
                $response['errors'][] = "Failed to move video file: $name";
                error_log("Failed to move video: $name to $video_path");
            }
        }
    }

    // Check if any media was processed successfully
    if (!empty($response['image_paths']) || !empty($response['video_path'])) {
        $response['success'] = true;
    } elseif (empty($response['errors'])) {
        // If no media was uploaded but also no errors, mark as successful
        $response['success'] = true;
        $response['message'] = 'No media files were uploaded.';
    }

    // Log request details for debugging
    error_log("Media upload attempt for ad_id: $ad_id");
    error_log("Response: " . print_r($response, true));

    echo json_encode($response);
    exit;
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => ['Invalid request']]);
    exit;
}

// Image compression function
function compressImage($source, $destination, $max_width = 800, $quality = 70) {
    try {
        // Get image info
        $info = getimagesize($source);
        if (!$info) {
            return ['success' => false, 'error' => 'Invalid image file'];
        }

        $mime = $info['mime'];
        $width = $info[0];
        $height = $info[1];

        // Create image from source
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                break;
            default:
                return ['success' => false, 'error' => 'Unsupported image type'];
        }

        if (!$image) {
            return ['success' => false, 'error' => 'Failed to create image resource'];
        }

        // Calculate new dimensions
        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = (int)($height * $max_width / $width);
        } else {
            $new_width = $width;
            $new_height = $height;
        }

        // Create new image
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG
        if ($mime == 'image/png') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }

        // Resize image
        if (!imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height)) {
            imagedestroy($image);
            imagedestroy($new_image);
            return ['success' => false, 'error' => 'Failed to resize image'];
        }

        // Save compressed image
        if ($mime == 'image/png') {
            // For PNG, use quality 0-9 (0 = no compression, 9 = max compression)
            $png_quality = 9 - (int)($quality / 10);
            $result = imagepng($new_image, $destination, $png_quality);
        } else {
            // For JPEG, use quality 0-100
            $result = imagejpeg($new_image, $destination, $quality);
        }

        // Clean up
        imagedestroy($image);
        imagedestroy($new_image);

        if (!$result) {
            return ['success' => false, 'error' => 'Failed to save compressed image'];
        }

        return ['success' => true, 'original_size' => filesize($source), 'compressed_size' => filesize($destination)];

    } catch (Exception $e) {
        error_log("Compression error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>