<?php
session_start();

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

function timeAgo($timestamp) {
    // Define timezone, adjust if needed
    $timezone = new DateTimeZone('Asia/Colombo'); // or your server timezone

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



// Function to safely render specific HTML tags
function safeRenderHtml($text) {
    $allowedTags = ['b', 'i', 'br'];
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = nl2br($text);
    foreach ($allowedTags as $tag) {
        $text = preg_replace("/<$tag>/i", "<$tag>", $text);
        $text = preg_replace("/<\/$tag>/i", "</$tag>", $text);
    }
    $text = strip_tags($text, '<b><i><br>');
    return $text;
}

// Get job ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: jobs.php");
    exit();
}

$jobId = $_GET['id'];

// Fetch job details with employer information
$sql = "SELECT j.*, jc.name as category_name, d.name as district_name, a.name as area_name,
        u.firstName as employer_firstName, u.lastName as employer_lastName, u.id as employer_id
        FROM jobs j
        JOIN job_categories jc ON j.job_position_id = jc.id
        JOIN districts d ON j.district = d.name
        JOIN areas a ON j.area = a.name
        JOIN users u ON j.user_id = u.id
        WHERE j.id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $jobId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: jobs.php");
    exit();
}

$job = $result->fetch_assoc();

// Fetch all images for this job
$imagesSql = "SELECT image_path FROM job_images WHERE job_id = ? ORDER BY id ASC";
$imagesStmt = $conn->prepare($imagesSql);
if ($imagesStmt === false) {
    die("Prepare failed: " . $conn->error);
}
$imagesStmt->bind_param("i", $jobId);
$imagesStmt->execute();
$imagesResult = $imagesStmt->get_result();
$images = [];

while ($image = $imagesResult->fetch_assoc()) {
    $images[] = $image['image_path'];
}

if (empty($images)) {
    $images[] = 'images/placeholder.jpg';
}

// Fetch similar jobs from the same category
$similarJobsSql = "SELECT j.id, j.job_title, j.salary, j.created_at, j.company,
                 (SELECT image_path FROM job_images WHERE job_id = j.id LIMIT 1) as image_path
                 FROM jobs j
                 WHERE j.job_position_id = ? AND j.id != ?
                 ORDER BY j.created_at DESC
                 LIMIT 4";
$similarJobsStmt = $conn->prepare($similarJobsSql);
if ($similarJobsStmt === false) {
    die("Prepare failed: " . $conn->error);
}
$similarJobsStmt->bind_param("ii", $job['job_position_id'], $jobId);
$similarJobsStmt->execute();
$similarJobsResult = $similarJobsStmt->get_result();
$similarJobs = [];

while ($similarJob = $similarJobsResult->fetch_assoc()) {
    $similarJobs[] = $similarJob;
}

