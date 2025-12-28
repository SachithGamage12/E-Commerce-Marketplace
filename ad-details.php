<?php
session_start();

include 'track-view.php';
// Database connection
$servername = "localhost";
$dbname = 'markets_markethub';
$username = 'markets_root';
$password = 'Sun123flower@';
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Helper function to convert timestamp to "time ago" format
function timeAgo($timestamp) {
    $timezone = new DateTimeZone('Asia/Colombo');
    $datetime = new DateTime($timestamp, $timezone);
    $now = new DateTime('now', $timezone);
    if ($datetime > $now) {
        return 'Just now';
    }
    $interval = $now->diff($datetime);
    if ($interval->y > 0) {
        return $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
    } elseif ($interval->m > 0) {
        return $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
    } elseif ($interval->d > 0) {
        return $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
    } elseif ($interval->h > 0) {
        return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
    } elseif ($interval->i > 0) {
        return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'Just now';
    }
}
// Get ad ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}
$adId = $_GET['id'];
// Fetch ad details with seller information
$sql = "SELECT a.*, c.name as category_name, c.parent_id as main_category_id, d.name as district_name, ar.name as area_name,
        u.firstName as seller_firstName, u.lastName as seller_lastName, u.id as seller_id, u.email as seller_email
        FROM ads a
        JOIN categories c ON a.subcategory_id = c.id
        JOIN districts d ON a.district = d.name
        JOIN areas ar ON a.area = ar.name
        JOIN users u ON a.user_id = u.id
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}
$ad = $result->fetch_assoc();
// Get parent category name
$mainCatSql = "SELECT name FROM categories WHERE id = ?";
$mainCatStmt = $conn->prepare($mainCatSql);
$mainCatStmt->bind_param("i", $ad['main_category_id']);
$mainCatStmt->execute();
$mainCatResult = $mainCatStmt->get_result();
$mainCategory = $mainCatResult->fetch_assoc();
// Fetch all images for this ad
$imagesSql = "SELECT image_path, original_path, compressed_path FROM ad_images WHERE ad_id = ? ORDER BY id ASC";
$imagesStmt = $conn->prepare($imagesSql);
$imagesStmt->bind_param("i", $adId);
$imagesStmt->execute();
$imagesResult = $imagesStmt->get_result();
$images = [];
while ($image = $imagesResult->fetch_assoc()) {
    // Use compressed image if available, otherwise original
    $imagePath = !empty($image['compressed_path']) ? $image['compressed_path'] : $image['image_path'];
    $images[] = [
        'path' => $imagePath,
        'type' => 'image'
    ];
}
// Fetch video for this ad
$videoSql = "SELECT video_path FROM ad_videos WHERE ad_id = ? LIMIT 1";
$videoStmt = $conn->prepare($videoSql);
$videoStmt->bind_param("i", $adId);
$videoStmt->execute();
$videoResult = $videoStmt->get_result();
$video = [];
if ($videoResult->num_rows > 0) {
    $videoData = $videoResult->fetch_assoc();
    $video = [
        'path' => $videoData['video_path'],
        'type' => 'video'
    ];
}
// Combine images and video for gallery
$mediaItems = [];
// Add video first if exists
if (!empty($video)) {
    $mediaItems[] = $video;
}
// Add images
foreach ($images as $image) {
    $mediaItems[] = $image;
}
// If no media, add placeholder
if (empty($mediaItems)) {
    $mediaItems[] = [
        'path' => 'images/placeholder.jpg',
        'type' => 'image'
    ];
}
// Fetch similar ads
$similarAdsSql = "SELECT a.id, a.title, a.sale_price, a.created_at,
                 (SELECT image_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as image_path,
                 (SELECT video_path FROM ad_videos WHERE ad_id = a.id LIMIT 1) as video_path
                 FROM ads a
                 WHERE a.subcategory_id = ? AND a.id != ?
                 ORDER BY a.created_at DESC
                 LIMIT 4";
$similarAdsStmt = $conn->prepare($similarAdsSql);
$similarAdsStmt->bind_param("ii", $ad['subcategory_id'], $adId);
$similarAdsStmt->execute();
$similarAdsResult = $similarAdsStmt->get_result();
$similarAds = [];
while ($similarAd = $similarAdsResult->fetch_assoc()) {
    $similarAds[] = $similarAd;
}
// Determine if the user is logged in
$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
// Check if the logged-in user is a seller
$isSeller = false;
if ($currentUserId) {
    $sellerCheckSql = "SELECT COUNT(*) as ad_count FROM ads WHERE user_id = ?";
    $sellerCheckStmt = $conn->prepare($sellerCheckSql);
    $sellerCheckStmt->bind_param("i", $currentUserId);
    $sellerCheckStmt->execute();
    $sellerCheckResult = $sellerCheckStmt->get_result();
    $sellerCheck = $sellerCheckResult->fetch_assoc();
    $isSeller = $sellerCheck['ad_count'] > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($ad['title']); ?> - Markets.lk</title>
    <link rel="icon" type="image/png" href="images/image.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .neon-yellow-border {
            box-shadow: 0 0 10px #FFFF00, 0 0 20px #FFFF00, 0 0 30px #FFFF00;
        }
        .animated-free {
            animation: pulse 1.5s infinite;
            transform-origin: center;
        }

        /* Media Gallery - WHITE BACKGROUND */
        .gallery-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-gap: 10px;
            margin-top: 20px;
        }
        .gallery-main {
            grid-column: span 4;
            height: 400px;
            overflow: hidden;
            border-radius: 10px;
            position: relative;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            touch-action: pan-y;
            cursor: pointer;
        }
        .gallery-main img,
        .gallery-main video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            user-select: none;
        }
        .gallery-main video {
            background-color: #ffffff;
        }
        .gallery-thumbnails {
            grid-column: span 4;
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding: 10px 0;
        }
        .gallery-thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 5px;
            overflow: hidden;
            cursor: pointer;
            flex-shrink: 0;
            border: 2px solid transparent;
            transition: border-color 0.3s ease;
            position: relative;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
        }
        .gallery-thumbnail.active {
            border-color: #ff6200;
        }
        .gallery-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gallery-thumbnail.video-thumbnail::after {
            content: '▶';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 20px;
            text-shadow: 0 0 10px rgba(0,0,0,0.5);
            z-index: 2;
        }

        /* Video Controls */
        .video-controls-container {
            position: absolute;
            bottom: 10px;
            left: 10px;
            right: 10px;
            display: flex;
            align-items: center;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 8px;
            padding: 8px 12px;
            z-index: 20;
            transition: opacity 0.3s ease;
            opacity: 0;
        }
        .gallery-main:hover .video-controls-container {
            opacity: 1;
        }
        .video-controls-container:hover {
            opacity: 1;
        }
        .video-controls {
            display: flex;
            align-items: center;
            width: 100%;
            gap: 10px;
        }
        .control-btn {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .control-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .time-display {
            color: white;
            font-size: 14px;
            font-family: monospace;
            margin: 0 10px;
        }
        .progress-container {
            flex: 1;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            cursor: pointer;
            position: relative;
        }
        .progress-bar {
            height: 100%;
            background: #ff6200;
            border-radius: 2px;
            width: 0%;
        }
        .volume-container {
            position: relative;
        }
        .volume-slider {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%) rotate(-90deg);
            width: 80px;
            height: 20px;
            background: rgba(0, 0, 0, 0.8);
            padding: 10px;
            border-radius: 5px;
            display: none;
            z-index: 21;
        }
        .volume-container:hover .volume-slider {
            display: block;
        }
        .volume-slider input {
            width: 100%;
            height: 4px;
            -webkit-appearance: none;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            outline: none;
        }
        .volume-slider input::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 12px;
            height: 12px;
            background: #ff6200;
            border-radius: 50%;
            cursor: pointer;
        }
        .fullscreen-btn {
            margin-left: auto;
        }

        /* Fullscreen styles */
        .gallery-main.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            background: black;
            border-radius: 0;
            border: none;
        }
        .gallery-main.fullscreen img,
        .gallery-main.fullscreen video {
            object-fit: contain;
        }
        .gallery-main.fullscreen .video-controls-container {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
        }

        /* Image Fullscreen Overlay */
        .image-fullscreen-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }
        .image-fullscreen-container {
            position: relative;
            width: 90%;
            height: 90%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .image-fullscreen-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            user-select: none;
            -webkit-user-drag: none;
        }
        .fullscreen-nav {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 15px;
            z-index: 10001;
        }
        .fullscreen-nav-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        .fullscreen-nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        .fullscreen-close-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            z-index: 10001;
        }
        .fullscreen-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        .fullscreen-counter {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 18px;
            background: rgba(0, 0, 0, 0.5);
            padding: 8px 16px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        /* Watermark for images and videos */
        .watermarked::after {
            content: "Markets.lk";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3rem;
            font-weight: bold;
            color: rgba(0, 0, 0, 0.15);
            text-shadow: 
                1px 1px 2px rgba(255, 255, 255, 0.8),
                -1px -1px 2px rgba(255, 255, 255, 0.8),
                1px -1px 2px rgba(255, 255, 255, 0.8),
                -1px 1px 2px rgba(255, 255, 255, 0.8);
            pointer-events: none;
            z-index: 10;
            white-space: nowrap;
            letter-spacing: 4px;
            mix-blend-mode: multiply;
        }

        /* Fullscreen watermark */
        .fullscreen-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3rem;
            font-weight: bold;
            color: rgba(0, 0, 0, 0.15);
            pointer-events: none;
            z-index: 10;
            white-space: nowrap;
            letter-spacing: 4px;
            text-shadow: 
                1px 1px 2px rgba(255, 255, 255, 0.8),
                -1px -1px 2px rgba(255, 255, 255, 0.8),
                1px -1px 2px rgba(255, 255, 255, 0.8),
                -1px 1px 2px rgba(255, 255, 255, 0.8);
                mix-blend-mode: multiply;
        }

        /* For videos, we'll use an overlay div since ::after doesn't work well with video */
        .video-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3rem;
            font-weight: bold;
            color: rgba(0, 0, 0, 0.19);
            text-shadow: 
                1px 1px 2px rgba(255, 255, 255, 0.8),
                -1px -1px 2px rgba(255, 255, 255, 0.8),
                1px -1px 2px rgba(255, 255, 255, 0.8),
                -1px 1px 2px rgba(255, 255, 255, 0.8);
            pointer-events: none;
            z-index: 10;
            white-space: nowrap;
            letter-spacing: 4px;
            mix-blend-mode: multiply;
        }
        
        @media (max-width: 768px) {
            .watermarked::after,
            .video-watermark { font-size: 2rem; }
            .fullscreen-watermark { font-size: 3rem; }
            .gallery-main { height: 300px; }
            .video-controls-container {
                padding: 6px 8px;
            }
            .control-btn {
                font-size: 16px;
            }
            .time-display {
                font-size: 12px;
            }
            .fullscreen-nav-btn,
            .fullscreen-close-btn {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }
            .fullscreen-counter {
                font-size: 16px;
                bottom: 20px;
            }
        }
        @media (max-width: 480px) {
            .watermarked::after,
            .video-watermark { font-size: 1.5rem; }
            .fullscreen-watermark { font-size: 2.5rem; }
            .gallery-main { height: 250px; }
            .gallery-thumbnail {
                width: 60px;
                height: 60px;
            }
            .fullscreen-nav-btn,
            .fullscreen-close-btn {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }
            .fullscreen-counter {
                font-size: 14px;
                padding: 6px 12px;
            }
        }

        /* Media type indicator */
        .media-type {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 98, 0, 0.8);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            z-index: 5;
        }
        .video-type {
            background: rgba(59, 130, 246, 0.8);
        }

        /* Contact card */
        .contact-card {
            background: linear-gradient(145deg, #1a1a3d, #2a2a5e);
            border: 1px solid #ff6200;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(255, 98, 0, 0.3);
            color: white;
        }
        .contact-btn {
            background: linear-gradient(90deg, #ff6200, #ff8f00);
            border-radius: 8px;
            color: #ffffff;
            font-weight: 600;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 0;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .contact-btn:hover {
            transform: scale(1.05);
        }
        .chat-btn {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
        }
        .chat-btn:hover {
            background: linear-gradient(90deg, #1d4ed8, #2563eb);
        }

        /* Chat Box */
        .chat-box {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            max-height: 500px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            z-index: 1000;
        }
        .chat-header {
            background: linear-gradient(90deg, #ff6200, #ff8f00);
            color: white;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-body {
            padding: 10px;
            height: 350px;
            overflow-y: auto;
        }
        .chat-product {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f1f1f1;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .chat-product img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 10px;
        }
        .chat-message {
            margin-bottom: 10px;
            display: flex;
            flex-direction: column;
        }
        .chat-message.sent {
            align-items: flex-end;
        }
        .chat-message.received {
            align-items: flex-start;
        }
        .chat-message .message-content {
            max-width: 70%;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 14px;
        }
        .chat-message.sent .message-content {
            background: #ff6200;
            color: white;
        }
        .chat-message.received .message-content {
            background: #e5e7eb;
            color: black;
        }
        .chat-message .message-time {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }
        .chat-footer {
            padding: 10px;
            border-top: 1px solid #e5e7eb;
            display: flex;
        }
        .chat-footer input {
            flex: 1;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            outline: none;
        }
        .chat-footer button {
            margin-left: 10px;
            padding: 8px 12px;
            background: #ff6200;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .chat-footer button:hover {
            background: #ff8f00;
        }

        @media (max-width: 640px) {
            .contact-btn {
                font-size: 14px;
                padding: 10px 0;
                margin-bottom: 8px;
            }
            .contact-card {
                padding: 15px;
            }
            .chat-box {
                width: 100%;
                right: 0;
                bottom: 0;
                border-radius: 10px 10px 0 0;
            }
        }

        /* Similar ads */
        .similar-ad-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .similar-ad-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .similar-ad-image {
            height: 150px;
            overflow: hidden;
            position: relative;
            background-color: #ffffff;
        }
        .similar-ad-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .similar-ad-image.video-ad::after {
            content: '▶';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 24px;
            text-shadow: 0 0 10px rgba(0,0,0,0.5);
            z-index: 2;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 16px;
        }
        .breadcrumb a {
            color: #ff6200;
        }
        .breadcrumb .separator {
            color: #ccc;
            margin: 0 4px;
        }
        .sidebar {
            transition: transform 0.3s ease-in-out;
            transform: translateX(-100%);
        }
        .sidebar.open {
            transform: translateX(0);
        }
        @media (min-width: 768px) {
            .sidebar {
                transform: translateX(0);
            }
        }

        /* Play button for video thumbnails */
        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            background: rgba(255, 98, 0, 0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            z-index: 2;
            transition: all 0.3s ease;
        }
        .gallery-thumbnail:hover .play-button {
            background: rgba(255, 98, 0, 1);
            transform: translate(-50%, -50%) scale(1.1);
        }

        /* Loading spinner */
        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 98, 0, 0.3);
            border-top-color: #ff6200;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            z-index: 5;
        }
        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>
<body class="font-chakra bg-gray-100">
    <header class="bg-gradient-to-r from-orange-400 to-orange-600 text-white">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <img src="images/image.png" alt="MarketHub Logo" class="h-14 w-19 object-contain">
                <a href="index.php">
                    <img src="images/mk.png" alt="Markets.lk Logo" class="h-14 object-contain">
                </a>
            </div>
            <button id="menuToggle" class="md:hidden text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
            <div class="hidden md:flex items-center space-x-4">
                <a href="index.php" class="nav-btn flex items-center px-4 py-2 text-black font-semibold bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2L2 9h2v9h4v-6h4v6h4V9h2L10 2z" /></svg>
                    Home
                </a>
                <button id="accountBtn" class="flex items-center px-4 py-2 text-black bg-gradient-to-r from-green-100 to-green-200 rounded-full hover:from-green-200 hover:to-green-300 transition-all duration-300 hidden">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?php echo $isSeller ? 'View Customer Chats' : 'Account'; ?>
                </button>
                <a href="index.php?open=addb" class="nav-btn relative inline-flex items-center px-6 py-2 text-black font-bold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] neon-yellow-border transition-all duration-300">
                    <span class="absolute -top-2 -left-2 bg-green-400 text-xs font-bold text-white px-2 py-1 rounded-full animated-free">Free</span>
                    Post Your Ad
                </a>
            </div>
        </nav>
        <div id="sidebar" class="sidebar fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-orange-400 to-orange-600 text-white transform -translate-x-full md:hidden z-50">
            <div class="flex justify-between items-center p-4 border-b border-orange-300">
                <div class="flex items-center space-x-2">
                    <img src="images/image.png" alt="MarketHub Logo" class="h-14 w-19 object-contain">
                    <a href="index.php">
                        <img src="images/mk.png" alt="Markets.lk Logo" class="h-14 object-contain">
                    </a>
                </div>
                <button id="closeSidebar" class="text-white focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="flex flex-col p-4 space-y-4">
                <a href="index.php" class="nav-btn flex items-center px-4 py-2 text-black font-semibold bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2L2 9h2v9h4v-6h4v6h4V9h2L10 2z" /></svg>
                    Home
                </a>
                <?php if ($currentUserId): ?>
                <a href="<?php echo $isSeller ? 'chat.php' : 'index.php#account'; ?>" class="nav-btn flex items-center px-4 py-2 text-black font-semibold bg-gradient-to-r from-green-100 to-green-200 rounded-full hover:from-green-200 hover:to-green-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?php echo $isSeller ? 'View Chats' : 'Account'; ?>
                </a>
                <?php endif; ?>
                <a href="index.php?open=addb" class="nav-btn relative inline-flex items-center px-6 py-2 text-black font-bold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] neon-yellow-border transition-all duration-300">
                    <span class="absolute -top-2 -left-2 bg-green-400 text-xs font-bold text-white px-2 py-1 rounded-full animated-free">Free</span>
                    Post Your Ad
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-6 py-8">
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="separator">/</span>
            <a href="index.php?category=<?php echo $ad['main_category_id']; ?>"><?php echo htmlspecialchars($mainCategory['name']); ?></a>
            <span class="separator">/</span>
            <a href="index.php?subcategory=<?php echo $ad['subcategory_id']; ?>"><?php echo htmlspecialchars($ad['category_name']); ?></a>
            <span class="separator">/</span>
            <span><?php echo htmlspecialchars($ad['title']); ?></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-2">
                <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                    <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($ad['title']); ?></h1>
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-orange-600">Rs <?php echo number_format($ad['sale_price'], 0); ?></h2>
                        <div class="text-gray-500"><?php echo timeAgo($ad['created_at']); ?></div>
                    </div>
                    <div class="text-sm text-gray-500 mt-2">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        <?php echo htmlspecialchars($ad['district_name'] . ', ' . $ad['area_name']); ?>
                    </div>
                </div>

                <!-- Media Gallery with swipe, watermark, and video controls -->
                <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                    <div class="gallery-container">
                        <div class="gallery-main" id="galleryMain">
                            <div id="mainMedia"></div>
                            <div class="loading-spinner" id="loadingSpinner"></div>
                        </div>

                        <?php if (count($mediaItems) > 1): ?>
                        <div class="gallery-thumbnails">
                            <?php foreach ($mediaItems as $index => $media): ?>
                            <div class="gallery-thumbnail <?php echo $index === 0 ? 'active' : ''; ?> <?php echo $media['type'] === 'video' ? 'video-thumbnail' : ''; ?>" 
                                 onclick="selectMedia(<?php echo $index; ?>)">
                                <?php if ($media['type'] === 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($media['path']); ?>" alt="Thumbnail">
                                <?php else: ?>
                                    <video muted playsinline style="width:100%;height:100%;object-fit:cover;">
                                        <source src="<?php echo htmlspecialchars($media['path']); ?>" type="video/mp4">
                                    </video>
                                    <div class="play-button">
                                        <i class="fas fa-play"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Image Fullscreen Overlay -->
                <div class="image-fullscreen-overlay" id="imageFullscreenOverlay">
                    <button class="fullscreen-close-btn" onclick="closeImageFullscreen()">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="fullscreen-nav">
                        <button class="fullscreen-nav-btn" onclick="prevImageFullscreen()">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="fullscreen-nav-btn" onclick="nextImageFullscreen()">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="image-fullscreen-container">
                        <img id="fullscreenImage" src="" alt="Fullscreen Image">
                        <div class="fullscreen-watermark">Markets.lk</div>
                    </div>
                    <div class="fullscreen-counter" id="fullscreenCounter"></div>
                </div>

                <div class="bg-gradient-to-br from-indigo-100 via-white to-blue-100 p-8 rounded-3xl shadow-2xl mb-6 transform transition-all duration-500 hover:scale-105 hover:shadow-3xl border border-indigo-200">
                    <h3 class="text-3xl font-extrabold text-indigo-900 mb-6 border-b-2 border-indigo-700 pb-3 inline-block">Description</h3>
                    <div class="bg-white p-6 rounded-xl shadow-lg hover:bg-indigo-50 transition-all duration-300 whitespace-pre-line text-gray-800 leading-relaxed">
                        <?php echo $ad['description']; ?>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-100 via-white to-blue-100 p-8 rounded-3xl shadow-2xl mb-6 transform transition-all duration-500 hover:scale-105 hover:shadow-3xl border border-indigo-200">
                    <h3 class="text-3xl font-extrabold text-indigo-900 mb-6 border-b-2 border-indigo-700 pb-3 inline-block">Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center space-x-4 bg-white p-4 rounded-xl shadow-lg hover:bg-indigo-50 transition-all duration-300">
                            <span class="text-indigo-600 text-2xl">📋</span>
                            <div>
                                <span class="text-gray-700 font-semibold block">Category</span>
                                <span class="text-indigo-900 font-medium"><?php echo htmlspecialchars($ad['category_name']); ?></span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4 bg-white p-4 rounded-xl shadow-lg hover:bg-indigo-50 transition-all duration-300">
                            <span class="text-indigo-600 text-2xl">📍</span>
                            <div>
                                <span class="text-gray-700 font-semibold block">Location</span>
                                <span class="text-indigo-900 font-medium"><?php echo htmlspecialchars($ad['district_name'] . ', ' . $ad['area_name']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($similarAds)): ?>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-xl font-semibold mb-4">Similar Ads</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <?php foreach ($similarAds as $similarAd): ?>
                        <?php 
                        // Check if ad has video or image
                        $hasVideo = !empty($similarAd['video_path']);
                        $mediaPath = $hasVideo ? $similarAd['video_path'] : 
                                    (!empty($similarAd['image_path']) ? $similarAd['image_path'] : 'images/placeholder.jpg');
                        ?>
                        <a href="ad-details.php?id=<?php echo $similarAd['id']; ?>" class="similar-ad-card">
                            <div class="similar-ad-image <?php echo $hasVideo ? 'video-ad' : ''; ?>">
                                <?php if ($hasVideo): ?>
                                    <video muted playsinline poster="<?php echo htmlspecialchars($similarAd['image_path'] ?: 'images/placeholder.jpg'); ?>" style="width:100%;height:100%;object-fit:cover;">
                                        <source src="<?php echo htmlspecialchars($similarAd['video_path']); ?>" type="video/mp4">
                                    </video>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($mediaPath); ?>" alt="<?php echo htmlspecialchars($similarAd['title']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="similar-ad-details">
                                <h4 class="font-semibold truncate"><?php echo htmlspecialchars($similarAd['title']); ?></h4>
                                <p class="text-orange-600 font-bold mt-1">Rs <?php echo number_format($similarAd['sale_price'], 0); ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo timeAgo($similarAd['created_at']); ?></p>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="md:col-span-1">
                <div class="contact-card mb-6 sticky top-4">
                    <h3 class="text-xl font-semibold mb-4"><?php echo htmlspecialchars($ad['seller_firstName'] . ' ' . $ad['seller_lastName']); ?></h3>
                    <p class="mb-4 text-sm">
                        <i class="fas fa-user mr-2"></i> Member since: <?php echo date('F Y', strtotime($ad['created_at'])); ?>
                    </p>
                    <div class="space-y-3">
                        <a href="tel:<?php echo htmlspecialchars($ad['telephone']); ?>" class="contact-btn flex items-center justify-center gap-2 py-3 w-full block">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($ad['telephone']); ?></span>
                        </a>
                        <?php if (!empty($ad['whatsapp_number'])): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $ad['whatsapp_number']); ?>" class="contact-btn flex items-center justify-center gap-2 py-3 w-full block bg-green-600 hover:bg-green-700 text-white" target="_blank">
                            <i class="fab fa-whatsapp"></i>
                            <span>WhatsApp</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($currentUserId && $currentUserId != $ad['seller_id']): ?>
                        <button id="chatBtn" class="contact-btn flex items-center justify-center gap-2 py-3 w-full block bg-gray-600 hover:bg-gray-700 text-white">
                            <i class="fas fa-comment"></i>
                            <span>Chat with Seller</span>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="mt-6 border-t border-gray-600 pt-4">
                        <h4 class="font-semibold mb-2">Safety Tips</h4>
                        <ul class="text-sm list-disc pl-4 space-y-1">
                            <li>Meet in a public place</li>
                            <li>Check the item before payment</li>
                            <li>Pay only after inspecting the item</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($currentUserId && $currentUserId != $ad['seller_id']): ?>
        <div class="chat-box" id="chatBox">
            <div class="chat-header">
                <span><?php echo htmlspecialchars($ad['seller_firstName'] . ' ' . $ad['seller_lastName']); ?></span>
                <button onclick="toggleChatBox()"><i class="fas fa-times"></i></button>
            </div>
            <div class="chat-body" id="chatBody">
                <div class="chat-product">
                    <img src="<?php echo htmlspecialchars($mediaItems[0]['path']); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>">
                    <span><?php echo htmlspecialchars($ad['title']); ?></span>
                </div>
            </div>
            <div class="chat-footer">
                <input type="text" id="chatInput" placeholder="Type a message...">
                <button onclick="sendMessage()">Send</button>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <footer class="bg-black text-white py-12 relative overflow-hidden">
        <div class="absolute bottom-0 left-0 w-full h-24">
            <svg class="w-full h-full" viewBox="0 0 1440 100" preserveAspectRatio="none">
                <path class="wave-path" fill="#ff6200" fill-opacity="0.3" d="M0,60 C360,120 1080,0 1440,60 L1440,100 L0,100 Z"></path>
            </svg>
        </div>
        <div class="container mx-auto px-6 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="flex flex-col items-center md:items-start">
                    <img src="images/image.png" alt="MarketHub Logo" class="h-20 w-28 mb-4 object-contain">
                    <a href="index.php">
                        <img src="images/mk.png" alt="Markets.lk Logo" class="h-14 object-contain">
                    </a>
                    <p class="text-gray-400 text-center md:text-left">Your trusted marketplace for buying and selling locally.</p>
                </div>
                <div class="text-center md:text-left">
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-400 hover:text-orange-500 transition-colors">Home</a></li>
                        <li><a href="about-us.php" class="text-gray-400 hover:text-orange-500 transition-colors">About Us</a></li>
                        <li><a href="sell.php" class="text-gray-400 hover:text-orange-500 transition-colors">Post Your Ad</a></li>
                        <li><a href="job.php" class="text-gray-400 hover:text-orange-500 transition-colors">Apply Jobs</a></li>
                        <li><a href="about-us.php" class="text-gray-400 hover:text-orange-500 transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="text-center md:text-left p-6">
                    <h3 class="text-lg font-semibold mb-4 text-white">Get in Touch</h3>
                    <div class="flex justify-center md:justify-start">
                        <a href="report.php" class="glow-button inline-block bg-orange-500 text-white font-semibold py-2 px-4 rounded-full hover:bg-orange-600 transition duration-300 focus:outline-none shadow-lg hover:shadow-orange-500/50">
                            Report Problem & Feedback
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center text-gray-400">
                © 2025 Markets.lk All rights reserved.
            </div>
        </div>
        <style>
            .wave-path { animation: wave 8s ease-in-out infinite; }
            @keyframes wave {
                0% { d: path('M0,60 C360,120 1080,0 1440,60 L1440,100 L0,100 Z'); }
                50% { d: path('M0,40 C360,100 1080,20 1440,80 L1440,100 L0,100 Z'); }
                100% { d: path('M0,60 C360,120 1080,0 1440,60 L1440,100 L0,100 Z'); }
            }
            footer { border: 2px solid #ff6200; box-shadow: 0 0 10px #ff6200; }
            .glow-button { box-shadow: 0 0 15px 5px rgba(249, 115, 22, 0.7); transition: all 0.3s ease; }
            .glow-button:hover { box-shadow: 0 0 25px 10px rgba(249, 115, 22, 0.9); }
        </style>
    </footer>

    <script>
        // Sidebar
        document.getElementById('menuToggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
        });
        document.getElementById('closeSidebar').addEventListener('click', () => {
            document.getElementById('sidebar').classList.remove('open');
        });

        // Media Gallery with swipe, video controls, and fullscreen
        let currentMediaIndex = 0;
        const mediaItems = <?php echo json_encode($mediaItems); ?>;
        const galleryMain = document.getElementById('galleryMain');
        const thumbnails = document.querySelectorAll('.gallery-thumbnail');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const imageFullscreenOverlay = document.getElementById('imageFullscreenOverlay');
        const fullscreenImage = document.getElementById('fullscreenImage');
        const fullscreenCounter = document.getElementById('fullscreenCounter');
        let currentVideo = null;
        let currentVideoControls = null;
        let isFullscreen = false;
        let hideControlsTimeout;

        function updateMainMedia() {
            const media = mediaItems[currentMediaIndex];
            let mediaHTML = '';
            
            // Show loading spinner
            loadingSpinner.style.display = 'block';
            
            if (media.type === 'image') {
                // For images: Create a container with watermark overlay
                mediaHTML = `
                    <div class="watermarked" style="width:100%;height:100%;position:relative;cursor:pointer;" onclick="openImageFullscreen(${currentMediaIndex})">
                        <img src="${media.path}" alt="Main Image" style="width:100%;height:100%;object-fit:contain;"
                             onload="hideLoadingSpinner()" onerror="hideLoadingSpinner()">
                    </div>
                `;
            } else {
                // For videos: Create video with controls, watermark overlay, and audio enabled
                mediaHTML = `
                    <div style="width:100%;height:100%;position:relative;">
                        <video id="mainVideo" controls playsinline style="width:100%;height:100%;object-fit:contain;background:#ffffff;">
                            <source src="${media.path}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <div class="video-watermark">Markets.lk</div>
                        <div class="video-controls-container">
                            <div class="video-controls">
                                <button class="control-btn play-pause-btn" onclick="togglePlayPause()">
                                    <i class="fas fa-play"></i>
                                </button>
                                <div class="time-display">
                                    <span class="current-time">0:00</span> / <span class="duration">0:00</span>
                                </div>
                                <div class="progress-container" onclick="seekVideo(event)">
                                    <div class="progress-bar"></div>
                                </div>
                                <div class="volume-container">
                                    <button class="control-btn volume-btn" onclick="toggleMute()">
                                        <i class="fas fa-volume-up"></i>
                                    </button>
                                    <div class="volume-slider">
                                        <input type="range" min="0" max="1" step="0.1" value="1" oninput="changeVolume(this.value)">
                                    </div>
                                </div>
                                <button class="control-btn fullscreen-btn" onclick="toggleFullscreen()">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Update gallery main content
            galleryMain.innerHTML = mediaHTML;
            loadingSpinner.style.display = 'block';
            
            // Pause previous video if exists
            if (currentVideo) {
                currentVideo.pause();
                currentVideo.removeEventListener('timeupdate', updateProgress);
                currentVideo.removeEventListener('loadedmetadata', updateDuration);
                currentVideo.removeEventListener('play', updatePlayButton);
                currentVideo.removeEventListener('pause', updatePlayButton);
                currentVideo.removeEventListener('volumechange', updateVolumeButton);
                currentVideo.removeEventListener('enterpictureinpicture', () => {});
                currentVideo.removeEventListener('leavepictureinpicture', () => {});
            }
            
            // Get the new video element if it exists
            currentVideo = galleryMain.querySelector('video');
            currentVideoControls = galleryMain.querySelector('.video-controls-container');
            
            if (currentVideo) {
                // Set video to play with sound by default
                currentVideo.muted = false;
                currentVideo.volume = 1;
                
                // Add event listeners for video
                currentVideo.addEventListener('loadedmetadata', updateDuration);
                currentVideo.addEventListener('timeupdate', updateProgress);
                currentVideo.addEventListener('play', updatePlayButton);
                currentVideo.addEventListener('pause', updatePlayButton);
                currentVideo.addEventListener('volumechange', updateVolumeButton);
                currentVideo.addEventListener('loadeddata', hideLoadingSpinner);
                currentVideo.addEventListener('error', hideLoadingSpinner);
                
                // Auto-play the video
                currentVideo.play().catch(e => {
                    console.log('Autoplay prevented:', e);
                    // Show play button if autoplay is blocked
                    updatePlayButton();
                });
                
                // Update controls initially
                updatePlayButton();
                updateVolumeButton();
                updateDuration();
                
                // Show/hide controls on hover
                galleryMain.addEventListener('mousemove', showControls);
                galleryMain.addEventListener('mouseleave', () => {
                    if (!currentVideo.paused) {
                        hideControlsAfterDelay();
                    }
                });
                
                // Show controls when video is paused
                currentVideo.addEventListener('pause', () => {
                    showControls();
                    clearTimeout(hideControlsTimeout);
                });
                
                // Hide controls when video starts playing
                currentVideo.addEventListener('play', () => {
                    hideControlsAfterDelay();
                });
                
                // Handle fullscreen change
                document.addEventListener('fullscreenchange', handleFullscreenChange);
                document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
                document.addEventListener('mozfullscreenchange', handleFullscreenChange);
                document.addEventListener('MSFullscreenChange', handleFullscreenChange);
            } else {
                hideLoadingSpinner();
            }
            
            // Update thumbnails
            thumbnails.forEach((thumb, i) => {
                thumb.classList.toggle('active', i === currentMediaIndex);
            });
        }

        // Image Fullscreen Functions
        let currentFullscreenImageIndex = 0;

        function openImageFullscreen(index) {
            const imageItems = mediaItems.filter(item => item.type === 'image');
            if (imageItems.length === 0) return;
            
            // Find the correct image index
            let imageIndex = 0;
            for (let i = 0; i < mediaItems.length; i++) {
                if (mediaItems[i].type === 'image') {
                    if (i >= index) {
                        break;
                    }
                    imageIndex++;
                }
            }
            currentFullscreenImageIndex = imageIndex;
            
            const image = imageItems[currentFullscreenImageIndex];
            fullscreenImage.src = image.path;
            fullscreenImage.onload = function() {
                updateFullscreenCounter();
            };
            
            imageFullscreenOverlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeImageFullscreen() {
            imageFullscreenOverlay.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function prevImageFullscreen() {
            const imageItems = mediaItems.filter(item => item.type === 'image');
            if (imageItems.length === 0) return;
            
            currentFullscreenImageIndex = (currentFullscreenImageIndex - 1 + imageItems.length) % imageItems.length;
            const image = imageItems[currentFullscreenImageIndex];
            fullscreenImage.src = image.path;
            updateFullscreenCounter();
        }

        function nextImageFullscreen() {
            const imageItems = mediaItems.filter(item => item.type === 'image');
            if (imageItems.length === 0) return;
            
            currentFullscreenImageIndex = (currentFullscreenImageIndex + 1) % imageItems.length;
            const image = imageItems[currentFullscreenImageIndex];
            fullscreenImage.src = image.path;
            updateFullscreenCounter();
        }

        function updateFullscreenCounter() {
            const imageItems = mediaItems.filter(item => item.type === 'image');
            fullscreenCounter.textContent = `${currentFullscreenImageIndex + 1} / ${imageItems.length}`;
        }

        // Close fullscreen on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && imageFullscreenOverlay.style.display === 'flex') {
                closeImageFullscreen();
            }
        });

        // Close fullscreen when clicking outside the image
        imageFullscreenOverlay.addEventListener('click', (e) => {
            if (e.target === imageFullscreenOverlay || e.target.classList.contains('image-fullscreen-container')) {
                closeImageFullscreen();
            }
        });

        // Keyboard navigation for fullscreen
        document.addEventListener('keydown', (e) => {
            if (imageFullscreenOverlay.style.display === 'flex') {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    prevImageFullscreen();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    nextImageFullscreen();
                }
            }
        });

        // Touch swipe for fullscreen images
        let touchStartXFullscreen = 0;
        let touchEndXFullscreen = 0;
        const swipeThresholdFullscreen = 50;

        imageFullscreenOverlay.addEventListener('touchstart', e => {
            touchStartXFullscreen = e.touches[0].clientX;
        }, { passive: true });

        imageFullscreenOverlay.addEventListener('touchend', e => {
            touchEndXFullscreen = e.changedTouches[0].clientX;
            const diff = touchStartXFullscreen - touchEndXFullscreen;
            if (Math.abs(diff) > swipeThresholdFullscreen) {
                if (diff > 0) {
                    nextImageFullscreen();
                } else {
                    prevImageFullscreen();
                }
            }
        }, { passive: true });

        function hideLoadingSpinner() {
            if (loadingSpinner) {
                loadingSpinner.style.display = 'none';
            }
        }

        function togglePlayPause() {
            if (!currentVideo) return;
            if (currentVideo.paused) {
                currentVideo.play();
            } else {
                currentVideo.pause();
            }
        }

        function toggleMute() {
            if (!currentVideo) return;
            currentVideo.muted = !currentVideo.muted;
            updateVolumeButton();
        }

        function changeVolume(value) {
            if (!currentVideo) return;
            currentVideo.volume = parseFloat(value);
            currentVideo.muted = (value == 0);
            updateVolumeButton();
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement && !document.webkitFullscreenElement && 
                !document.mozFullScreenElement && !document.msFullscreenElement) {
                // Enter fullscreen
                if (galleryMain.requestFullscreen) {
                    galleryMain.requestFullscreen();
                } else if (galleryMain.webkitRequestFullscreen) {
                    galleryMain.webkitRequestFullscreen();
                } else if (galleryMain.mozRequestFullScreen) {
                    galleryMain.mozRequestFullScreen();
                } else if (galleryMain.msRequestFullscreen) {
                    galleryMain.msRequestFullscreen();
                }
            } else {
                // Exit fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        }

        function handleFullscreenChange() {
            isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || 
                             document.mozFullScreenElement || document.msFullscreenElement);
            galleryMain.classList.toggle('fullscreen', isFullscreen);
            
            // Update fullscreen button icon
            const fullscreenBtn = galleryMain.querySelector('.fullscreen-btn i');
            if (fullscreenBtn) {
                fullscreenBtn.className = isFullscreen ? 'fas fa-compress' : 'fas fa-expand';
            }
            
            // Update video controls position
            if (currentVideoControls) {
                if (isFullscreen) {
                    currentVideoControls.style.position = 'fixed';
                    currentVideoControls.style.bottom = '20px';
                    currentVideoControls.style.left = '20px';
                    currentVideoControls.style.right = '20px';
                } else {
                    currentVideoControls.style.position = 'absolute';
                    currentVideoControls.style.bottom = '10px';
                    currentVideoControls.style.left = '10px';
                    currentVideoControls.style.right = '10px';
                }
            }
        }

        function seekVideo(event) {
            if (!currentVideo) return;
            const progressContainer = event.currentTarget;
            const rect = progressContainer.getBoundingClientRect();
            const percent = (event.clientX - rect.left) / rect.width;
            currentVideo.currentTime = percent * currentVideo.duration;
            showControls();
        }

        function updateProgress() {
            if (!currentVideo) return;
            const progressBar = galleryMain.querySelector('.progress-bar');
            const currentTimeDisplay = galleryMain.querySelector('.current-time');
            
            if (progressBar) {
                const percent = (currentVideo.currentTime / currentVideo.duration) * 100;
                progressBar.style.width = percent + '%';
            }
            
            if (currentTimeDisplay) {
                currentTimeDisplay.textContent = formatTime(currentVideo.currentTime);
            }
        }

        function updateDuration() {
            if (!currentVideo) return;
            const durationDisplay = galleryMain.querySelector('.duration');
            if (durationDisplay) {
                durationDisplay.textContent = formatTime(currentVideo.duration);
            }
        }

        function updatePlayButton() {
            if (!currentVideo) return;
            const playBtn = galleryMain.querySelector('.play-pause-btn i');
            if (playBtn) {
                playBtn.className = currentVideo.paused ? 'fas fa-play' : 'fas fa-pause';
            }
        }

        function updateVolumeButton() {
            if (!currentVideo) return;
            const volumeBtn = galleryMain.querySelector('.volume-btn i');
            if (volumeBtn) {
                if (currentVideo.muted || currentVideo.volume === 0) {
                    volumeBtn.className = 'fas fa-volume-mute';
                } else if (currentVideo.volume < 0.5) {
                    volumeBtn.className = 'fas fa-volume-down';
                } else {
                    volumeBtn.className = 'fas fa-volume-up';
                }
            }
        }

        function formatTime(seconds) {
            if (isNaN(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
        }

        function showControls() {
            if (currentVideoControls) {
                currentVideoControls.style.opacity = '1';
                clearTimeout(hideControlsTimeout);
                if (!currentVideo.paused) {
                    hideControlsAfterDelay();
                }
            }
        }

        function hideControlsAfterDelay() {
            clearTimeout(hideControlsTimeout);
            hideControlsTimeout = setTimeout(() => {
                if (currentVideoControls && !currentVideo.paused && !isFullscreen) {
                    currentVideoControls.style.opacity = '0';
                }
            }, 2000);
        }

        function selectMedia(index) {
            currentMediaIndex = index;
            updateMainMedia();
        }

        // Initialize main media
        updateMainMedia();

        // Touch swipe
        let touchStartX = 0;
        let touchEndX = 0;
        const swipeThreshold = 50;

        galleryMain.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
            showControls();
        }, { passive: true });

        galleryMain.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchEndX;
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    currentMediaIndex = (currentMediaIndex + 1) % mediaItems.length;
                } else {
                    currentMediaIndex = (currentMediaIndex - 1 + mediaItems.length) % mediaItems.length;
                }
                updateMainMedia();
            }
        }, { passive: true });

        // Mouse drag
        let isDragging = false;
        let mouseStartX = 0;

        galleryMain.addEventListener('mousedown', e => {
            isDragging = true;
            mouseStartX = e.clientX;
            galleryMain.style.cursor = 'grabbing';
            showControls();
        });

        galleryMain.addEventListener('mousemove', e => {
            if (!isDragging) return;
            e.preventDefault();
        });

        galleryMain.addEventListener('mouseup', e => {
            if (!isDragging) return;
            isDragging = false;
            galleryMain.style.cursor = 'pointer';
            const diff = mouseStartX - e.clientX;
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    currentMediaIndex = (currentMediaIndex + 1) % mediaItems.length;
                } else {
                    currentMediaIndex = (currentMediaIndex - 1 + mediaItems.length) % mediaItems.length;
                }
                updateMainMedia();
            }
        });

        galleryMain.addEventListener('mouseleave', () => {
            isDragging = false;
            galleryMain.style.cursor = 'pointer';
        });

        // Keyboard navigation
        document.addEventListener('keydown', e => {
            if (e.key === 'ArrowRight') {
                currentMediaIndex = (currentMediaIndex + 1) % mediaItems.length;
                updateMainMedia();
            } else if (e.key === 'ArrowLeft') {
                currentMediaIndex = (currentMediaIndex - 1 + mediaItems.length) % mediaItems.length;
                updateMainMedia();
            } else if (e.key === ' ' && currentVideo) {
                e.preventDefault();
                togglePlayPause();
            } else if (e.key === 'f' || e.key === 'F') {
                e.preventDefault();
                toggleFullscreen();
            } else if (e.key === 'm' || e.key === 'M') {
                e.preventDefault();
                toggleMute();
            }
        });

        // Chat functionality
        const chatBox = document.getElementById('chatBox');
        const chatBody = document.getElementById('chatBody');
        const chatInput = document.getElementById('chatInput');
        const chatBtn = document.getElementById('chatBtn');
        let pollingInterval;

        function toggleChatBox() {
            chatBox.style.display = chatBox.style.display === 'block' ? 'none' : 'block';
            if (chatBox.style.display === 'block') {
                loadMessages();
                startPolling();
            } else {
                stopPolling();
            }
        }

        function loadMessages() {
            fetch('get_messages.php?ad_id=<?php echo $adId; ?>&seller_id=<?php echo $ad['seller_id']; ?>')
                .then(response => response.ok ? response.json() : Promise.reject(response))
                .then(data => {
                    if (data.success && Array.isArray(data.messages)) {
                        chatBody.innerHTML = `<div class="chat-product"><img src="<?php echo htmlspecialchars($mediaItems[0]['path']); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>"><span><?php echo htmlspecialchars($ad['title']); ?></span></div>`;
                        if (data.messages.length === 0) {
                            chatBody.innerHTML += `<div class="chat-message received"><div class="message-content">No messages yet. Start the conversation!</div></div>`;
                        } else {
                            data.messages.forEach(msg => {
                                const isSent = msg.sender_id == <?php echo $currentUserId ?: 0; ?>;
                                chatBody.innerHTML += `
                                    <div class="chat-message ${isSent ? 'sent' : 'received'}">
                                        <div class="message-content">${msg.message}</div>
                                        <div class="message-time">${new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</div>
                                    </div>`;
                            });
                        }
                        chatBody.scrollTop = chatBody.scrollHeight;
                    }
                })
                .catch(() => {
                    chatBody.innerHTML += `<div class="chat-message received"><div class="message-content">Error loading messages.</div></div>`;
                });
        }

        function sendMessage() {
            const message = chatInput.value.trim();
            if (!message) return;

            fetch('send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ad_id: <?php echo $adId; ?>,
                    sender_id: <?php echo $currentUserId ?: 0; ?>,
                    receiver_id: <?php echo $ad['seller_id']; ?>,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    chatInput.value = '';
                    loadMessages();
                }
            });
        }

        function startPolling() {
            if (pollingInterval) clearInterval(pollingInterval);
            pollingInterval = setInterval(loadMessages, 3000);
        }

        function stopPolling() {
            if (pollingInterval) clearInterval(pollingInterval);
        }

        document.getElementById('chatBtn')?.addEventListener('click', toggleChatBox);
        chatInput?.addEventListener('keypress', e => { if (e.key === 'Enter') sendMessage(); });

        window.onload = function () {
            const isLoggedIn = <?php echo $currentUserId ? 'true' : 'false'; ?>;
            if (isLoggedIn) {
                document.getElementById('accountBtn')?.classList.remove('hidden');
            }
        };
    </script>
</body>
</html>