// Determine if the user is logged in
$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Check if the logged-in user is an employer
$isEmployer = false;
if ($currentUserId) {
    $employerCheckSql = "SELECT COUNT(*) as job_count FROM jobs WHERE user_id = ?";
    $employerCheckStmt = $conn->prepare($employerCheckSql);
    if ($employerCheckStmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $employerCheckStmt->bind_param("i", $currentUserId);
    $employerCheckStmt->execute();
    $employerCheckResult = $employerCheckStmt->get_result();
    $employerCheck = $employerCheckResult->fetch_assoc();
    $isEmployer = $employerCheck['job_count'] > 0;
    $employerCheckStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['job_title']); ?> - Markets.lk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v12.0" async defer></script>
    <style>
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        @keyframes pulseGlow {
            0% { box-shadow: 0 0 10px #ff6200, 0 0 20px #ff6200; transform: scale(1); }
            50% { box-shadow: 0 0 15px #ff6200, 0 0 30px #ff6200; transform: scale(1.02); }
            100% { box-shadow: 0 0 10px #ff6200, 0 0 20px #ff6200; transform: scale(1); }
        }
        @keyframes slowJump {
            0% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0); }
        }
        .neon-yellow-border {
            box-shadow: 0 0 10px #FFFF00, 0 0 20px #FFFF00, 0 0 30px #FFFF00;
        }
        .animated-free {
            animation: pulse 1.5s infinite;
            transform-origin: center;
        }

        .login-popup {
            background: linear-gradient(145deg, #1a1a3d, #2a2a5e);
            border: 2px solid #ff6200;
            border-radius: 15px 5px 15px 5px;
            box-shadow: 0 0 20px #ff6200, inset 0 0 10px #00f6ff;
            animation: pulseGlow 2s ease-in-out infinite;
            transform: perspective(1000px) rotateX(5deg);
            transition: transform 0.5s ease;
        }
        .login-popup:hover {
            transform: perspective(1000px) rotateX(0deg);
        }
        .cyber-input {
            background: #0f0f2a;
            border: 1px solid #ff6200;
            border-radius: 8px;
            color: #00f6ff;
            transition: all 0.3s ease;
        }
        .cyber-input:focus {
            border-color: #00f6ff;
            box-shadow: 0 0 12px rgba(0, 246, 255, 0.7);
        }
        .cyber-btn {
            background: linear-gradient(90deg, #ff6200, #00f6ff);
            border-radius: 8px;
            color: #ffffff;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .cyber-btn:hover {
            transform: scale(1.05);
        }
        .social-btn {
            background: linear-gradient(90deg, #3b82f6, #1e3a8a);
            border-radius: 8px;
            color: #ffffff;
            transition: transform 0.3s ease;
        }
        .social-btn:hover {
            transform: scale(1.05);
        }
        .close-btn {
            background: #ff6200;
            color: #ffffff;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: -15px;
            right: -15px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .close-btn:hover {
            transform: rotate(90deg);
        }
        .divider {
            background: linear-gradient(to right, transparent, #ff6200, transparent);
            height: 1px;
            margin: 1rem 0;
        }
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
        /* Image Gallery */
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
        }
        .gallery-main img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: #f7f7f7;
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
        }
        .gallery-thumbnail.active {
            border-color: #ff6200;
        }
        .gallery-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .nav-btn-gallery {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            color: #333;
            font-size: 20px;
            transition: all 0.3s ease;
        }
        .nav-btn-gallery:hover {
            background: rgba(255, 255, 255, 0.9);
        }
        .prev-btn {
            left: 10px;
        }
        .next-btn {
            right: 10px;
        }
        .description-box {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .job-detail-item {
            display: flex;
            margin-bottom: 12px;
        }
        .job-detail-label {
            width: 140px;
            font-weight: 600;
            color: #666;
        }
        .job-detail-value {
            flex: 1;
            color: #333;
        }
        .similar-job-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .similar-job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .similar-job-image {
            height: 150px;
            overflow: hidden;
        }
        .similar-job-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .similar-job-details {
            padding: 12px;
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
        @media (max-width: 640px) {
            .contact-btn {
                font-size: 14px;
                padding: 10px 0;
                margin-bottom: 8px;
            }
            .contact-card {
                padding: 15px;
            }
            .contact-btn i {
                margin-right: 6px;
            }
            .contact-btn span {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }
    </style>
</head>
<body class="font-chakra bg-gray-100">
    <!-- Header Section -->
    <header class="bg-gradient-to-r from-orange-400 to-orange-600 text-white">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <img src="images/image.png" alt="MarketHub Logo" class="h-14 w-19 object-contain">
                <a href="index.php">
        <img src="images/mk.png" alt="Markets.lk Logo" class="h-14 object-contain">
    </a>

            </div>
            <button id="menuToggle" class="md:hidden text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
            <div class="hidden md:flex items-center space-x-4">
            <a href="index.php" class="nav-btn flex items-center px-4 py-2 text-black font-semibold bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300">
    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path d="M10 2L2 9h2v9h4v-6h4v6h4V9h2L10 2z" />
    </svg>
    Home
</a>

              
                <a href="jobs.php" class="nav-btn flex items-center px-4 py-2 text-black bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Apply Jobs
                </a>
                <a href="sell.php" class="nav-btn relative inline-flex items-center px-6 py-2 text-black font-bold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] neon-yellow-border transition-all duration-300">
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
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="flex flex-col p-4 space-y-4">
                <a href="index.php" class="nav-btn flex items-center px-4 py-2 text-black font-semibold bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300">
    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
        <path d="M10 2L2 9h2v9h4v-6h4v6h4V9h2L10 2z" />
    </svg>
    Home
</a>
                <a href="jobs.php" class="nav-btn flex items-center px-4 py-2 text-black bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Apply Jobs
                </a>
                <a href="sell.php" class="nav-btn relative inline-flex items-center px-6 py-2 text-black font-bold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] neon-yellow-border transition-all duration-300">
                    <span class="absolute -top-2 -left-2 bg-green-400 text-xs font-bold text-white px-2 py-1 rounded-full animated-free">Free</span>
                    Post Your Ad
                </a>
            </div>
        </div>
    </header>

    <!-- Login Popup -->
    <div id="loginPopup" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50">
        <div class="login-popup w-full max-w-md p-8 relative">
            <button id="closePopup" class="close-btn">×</button>
            <div id="loginForm" class="space-y-6">
                <h2 class="text-4xl font-bold text-center bg-clip-text text-transparent bg-gradient-to-r from-[#ff6200] to-[#00f6ff]">Cyber Access</h2>
                <div class="space-y-4">
                    <input id="loginEmail" type="email" placeholder="Email" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                    <input id="loginPassword" type="password" placeholder="Password" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                </div>
                <button id="submitLogin" class="w-full cyber-btn py-3 font-semibold">Access System</button>
                <div class="divider"></div>
                <div class="flex justify-center space-x-4">
                    <button id="googleLogin" class="social-btn px-4 py-2 font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 48 48"><path fill="#4285F4" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path><path fill="#34A853" d="M24 44c5.98 0 11.01-2.04 14.7-5.55l-7.37-5.73c-2.08 1.39-4.69 2.28-7.33 2.28-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 38.62 14.62 44 24 44z"></path><path fill="#FBBC05" d="M46.44 13.22l-6.85 6.85c.92 1.97 1.41 4.09 1.41 6.43 0 2.34-.49 4.46-1.41 6.43l6.85 6.85c1.95-2.14 3.56-4.76 4.56-7.78C48.49 25.71 48.49 19.29 46.44 13.22z"></path><path fill="#EA4335" d="M10.54 19.41l-7.98-6.19C1.56 16.24 1 19.62 1 23c0 3.38.56 6.76 1.56 9.78l7.98-6.19c-.89-1.97-1.54-4.09-1.54-6.59 0-2.5.65-4.62 1.54-6.59z"></path></svg>
                        Google
                    </button>
                    <button id="facebookLogin" class="social-btn px-4 py-2 font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24"><path fill="#3b5998" d="M22.675 0H1.325C.593 0 0 .593 0 1.325v21.351C0 23.407.593 24 1.325 24H12.82v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116c.73 0 1.323-.593 1.323-1.325V1.325C24 .593 23.407 0 22.675 0z"></path></svg>
                        Facebook
                    </button>
                </div>
                <div class="text-center">
                    <button id="showRegister" class="text-sm text-[#00f6ff] hover:text-[#ff6200]">New User? Register</button>
                </div>
            </div>
            <div id="registerForm" class="space-y-6 hidden">
                <h2 class="text-4xl font-bold text-center bg-clip-text text-transparent bg-gradient-to-r from-[#ff6200] to-[#00f6ff]">Join Network</h2>
                <div class="space-y-4">
                    <input id="firstName" type="text" placeholder="First Name" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                    <input id="lastName" type="text" placeholder="Last Name" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                    <input id="registerEmail" type="email" placeholder="Email" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                    <input id="registerPassword" type="password" placeholder="Password" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                    <input id="confirmPassword" type="password" placeholder="Confirm Password" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                </div>
                <button id="submitRegister" class="w-full cyber-btn py-3 font-semibold">Create Profile</button>
                <div class="divider"></div>
                <div class="flex justify-center space-x-4">
                    <button id="googleRegister" class="social-btn px-4 py-2 font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 48 48"><path fill="#4285F4" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path><path fill="#34A853" d="M24 44c5.98 0 11.01-2.04 14.7-5.55l-7.37-5.73c-2.08 1.39-4.69 2.28-7.33 2.28-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 38.62 14.62 44 24 44z"></path><path fill="#FBBC05" d="M46.44 13.22l-6.85 6.85c.92 1.97 1.41 4.09 1.41 6.43 0 2.34-.49 4.46-1.41 6.43l6.85 6.85c1.95-2.14 3.56-4.76 4.56-7.78C48.49 25.71 48.49 19.29 46.44 13.22z"></path><path fill="#EA4335" d="M10.54 19.41l-7.98-6.19C1.56 16.24 1 19.62 1 23c0 3.38.56 6.76 1.56 9.78l7.98-6.19c-.89-1.97-1.54-4.09-1.54-6.59 0-2.5.65-4.62 1.54-6.59z"></path></svg>
                        Google
                    </button>
                    <button id="facebookRegister" class="social-btn px-4 py-2 font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24"><path fill="#3b5998" d="M22.675 0H1.325C.593 0 0 .593 0 1.325v21.351C0 23.407.593 24 1.325 24H12.82v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116c.73 0 1.323-.593 1.323-1.325V1.325C24 .593 23.407 0 22.675 0z"></path></svg>
                        Facebook
                    </button>
                </div>
                <div class="text-center">
                    <button id="showLogin" class="text-sm text-[#00f6ff] hover:text-[#ff6200]">Back to Login</button>
                </div>
            </div>
            <div id="accountPopup" class="space-y-6 hidden">
                <h2 class="text-4xl font-bold text-center bg-clip-text text-transparent bg-gradient-to-r from-[#ff6200] to-[#00f6ff]">Account Details</h2>
                <div class="space-y-4 account-info">
                    <p><strong>First Name:</strong> <span id="accountFirstName"></span></p>
                    <p><strong>Last Name:</strong> <span id="accountLastName"></span></p>
                    <p><strong>Email:</strong> <span id="accountEmail"></span></p>
                </div>
                <button id="logoutBtn" class="w-full cyber-btn py-3 font-semibold">Logout</button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="separator">/</span>
            <a href="jobs.php">Jobs</a>
            <span class="separator">/</span>
            <a href="jobs.php?category=<?php echo $job['job_position_id']; ?>"><?php echo htmlspecialchars($job['category_name']); ?></a>
            <span class="separator">/</span>
            <span><?php echo htmlspecialchars($job['job_title']); ?></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Left Column - Job Details -->
            <div class="md:col-span-2">
                <!-- Job Title and Salary -->
                <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                    <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($job['job_title']); ?></h1>
                    <div class="flex justify-between items-center">
    <h2 class="text-2xl font-bold text-orange-600">
        <?php echo $job['salary'] ? 'LKR ' . number_format($job['salary'], 0) : 'Negotiable'; ?>
    </h2>
    <div class="text-gray-500">
        <?php echo timeAgo($job['created_at']); ?>
    </div>
</div>


                    <div class="text-sm text-gray-500 mt-2">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        <?php echo htmlspecialchars($job['district_name'] . ', ' . $job['area_name']); ?>
                    </div>
                </div>

                <!-- Image Gallery -->
                <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                    <div class="gallery-container">
                        <div class="gallery-main">
                            <img id="mainImage" src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($job['job_title']); ?>">
                            <?php if (count($images) > 1): ?>
                                <div class="prev-btn nav-btn-gallery" onclick="changeImage(-1)"><i class="fas fa-chevron-left"></i></div>
                                <div class="next-btn nav-btn-gallery" onclick="changeImage(1)"><i class="fas fa-chevron-right"></i></div>
                            <?php endif; ?>
                        </div>
                        <?php if (count($images) > 1): ?>
                            <div class="gallery-thumbnails">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="gallery-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" onclick="selectImage(<?php echo $index; ?>)">
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Thumbnail">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Job Description -->
             <div class="bg-gradient-to-br from-indigo-100 via-white to-blue-100 p-8 rounded-3xl shadow-2xl mb-6 transform transition-all duration-500 hover:scale-105 hover:shadow-3xl border border-indigo-200">
  <h3 class="text-3xl font-extrabold text-indigo-900 mb-6 border-b-2 border-indigo-700 pb-3 inline-block ">Job Description</h3>
  <div class="bg-white p-6 rounded-xl shadow-lg hover:bg-indigo-50 transition-all duration-300 whitespace-pre-line text-gray-800 leading-relaxed">
    <?php echo $job['job_description']; ?>
  </div>
</div>

                <!-- Job Details -->
                <div class="bg-gradient-to-br from-indigo-100 via-white to-blue-100 p-8 rounded-3xl shadow-2xl mb-6 transform transition-all duration-500 hover:scale-105 hover:shadow-3xl border border-indigo-200">
  <h3 class="text-3xl font-extrabold text-indigo-900 mb-6 border-b-2 border-indigo-700 pb-3 inline-block ">Details</h3>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="flex items-center space-x-4 bg-white p-4 rounded-xl shadow-lg hover:bg-indigo-50 transition-all duration-300">
      <span class="text-indigo-600 text-2xl">📋</span>
      <div>
        <span class="text-gray-700 font-semibold block">Category</span>
        <span class="text-indigo-900 font-medium">Landscaping</span>
      </div>
    </div>
    <div class="flex items-center space-x-4 bg-white p-4 rounded-xl shadow-lg hover:bg-indigo-50 transition-all duration-300">
      <span class="text-indigo-600 text-2xl">🏢</span>
      <div>
        <span class="text-gray-700 font-semibold block">Company</span>
        <span class="text-indigo-900 font-medium">efefe</span>
      </div>
    </div>
    <div class="flex items-center space-x-4 bg-white p-4 rounded-xl shadow-lg hover:bg-indigo-50 transition-all duration-300">
      <span class="text-indigo-600 text-2xl">📍</span>
      <div>
        <span class="text-gray-700 font-semibold block">Location</span>
        <span class="text-indigo-900 font-medium">Kandy, Poojapitaya</span>
      </div>
    </div>
    <div class="flex items-center space-x-4 bg-white p-4 rounded-xl shadow-lg hover:bg-indigo-50 transition-all duration-300">
      <span class="text-indigo-600 text-2xl">⏰</span>
      <div>
    <span class="text-gray-700 font-semibold block">Posted</span>
    <span class="text-indigo-900 font-medium">
        <?php echo timeAgo($job['created_at']); ?>
    </span>
</div>

    </div>
  </div>
</div>
                <!-- Similar Jobs -->
                <?php if (!empty($similarJobs)): ?>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-xl font-semibold mb-4">Similar Jobs</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <?php foreach ($similarJobs as $similarJob): ?>
                            <?php 
                                $similarJobImg = !empty($similarJob['image_path']) ? $similarJob['image_path'] : 'images/placeholder.jpg';
                                $salary = $similarJob['salary'] ? 'LKR ' . number_format($similarJob['salary'], 0) : 'Negotiable';
                            ?>
                            <a href="job-details.php?id=<?php echo $similarJob['id']; ?>" class="similar-job-card">
                                <div class="similar-job-image">
                                    <img src="<?php echo htmlspecialchars($similarJobImg); ?>" alt="<?php echo htmlspecialchars($similarJob['job_title']); ?>">
                                </div>
                                <div class="similar-job-details">
                                    <h4 class="font-semibold truncate"><?php echo htmlspecialchars($similarJob['job_title']); ?></h4>
                                    <p class="text-orange-600 font-bold mt-1"><?php echo $salary; ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($similarJob['company']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo timeAgo($similarJob['created_at']); ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column - Employer Contact -->
            <div class="md:col-span-1">
                <!-- Employer Info Card -->
                <div class="contact-card mb-6 sticky top-4">
                    <h3 class="text-xl font-semibold mb-4"><?php echo htmlspecialchars($job['employer_firstName'] . ' ' . $job['employer_lastName']); ?></h3>
                    <p class="mb-4 text-sm">
                        <i class="fas fa-user mr-2"></i> Member since: <?php echo date('F Y', strtotime($job['created_at'])); ?>
                    </p>

                    <!-- Contact Buttons -->
                    <div class="space-y-3">
                        <style>
    .contact-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px; /* Space between icon and text */
        text-align: center;
    }
</style>

<a href="tel:<?php echo htmlspecialchars($job['telephone']); ?>" class="contact-btn py-3 w-full block">
    <i class="fas fa-phone"></i>
    <span><?php echo htmlspecialchars($job['telephone']); ?></span>
</a>
<a href="https://mail.google.com/mail/u/0/?view=cm&fs=1&to=<?php echo urlencode($job['email']); ?>&su=<?php echo urlencode('Application for ' . $job['job_title']); ?>&body=<?php echo urlencode('Dear ' . $job['employer_firstName'] . ",\n\nI am applying for the " . $job['job_title'] . " position. Please find my CV attached.\n\nRegards,\n[Your Name]"); ?>" target="_blank" class="contact-btn py-3 w-full block bg-blue-600 hover:bg-blue-700">
        <i class="fas fa-envelope"></i>
        <span>Send Your CV </span>
    </a>
                    </div>

                    <!-- Application Tips -->
                    <div class="mt-6 border-t border-gray-600 pt-4">
                        <h4 class="font-semibold mb-2">Application Tips</h4>
                        <ul class="text-sm list-disc pl-4 space-y-1">
                            <li>Attach a tailored CV</li>
                            <li>Include a cover letter</li>
                            <li>Follow up after a week</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <a href="index.php">
        <img src="images/mk.png" alt="Markets.lk Logo" class="h-14 object-contain">
    </a>
                    <p class="text-gray-400">Your trusted marketplace for buying and selling locally.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">About Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Contact</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white">Facebook</a>
                        <a href="#" class="text-gray-400 hover:text-white">Twitter</a>
                        <a href="#" class="text-gray-400 hover:text-white">Instagram</a>
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center text-gray-400">
                © 2025 Markets.lk. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        
        // Image Gallery
        let currentImageIndex = 0;
        const images = <?php echo json_encode($images); ?>;
        const mainImage = document.getElementById('mainImage');
        const thumbnails = document.querySelectorAll('.gallery-thumbnail');

        function selectImage(index) {
            if (index < 0 || index >= images.length) return;
            currentImageIndex = index;
            mainImage.src = images[index];
            thumbnails.forEach((thumb, i) => {
                thumb.classList.toggle('active', i === index);
            });
        }

        function changeImage(direction) {
            let newIndex = currentImageIndex + direction;
            if (newIndex < 0) newIndex = images.length - 1;
            if (newIndex >= images.length) newIndex = 0;
            selectImage(newIndex);
        }

        // Sidebar toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const closeSidebar = document.getElementById('closeSidebar');
        const loginBtnMobile = document.getElementById('loginBtnMobile');
        const accountBtnMobile = document.getElementById('accountBtnMobile');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        closeSidebar.addEventListener('click', () => {
            sidebar.classList.remove('open');
        });

        loginBtnMobile.addEventListener('click', () => {
            document.getElementById('loginBtn').click();
            sidebar.classList.remove('open');
        });

        accountBtnMobile.addEventListener('click', () => {
            document.getElementById('accountBtn').click();
            sidebar.classList.remove('open');
        });

        // Popup toggle
        const loginBtn = document.getElementById('loginBtn');
        const accountBtn = document.getElementById('accountBtn');
        const loginPopup = document.getElementById('loginPopup');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const accountPopup = document.getElementById('accountPopup');
        const showRegister = document.getElementById('showRegister');
        const showLogin = document.getElementById('showLogin');
        const closePopup = document.getElementById('closePopup');
        const logoutBtn = document.getElementById('logoutBtn');

        // Check login status on page load
        window.onload = function () {
            const isLoggedIn = <?php echo $currentUserId ? 'true' : 'false'; ?>;
            if (isLoggedIn) {
                loginBtn.classList.add('hidden');
                accountBtn.classList.remove('hidden');
                loginBtnMobile.classList.add('hidden');
                accountBtnMobile.classList.remove('hidden');
                // Initialize Google Sign-In
                google.accounts.id.initialize({
                    client_id: 'YOUR_GOOGLE_CLIENT_ID',
                    callback: (response) => handleGoogleAuth(response, false)
                });
            } else {
                loginBtn.classList.remove('hidden');
                accountBtn.classList.add('hidden');
                loginBtnMobile.classList.remove('hidden');
                accountBtnMobile.classList.add('hidden');
            }
        };

        loginBtn.addEventListener('click', () => {
            loginPopup.classList.remove('hidden');
            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');
            accountPopup.classList.add('hidden');
            window.location.href = 'index.php?redirect=job-details.php?id=<?php echo $jobId; ?>';
        });

        accountBtn.addEventListener('click', () => {
            const isEmployer = <?php echo $isEmployer ? 'true' : 'false'; ?>;
            window.location.href = isEmployer ? 'jobs.php' : 'index.php#account';
        });

        closePopup.addEventListener('click', () => {
            loginPopup.classList.add('hidden');
            loginForm.classList.add('hidden');
            registerForm.classList.add('hidden');
            accountPopup.classList.add('hidden');
        });

        showRegister.addEventListener('click', () => {
            loginForm.classList.add('hidden');
            registerForm.classList.remove('hidden');
            accountPopup.classList.add('hidden');
        });

        showLogin.addEventListener('click', () => {
            registerForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
            accountPopup.classList.add('hidden');
        });

        logoutBtn.addEventListener('click', () => {
            localStorage.removeItem('user');
            loginBtn.classList.remove('hidden');
            accountBtn.classList.add('hidden');
            loginBtnMobile.classList.remove('hidden');
            accountBtnMobile.classList.add('hidden');
            loginPopup.classList.add('hidden');
            window.location.href = window.location.pathname; // Redirect to same page
        });

        // Login handling
        document.getElementById('submitLogin').addEventListener('click', async () => {
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const data = await response.json();
                if (response.ok) {
                    alert('Login successful!');
                    localStorage.setItem('user', JSON.stringify({
                        email,
                        firstName: data.firstName || '',
                        lastName: data.lastName || ''
                    }));
                    loginPopup.classList.add('hidden');
                    loginBtn.classList.add('hidden');
                    accountBtn.classList.remove('hidden');
                    loginBtnMobile.classList.add('hidden');
                    accountBtnMobile.classList.remove('hidden');
                    window.location.reload(); // Reload to reflect session state
                } else {
                    alert(data.message || 'Login failed');
                }
            } catch (error) {
                alert('Error during login');
            }
        });

        // Registration handling
        document.getElementById('submitRegister').addEventListener('click', async () => {
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('registerEmail').value;
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }

            const payload = { firstName, lastName, email, password };
            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (response.ok) {
                    alert('Registration successful! Please login.');
                    registerForm.classList.add('hidden');
                    loginForm.classList.remove('hidden');
                    accountPopup.classList.add('hidden');
                } else {
                    alert(data.message || 'Registration failed');
                }
            } catch (error) {
                alert('Error during registration: ' + error.message);
            }
        });

        // Google Login and Register
        window.handleGoogleAuth = async (response, isRegister = false) => {
            try {
                const res = await fetch('api/google-auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token: response.credential, isRegister })
                });
                const data = await res.json();
                if (res.ok) {
                    alert(`${isRegister ? 'Google registration' : 'Google login'} successful!${isRegister ? ' Please login.' : ''}`);
                    if (!isRegister) {
                        localStorage.setItem('user', JSON.stringify({
                            email: data.email,
                            firstName: data.firstName || '',
                            lastName: data.lastName || ''
                        }));
                        loginPopup.classList.add('hidden');
                        window.location.href = window.location.pathname; // Redirect to same page
                    } else {
                        registerForm.classList.add('hidden');
                        loginForm.classList.remove('hidden');
                        accountPopup.classList.add('hidden');
                    }
                } else {
                    alert(data.message || `Google ${isRegister ? 'registration' : 'login'} failed`);
                }
            } catch (error) {
                alert(`Error during Google ${isRegister ? 'registration' : 'login'}`);
            }
        };

        document.getElementById('googleLogin').addEventListener('click', () => {
            google.accounts.id.prompt();
        });
        document.getElementById('googleRegister').addEventListener('click', () => {
            google.accounts.id.initialize({
                client_id: 'YOUR_GOOGLE_CLIENT_ID',
                callback: (response) => handleGoogleAuth(response, true)
            });
            google.accounts.id.prompt();
        });

        // Facebook Login and Register
        FB.init({
            appId: 'YOUR_FACEBOOK_APP_ID',
            cookie: true,
            xfbml: true,
            version: 'v12.0'
        });

        const handleFacebookAuth = async (response, isRegister = false) => {
            if (response.authResponse) {
                try {
                    const res = await fetch('api/facebook-auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ accessToken: response.authResponse.accessToken, isRegister })
                    });
                    const data = await res.json();
                    if (res.ok) {
                        alert(`${isRegister ? 'Facebook registration' : 'Facebook login'} successful!${isRegister ? ' Please login.' : ''}`);
                        if (!isRegister) {
                            localStorage.setItem('user', JSON.stringify({
                                email: data.email,
                                firstName: data.firstName || '',
                                lastName: data.lastName || ''
                            }));
                            loginPopup.classList.add('hidden');
                            window.location.href = window.location.pathname; // Redirect to same page
                        } else {
                            registerForm.classList.add('hidden');
                            loginForm.classList.remove('hidden');
                            accountPopup.classList.add('hidden');
                        }
                    } else {
                        alert(data.message || `Facebook ${isRegister ? 'registration' : 'login'} failed`);
                    }
                } catch (error) {
                    alert(`Error during Facebook ${isRegister ? 'registration' : 'login'}`);
                }
            } else {
                alert('Facebook authentication cancelled');
            }
        };

        document.getElementById('facebookLogin').addEventListener('click', () => {
            FB.login((response) => handleFacebookAuth(response, false), { scope: 'email' });
        });

        document.getElementById('facebookRegister').addEventListener('click', () => {
            FB.login((response) => handleFacebookAuth(response, true), { scope: 'email' });
        });

        // Handle Post Your Ad button clicks
        document.querySelectorAll('a[href="sell.php"][class*="rounded-full"]').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const user = JSON.parse(localStorage.getItem('user'));
                if (user) {
                    window.location.href = 'sell.php';
                } else {
                    window.location.href = 'index.php?redirect=sell.php';
                }
            });
        });
    </script>
</body>
</html>