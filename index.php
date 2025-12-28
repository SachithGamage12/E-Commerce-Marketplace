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

$adsPerPage = 10; // Number of ads per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $adsPerPage;

// Get filter parameters
$category = isset($_GET['category']) ? (int)$_GET['category'] : '';
$location = isset($_GET['location']) ? $conn->real_escape_string($_GET['location']) : '';

// Build the WHERE clause for filtering
$whereClauses = [];
if ($category !== '') {
    // Check if the category is a parent category
    $checkParentSql = "SELECT parent_id FROM categories WHERE id = ?";
    $checkParentStmt = $conn->prepare($checkParentSql);
    $checkParentStmt->bind_param("i", $category);
    $checkParentStmt->execute();
    $parentResult = $checkParentStmt->get_result();
    $parentRow = $parentResult->fetch_assoc();
    $isParent = $parentRow ? $parentRow['parent_id'] === null : false;

    if ($isParent) {
        // If parent category, include all subcategories
        $whereClauses[] = "a.subcategory_id IN (SELECT id FROM categories WHERE parent_id = $category OR id = $category)";
    } else {
        // If subcategory, filter by specific category
        $whereClauses[] = "a.subcategory_id = $category";
    }
}
if ($location !== '') {
    $whereClauses[] = "a.district = '$location'";
}
$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Count total ads with filters
$countSql = "SELECT COUNT(*) as total FROM ads a
             JOIN categories c ON a.subcategory_id = c.id
             JOIN districts d ON a.district = d.name
             JOIN areas ar ON a.area = ar.name
             JOIN users u ON a.user_id = u.id
             $whereSql";
$countResult = $conn->query($countSql);
$totalAds = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalAds / $adsPerPage);

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Markets.lk - Your Modern Marketplace</title>
    <link rel="icon" type="image/png" href="images/image.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.0/nouislider.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            top: -0.5rem; /* Reduced from -top-2 */
            left: -0.5rem; /* Reduced from -left-2 */
            padding: 0.25rem 0.5rem; /* Reduced from px-2 py-1 */
            font-size: 0.75rem; /* Smaller text */
        }

        .login-popup {
            background: linear-gradient(145deg, rgb(241, 241, 250), rgb(204, 204, 247));
            border: 2px solid #ff6200;
            border-radius: 15px 5px 15px 5px;
            box-shadow: 0 0 20px #ff6200, inset 0 0 10px #00f6ff;
            
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
        /* Pagination adjustments */
        .pagination-container {
            max-width: 100%; /* Ensure it stays within the container */
            overflow-x: auto; /* Allow horizontal scrolling if needed */
            white-space: nowrap; /* Prevent buttons from wrapping unnecessarily */
            padding: 0.5rem; /* Add some padding */
            scrollbar-width: thin; /* Thin scrollbar for Firefox */
            scrollbar-color: #ff6200 #2a2a5e; /* Custom scrollbar colors */
        }

        .pagination-container::-webkit-scrollbar {
            height: 6px; /* Thin scrollbar for Webkit browsers */
        }

        .pagination-container::-webkit-scrollbar-track {
            background: #2a2a5e;
            border-radius: 5px;
        }

        .pagination-container::-webkit-scrollbar-thumb {
            background: #ff6200;
            border-radius: 5px;
        }

        .pagination-container::-webkit-scrollbar-thumb:hover {
            background: #e55b00;
        }

        .pagination-container a, .pagination-container button {
            padding: 0.5rem 0.75rem; /* Reduced padding */
            font-size: 0.875rem; /* Smaller font size (text-sm) */
            border-radius: 0.375rem; /* Consistent rounded corners */
            min-width: 2rem; /* Ensure buttons don't get too small */
            text-align: center;
        }

        .pagination-container .bg-blue-500 {
            background-color: #3b82f6; /* Consistent with your theme */
        }

        .pagination-container .bg-gray-100:hover {
            background-color: #e5e7eb; /* Slightly darker hover */
        }

        .pagination-container .bg-gray-200:hover {
            background-color: #d1d5db; /* Slightly darker hover for Previous/Next */
        }

        /* Ensure flex wrapping for smaller screens */
        @media (max-width: 640px) {
            .pagination-container {
                flex-wrap: wrap; /* Allow wrapping on small screens */
                white-space: normal; /* Allow wrapping */
                overflow-x: visible; /* Remove horizontal scroll on small screens */
            }
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
        .accountPedido {
            color: #00f6ff;
            font-size: 1.1rem;
        }
        .noUi-target {
            background: #2a2a5e;
            border: none;
            box-shadow: none;
            height: 8px;
            border-radius: 5px;
        }
        .noUi-connect {
            background: #ff6200;
        }
        .noUi-handle {
            background: #ff6200;
            border-radius: 50%;
            width: 20px !important;
            height: 20px !important;
            box-shadow: 0 0 10px #ff6200;
            cursor: pointer;
            top: -6px !important;
        }
        .noUi-handle:hover {
            transform: scale(1.2);
        }
        .noUi-tooltip {
            display: none;
        }
        #pricePopup {
            transform: translateY(100%);
            top: 100%;
        }
        .filter-section {
            background: linear-gradient(145deg, #1a1a3d, #2a2a5e);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 0 20px rgba(255, 98, 0, 0.3);
        }
        .ad-card {
            background: linear-gradient(145deg, rgba(255, 98, 0, 0.1), rgba(255, 98, 0, 0.2));
            border: 1px solid #ff6200;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            max-width: 800px;
            min-height: 160px;
            display: flex;
        }
        .ad-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .ad-image-container {
            width: 140px;
            height: 140px;
            flex-shrink: 0;
            margin: 10px;
        }
        .ad-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 5px;
        }
        .ad-details {
            padding: 10px;
            font-size: 0.9rem;
            line-height: 1.3;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .ad-details h3 {
            font-size: 1rem;
            margin-bottom: 4px;
        }
        .ad-details .price {
            font-size: 1.1rem;
            margin-bottom: 4px;
        }
        .ad-details p {
            margin-bottom: 4px;
        }
        
        /* AD DETAILS FOOTER - RESPONSIVE STYLES */
        .ad-details-footer {
            display: flex;
            align-items: center;
            margin-top: auto;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
        }

        /* View count style - white rectangle with eye icon */
        .view-count-container {
            display: flex;
            align-items: center;
            
            
            
            padding: 4px 8px;
            gap: 4px;
           
        }

        .view-count-container svg {
            width: 14px;
            height: 14px;
            color: #6b7280;
        }

        .view-count-text {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
        }

        .view-count-number {
            font-size: 0.8rem;
            color: #374151;
            font-weight: 600;
            margin-left: 2px;
        }

        /* Time ago style */
        .time-ago {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
            padding: 4px 8px;
            
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }

        /* Responsive positioning */
        /* Desktop (≥768px): Time on LEFT, Views on RIGHT */
        @media (min-width: 768px) {
            .ad-details-footer {
                flex-direction: row;
                justify-content: space-between;
            }
            .time-ago {
                order: 1; /* Position 1st (left) */
            }
            .view-count-container {
                order: 2; /* Position 2nd (right) */
            }
        }

        /* Mobile (<768px): Time on TOP, Views on BOTTOM (stacked) */
        @media (max-width: 767px) {
            .ad-details-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            .time-ago {
                order: 1; /* Position 1st (top) */
                width: 100%;
                text-align: left;
            }
            .view-count-container {
                order: 2; /* Position 2nd (bottom) */
                width: 100%;
                justify-content: flex-start;
            }
        }

        /* Sidebar styles */
        .sidebar {
            transition: transform 0.3s ease-in-out;
            transform: translateX(-100%);
            width: 16rem; /* Reduced from w-64 */
        }
        .sidebar.open {
            transform: translateX(0);
        }
        .sidebar .p-4 {
            padding: 0.75rem; /* Reduced from p-4 */
        }
        .sidebar .space-y-4 {
            gap: 0.75rem; /* Reduced from space-y-4 */
        }
        .blinking-cursor {
            font-weight: bold;
            font-size: inherit;
            color: #000;
            animation: blink 0.7s steps(1) infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        /* Browse Categories Section */
        .categories-wrapper {
            position: relative;
            padding: 0 2.5rem; /* Space for arrows */
        }
        .categories-container {
            display: flex;
            overflow-x: auto;
            gap: 1rem;
            padding: 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: #ff6200 #2a2a5e;
        }
        .categories-container::-webkit-scrollbar {
            height: 8px;
        }
        .categories-container::-webkit-scrollbar-track {
            background: #2a2a5e;
            border-radius: 5px;
        }
        .categories-container::-webkit-scrollbar-thumb {
            background: #ff6200;
            border-radius: 5px;
        }
        .categories-container::-webkit-scrollbar-thumb:hover {
            background: #e55b00;
        }
        .categories-container button {
            flex: 0 0 auto;
            min-width: 120px;
            text-align: center;
        }
        .category-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: #ff6200;
            color: white;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
            z-index: 10;
        }
        .category-arrow:hover {
            background: #e55b00;
        }
        .category-arrow-left {
            left: 0;
        }
        .category-arrow-right {
            right: 0;
        }
        @media (min-width: 640px) {
            .categories-container {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                overflow-x: visible;
            }
            .category-arrow {
                display: none; /* Hide arrows on larger screens */
            }
            .categories-wrapper {
                padding: 0; /* Remove padding on larger screens */
            }
        }
        @media (min-width: 768px) {
            .categories-container {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
            .sidebar {
                transform: translateX(0);
            }
        }
        @media (min-width: 1024px) {
            .categories-container {
                grid-template-columns: repeat(9, minmax(0, 1fr));
            }
        }
        .categories-container {
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }
        /* Center the listings container in desktop view */
        @media (min-width: 768px) {
            .listings-container {
                max-width: 800px; /* Adjust this value to control the width of the listings */
                margin-left: auto;
                margin-right: auto;
            }
        }
        /* Style for optgroups in select dropdown */
        /* Force orange color for optgroups in all dropdowns */
select.cyber-input optgroup,
#category optgroup,
#location optgroup,
.cyber-input optgroup {
    color: #ff6200 !important;
    background-color: #0f0f2a !important;
    font-weight: bold !important;
    font-size: 1.2rem !important;
    padding: 8px 5px !important;
    border-bottom: 2px solid rgba(255, 98, 0, 0.5) !important;
    margin-top: 10px !important;
}

/* Style for options within optgroups */
select.cyber-input optgroup option,
#category optgroup option,
#location optgroup option,
.cyber-input optgroup option {
    color: #00f6ff !important;
    background-color: #0f0f2a !important;
    font-weight: normal !important;
    font-size: 1rem !important;
    padding: 8px 25px !important;
}

/* Style for regular options */
select.cyber-input option:not(optgroup option),
#category option:not(optgroup option),
#location option:not(optgroup option),
.cyber-input option:not(optgroup option) {
    color: #00f6ff !important;
    background-color: #0f0f2a !important;
}

/* Hover states */
select.cyber-input option:hover,
#category option:hover,
#location option:hover,
.cyber-input option:hover {
    background-color: rgba(255, 98, 0, 0.3) !important;
    color: #ffffff !important;
}
        /* Navbar styles */
        nav {
            padding: 0.5rem 1rem; /* Reduced from py-4 px-6 */
        }
        .nav-btn {
            padding: 0.375rem 0.75rem; /* Reduced from px-4 py-2 */
            font-size: 0.875rem; /* Smaller text (text-sm) */
        }
        .nav-btn svg {
            width: 1rem; /* Reduced from w-5 to w-4 */
            height: 1rem; /* Reduced from h-5 to h-4 */
            margin-right: 0.25rem; /* Reduced from mr-2 to mr-1 */
        }
        #addb {
            padding: 0.375rem 1rem; /* Reduced from px-6 py-2 */
        }
    </style>
</head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-MLZ3TSKYRK"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-MLZ3TSKYRK');
</script>
<body class="font-chakra bg-gray-100">
    <!-- Hero Section -->
    <header class="bg-gradient-to-r from-orange-400 to-orange-600 text-white">
        <nav class="container mx-auto px-4 py-2 flex justify-between items-center">
            <!-- Logo and Brand -->
            <div class="flex items-center space-x-2">
                <img src="images/image.png" alt="MarketHub Logo" class="h-10 w-14 object-contain">
                <a href="index.php">
                    <img src="images/mk.png" alt="Markets.lk Logo" class="h-10 object-contain">
                </a>
            </div>
            <!-- Hamburger Menu for Mobile -->
            <button id="menuToggle" class="md:hidden text-white focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-2">
                <button id="loginBtn" class="nav-btn flex items-center px-3 py-1.5 text-black bg-gradient-to-r from-sky-100 to-blue-200 rounded-full hover:from-sky-200 hover:to-blue-300 transition-all duration-300 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Login
                </button>
                <button id="accountBtn" class="nav-btn flex items-center px-3 py-1.5 text-black bg-gradient-to-r from-green-100 to-green-200 rounded-full hover:from-green-200 hover:to-green-300 transition-all duration-300 text-sm hidden">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Account
                    <span id="chatIcon" class="ml-1 hidden">
                        <svg class="w-4 h-4 text-red-500 animate-blink" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </span>
                </button>
                <a href="jobs.php" class="nav-btn flex items-center px-3 py-1.5 text-black bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Apply Jobs
                </a>
                <a href="sell.php" id="addb" class="nav-btn relative inline-flex items-center px-4 py-1.5 text-black font-semibold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] neon-yellow-border transition-all duration-300 text-sm">
                    <span class="absolute -top-1 -left-1 bg-green-400 text-xs font-bold text-white px-1.5 py-0.5 rounded-full animated-free">Free</span>
                    Post Your Ad
                </a>
            </div>
        </nav>
        <!-- Mobile Sidebar -->
        <div id="sidebar" class="sidebar fixed inset-y-0 left-0 w-56 bg-gradient-to-b from-orange-600 to-orange-400 text-white transform -translate-x-full md:hidden z-50">
            <div class="flex justify-between items-center p-3 border-b border-orange-300">
                <div class="flex items-center space-x-2">
                    <img src="images/image.png" alt="MarketHub Logo" class="h-10 w-14 object-contain">
                    <a href="index.php">
                        <img src="images/mk.png" alt="Markets.lk Logo" class="h-10 object-contain">
                    </a>
                </div>
                <button id="closeSidebar" class="text-white focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="flex flex-col p-3 space-y-3">
                <button id="loginBtnMobile" class="nav-btn flex items-center px-3 py-1.5 text-black bg-gradient-to-r from-sky-100 to-blue-200 rounded-full hover:from-sky-200 hover:to-blue-300 transition-all duration-300 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Login
                </button>
                <button id="accountBtnMobile" class="nav-btn flex items-center px-3 py-1.5 text-black bg-gradient-to-r from-green-100 to-green-200 rounded-full hover:from-green-200 hover:to-green-300 transition-all duration-300 text-sm hidden">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Account
                    <span id="chatIconMobile" class="ml-1 hidden">
                        <svg class="w-4 h-4 text-red-500 animate-blink" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </span>
                </button>
                <a href="jobs.php" class="nav-btn flex items-center px-3 py-1.5 text-black bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Apply Jobs
                </a>
                <a href="sell.php" class="nav-btn relative inline-flex items-center px-4 py-1.5 text-black font-semibold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] neon-yellow-border transition-all duration-300 text-sm">
                    <span class="absolute -top-1 -left-1 bg-green-400 text-xs font-bold text-white px-1.5 py-0.5 rounded-full animated-free">Free</span>
                    Post Your Ad
                </a>
            </div>
        </div>
        <div class="container mx-auto px-6 py-12 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 text-white-800 flex flex-wrap justify-center leading-tight text-center" id="wave-text"></h1>
            <p class="text-xl font-medium text-gray-600 leading-relaxed mb-8">
                Buy, sell, and discover amazing deals in your local Market
            </p>
            <div class="max-w-xl mx-auto bg-white rounded-full shadow-lg flex items-center p-2">
                <input type="text" placeholder="Search for items, cars, jobs, and more..." class="w-full px-4 py-2 text-gray-700 focus:outline-none rounded-l-full">
                <button id="searchButton" class="bg-blue-600 text-white px-6 py-2 rounded-r-full hover:bg-blue-700">Search</button>
            </div>
        </div>
    </header>
    <!-- Browse Categories Section -->
    <div class="container mx-auto px-6 py-6 bg-gray-100">
        <h2 class="text-2xl font-bold mb-4 text-center">Browse Categories</h2>
        <div class="categories-wrapper">
            <button class="category-arrow category-arrow-left">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <button class="category-arrow category-arrow-right">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            <div class="categories-container">
                <?php
                // Query to fetch only parent categories (where parent_id is NULL) and count ads for each
                $sql = "SELECT c.id, c.name, COUNT(a.id) as ad_count
                        FROM categories c
                        LEFT JOIN categories sub ON sub.parent_id = c.id
                        LEFT JOIN ads a ON a.subcategory_id = sub.id OR a.subcategory_id = c.id
                        WHERE c.parent_id IS NULL
                        GROUP BY c.id, c.name
                        ORDER BY 
                            CASE 
                                WHEN c.name = 'Vehicles' THEN 1
                                WHEN c.name = 'Home & Property' THEN 2
                                WHEN c.name = 'Electronics' THEN 3
                                WHEN c.name = 'Pets & Animals' THEN 4
                                WHEN c.name = 'Fashion & Style' THEN 5
                                WHEN c.name = 'Sports & Kids' THEN 6
                                WHEN c.name = 'Food & Agriculture' THEN 7
                                WHEN c.name = 'Buisness & Industry' THEN 8
                                ELSE 9
                            END ASC, 
                            c.name = 'Other' ASC, c.name ASC";
                $result = $conn->query($sql);

                // Mapping of category keywords to emoji icons and colors
                $iconMappings = [
                    'vehicle' => ['🚗', 'text-teal-500'],
                    'car' => ['🚗', 'text-teal-500'],
                    'home' => ['🏠', 'text-yellow-500'],
                    'property' => ['🏠', 'text-yellow-500'],
                    'electronic' => ['📱', 'text-blue-500'],
                    'pets' => ['🐾', 'text-amber-800'],
                    'animal' => ['🐾', 'text-amber-800'],
                    'garden' => ['🌿', 'text-green-500'],
                    'fashion' => ['👕', 'text-indigo-500'],
                    'beauty' => ['💄', 'text-pink-500'],
                    'service' => ['🛠️', 'text-orange-500'],
                    'education' => ['📚', 'text-gray-500'],
                    'business' => ['💼', 'text-purple-500'],
                    'industry' => ['🏭', 'text-purple-500'],
                    'hobby' => ['🎨', 'text-violet-500'],
                    'sport' => ['⚽', 'text-violet-500'],
                    'kids' => ['🧸', 'text-violet-500'],
                    'food' => ['🍎', 'text-green-500'],
                    'agriculture' => ['🚜', 'text-green-500'],
                    'other' => ['⋯', 'text-cyan-500'],
                ];

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $categoryName = htmlspecialchars($row['name']);
                        $categoryId = $row['id'];
                        $adCount = number_format($row['ad_count']);

                        // Customize display name for Electronics
                        if ($categoryName === 'Electronics') {
                            $displayName = 'Mobile & Computer';
                        } else {
                            $displayName = $categoryName;
                        }

                        // Find matching icon (case-insensitive)
                        $icon = '📁'; // Default icon
                        $color = 'text-gray-500'; // Default color
                        
                        $lowerName = strtolower($categoryName);
                        foreach ($iconMappings as $keyword => $mapping) {
                            if (strpos($lowerName, $keyword) !== false || $lowerName === $keyword) {
                                $icon = $mapping[0];
                                $color = $mapping[1];
                                break;
                            }
                        }
                        ?>
                        <button class="flex flex-col items-center p-2 bg-white rounded-lg shadow hover:bg-gray-200 transition" data-category-id="<?php echo $categoryId; ?>">
                            <span class="<?php echo $color; ?> text-2xl mb-2"><?php echo $icon; ?></span>
                            <span class="text-center"><?php echo $displayName; ?><br><small class="text-gray-500"><?php echo $adCount; ?> ads</small></span>
                        </button>
                        <?php
                    }
                } else {
                    echo '<p class="text-center text-gray-500">No categories found</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <!-- Login Popup -->
    <div id="loginPopup" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50">
        <div class="login-popup w-full max-w-md p-8 relative">
            <button id="closePopup" class="close-btn">×</button>
            <div id="loginForm" class="space-y-6">
                <h2 class="text-4xl font-bold text-center bg-clip-text text-transparent bg-gradient-to-r from-[#ff6200] to-[#00f6ff]">Welcome Back!</h2>
                <div class="space-y-4">
                    <input id="loginEmail" type="email" placeholder="Email" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                    <input id="loginPassword" type="password" placeholder="Password" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                </div>
                <button id="submitLogin" class="w-full cyber-btn py-3 font-semibold">Log In</button>
                <div class="divider"></div>
                <div class="text-center">
                    <button id="showRegister" class="text-sm text-[#2b2928] hover:text-[#ff6200]">New User? Register</button>
                </div>
            </div>
            <div id="registerForm" class="space-y-6 hidden">
                <h2 class="text-4xl font-bold text-center bg-clip-text text-transparent bg-gradient-to-r from-[#ff6200] to-[#00f6ff]">Join With Markets.lk</h2>
                <div class="space-y-4">
                    <input id="firstName" type="text" placeholder="First Name" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                    <input id="lastName" type="text" placeholder="Last Name" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                    <input id="registerEmail" type="email" placeholder="Email" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                    
                    <input id="registerPassword" type="password" placeholder="Password" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                     <p style="color: #FF2B00; font-size: 14px;">
            ✱ Password must include uppercase, lowercase, number, and special character. (Ex: Sed2341@#)
        </p>
                    <input id="confirmPassword" type="password" placeholder="Confirm Password" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                </div>
                <button id="submitRegister" class="w-full cyber-btn py-3 font-semibold">Create Profile</button>
                <div class="divider"></div>
                <div class="text-center">
                    <button id="showLogin" class="text-sm text-[#2b2928] hover:text-[#ff6200]">Back to Login</button>
                </div>
            </div>
            <div id="accountPopup" class="space-y-6 hidden max-h-[70vh] max-w-md mx-auto overflow-y-auto p-4 sm:p-6 bg-white rounded-lg shadow-lg">
                <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-center bg-clip-text text-transparent bg-gradient-to-r from-[#ff6200] to-[#00f6ff]">
                    Account Details
                </h2>
                <div class="spaces-y-4 account-info text-sm sm:text-base">
                    <p><strong>First Name:</strong> <span id="accountFirstName"></span></p>
                    <p><strong>Last Name:</strong> <span id="accountLastName"></span></p>
                    <p><strong>Email:</strong> <span id="accountEmail"></span></p>
                </div>
                <!-- Recent Ads Section -->
                <div class="space-y-4">
                    <h3 class="text-lg sm:text-xl font-semibold text-[#ed0e0e]">Your Recent Ads</h3>
                    <div id="recentAds" class="space-y-4">
                        <!-- Ads will be dynamically inserted here -->
                    </div>
                    <p id="noAdsMessage" class="text-gray-400 text-sm sm:text-base hidden">You haven't posted any ads yet.</p>
                </div>
                <button id="logoutBtn" class="w-full cyber-btn py-2 sm:py-3 font-semibold text-sm sm:text-base">Logout</button>
            </div>
        </div>
    </div>
    <!-- Main Content Section -->
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold">All Listings</h2>
                <button id="filterBtn" class="cyber-btn px-4 py-2 font-semibold">Filter Listings</button>
            </div>
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Filter Popup -->
                <div id="filterPopup" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50">
                    <div class="login-popup w-full max-w-md p-8 relative">
                        <button id="closeFilterPopup" class="close-btn">×</button>
                        <div class="filter-section">
                            <h2 class="text-xl font-bold text-white mb-6">Filter Listings</h2>
                            <!-- Category Filter -->
                            <div class="mb-6">
                                <label for="category" class="block text-sm font-medium text-gray-200 mb-2">Category</label>
                                <select id="category" class="w-full px-4 py-3 cyber-input text-white">
                                    <option value="">All Categories</option>
                                    <?php
                                    // Query to get all parent categories
                                    $parentSql = "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY 
                                                CASE 
                                                    WHEN name = 'Vehicles' THEN 1
                                                    WHEN name = 'Home & Property' THEN 2
                                                    WHEN name = 'Electronics' THEN 3
                                                    WHEN name = 'Pets & Animals' THEN 4
                                                    WHEN name = 'Fashion & Style' THEN 5
                                                    WHEN name = 'Sports & Kids' THEN 6
                                                    WHEN name = 'Food & Agriculture' THEN 7
                                                    WHEN name = 'Business & Industry' THEN 8
                                                    ELSE 9
                                                END ASC";
                                    $parentResult = $conn->query($parentSql);
                                    
                                    if ($parentResult->num_rows > 0) {
                                        while ($parentRow = $parentResult->fetch_assoc()) {
                                            // Add parent category as disabled option (placeholder)
                                            echo '<optgroup label="' . htmlspecialchars($parentRow['name']) . '">';
                                            
                                            // Get all subcategories for this parent
                                            $subSql = "SELECT * FROM categories WHERE parent_id = " . $parentRow['id'] . " ORDER BY name ASC";
                                            $subResult = $conn->query($subSql);
                                            
                                            if ($subResult->num_rows > 0) {
                                                while ($subRow = $subResult->fetch_assoc()) {
                                                    $selected = (isset($_GET['category']) && $_GET['category'] == $subRow['id']) ? 'selected' : '';
                                                    echo '<option value="' . $subRow['id'] . '" ' . $selected . '>' . htmlspecialchars($subRow['name']) . '</option>';
                                                }
                                            }
                                            
                                            echo '</optgroup>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <!-- Location Filter -->
                            <div class="mb-6">
                                <label for="location" class="block text-sm font-medium text-gray-200 mb-2">Location</label>
                                <select id="location" class="w-full px-4 py-3 cyber-input text-white">
                                    <option value="">All Locations</option>
                                    <?php
                                    $sql = "SELECT DISTINCT name FROM districts ORDER BY name ASC";
                                    $result = $conn->query($sql);
                                    if ($result === FALSE) {
                                        die("Query failed: " . $conn->error);
                                    }
                                    if ($result->num_rows > 0) {
                                        while($row = $result->fetch_assoc()) {
                                            $selected = (isset($_GET['location']) && $_GET['location'] == $row['name']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($row['name']) . '" ' . $selected . '>' . htmlspecialchars($row['name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <!-- Filter Button -->
                            <button id="applyFilters" class="w-full cyber-btn py-3 font-semibold">Apply Filters</button>
                        </div>
                    </div>
                </div>
                <!-- Right Content (Advertisements) -->
                <div class="listings-container md:w-full">
                    <div class="grid grid-cols-1 gap-6">
                        <?php
                        $sql = "SELECT a.*, c.name as category_name, d.name as district_name, ar.name as area_name, 
                                (SELECT image_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as image_path,
                                a.telephone as owner_phone,
                                COUNT(av.id) as view_count
                                FROM ads a
                                JOIN categories c ON a.subcategory_id = c.id
                                JOIN districts d ON a.district = d.name
                                JOIN areas ar ON a.area = ar.name
                                JOIN users u ON a.user_id = u.id
                                LEFT JOIN ad_views av ON a.id = av.ad_id
                                $whereSql
                                GROUP BY a.id
                                ORDER BY a.created_at DESC
                                LIMIT $adsPerPage OFFSET $offset";

                        $result = $conn->query($sql);

                        if ($result === FALSE) {
                            die("Query failed: " . $conn->error);
                        }

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $imagePath = !empty($row['image_path']) ? $row['image_path'] : 'images/placeholder.jpg';
                                $description = htmlspecialchars($row['description']);
                                $shortDesc = strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description;
                                $viewCount = $row['view_count'] ? $row['view_count'] : 0;
                                ?>
                                <a href="ad-details.php?id=<?php echo $row['id']; ?>" class="block">
                                    <div class="ad-card flex">
                                        <!-- Image Section -->
                                        <div class="ad-image-container">
                                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="ad-image">
                                        </div>
                                        <!-- Details Section -->
                                        <div class="ad-details">
                                            <div>
                                                <h3 class="font-semibold text-gray-800 uppercase"><?php echo htmlspecialchars($row['title']); ?></h3>
                                                <p class="text-gray-600"><?php echo htmlspecialchars($row['category_name']); ?></p>
                                                <p class="text-gray-600"><?php echo htmlspecialchars($row['district_name'] . ', ' . $row['area_name']); ?></p>
                                                <p class="font-bold text-orange-600 price">Rs <?php echo number_format($row['sale_price'], 0); ?></p>
                                            </div>
                                            <!-- Footer with view count and time ago - RESPONSIVE -->
                                            <div class="ad-details-footer">
                                                <!-- Time ago container - Desktop: LEFT, Mobile: TOP -->
                                                <div class="time-ago">
                                                    <?php echo timeAgo($row['created_at']); ?>
                                                </div>
                                                
                                                <!-- View count container - Desktop: RIGHT, Mobile: BOTTOM -->
                                                <div class="view-count-container">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span class="view-count-text">Views</span>
                                                    <span class="view-count-number"><?php echo number_format($viewCount); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <?php
                            }
                        } else {
                            echo '<p class="text-center text-gray-500">No listings found</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-center pagination-container space-x-1">
                <?php
                // Build query string for pagination links
                $queryParams = [];
                if ($category !== '') {
                    $queryParams[] = "category=$category";
                }
                if ($location !== '') {
                    $queryParams[] = "location=" . urlencode($location);
                }
                $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';

                // Calculate which pages to show (show 5 pages at a time)
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $startPage + 4);
                
                // Adjust start page if we're near the end
                if ($endPage - $startPage < 4) {
                    $startPage = max(1, $endPage - 4);
                }

                // Previous button
                if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $queryString; ?>" class="bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded text-sm font-medium">Previous</a>
                <?php endif; ?>

                <?php 
                // First page (if not in the first set)
                if ($startPage > 1): ?>
                    <a href="?page=1<?php echo $queryString; ?>" class="bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded text-sm font-medium">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="px-3 py-2 text-gray-500">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $queryString; ?>" 
                       class="<?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-100 hover:bg-gray-200'; ?> px-3 py-2 rounded text-sm font-medium">
                       <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php 
                // Last page (if not in the last set)
                if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="px-3 py-2 text-gray-500">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $totalPages; ?><?php echo $queryString; ?>" class="bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded text-sm font-medium"><?php echo $totalPages; ?></a>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $queryString; ?>" class="bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded text-sm font-medium">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <!-- Call to Action Section -->
    <section class="bg-blue-600 text-white py-12">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold mb-4">Ready to Sell?</h2>
            <p class="text-lg mb-6">Post your ad for free and reach thousands of buyers today!</p>
            <a href="sell.php" style="border: 2px solid #FFFF33; box-shadow: 0 0 8px #FFFF33;" class="bg-white text-blue-600 px-6 py-3 rounded-full font-semibold hover:bg-gray-100">Post Your Ad</a>
        </div>
    </section>
    <!-- Footer -->
    <footer class="bg-black text-white py-12 relative overflow-hidden">
        <!-- Wave Animation -->
        <div class="absolute bottom-0 left-0 w-full h-24">
            <svg class="w-full h-full" viewBox="0 0 1440 100" preserveAspectRatio="none">
                <path
                    class="wave-path"
                    fill="#ff6200"
                    fill-opacity="0.3"
                    d="M0,60 C360,120 1080,0 1440,60 L1440,100 L0,100 Z"
                ></path>
            </svg>
        </div>
        <div class="container mx-auto px-6 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Left Section: Logo and Description -->
                <div class="flex flex-col items-center md:items-start">
                    <img src="images/image.png" alt="MarketHub Logo" class="h-20 w-28 mb-4 object-contain">
                    <a href="index.php">
                        <img src="images/mk.png" alt="Markets.lk Logo" class="h-14 object-contain">
                    </a>
                    <p class="text-gray-400 text-center md:text-left">
                        Your trusted marketplace for buying and selling locally.
                    </p>
                </div>
                <!-- Center Section: Quick Links -->
                <div class="text-center md:text-left">
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-400 hover:text-orange-500 transition-colors">Home</a></li>
                        <li><a href="about-us.php" class="text-gray-400 hover:text-orange-500 transition-colors">About Us</a></li>
                        <li><a href="about-us.php" class="text-gray-400 hover:text-orange-500 transition-colors">Terms of Service</a></li>
                        <li><a href="privacy.php" class="text-gray-400 hover:text-orange-500 transition-colors">Privacy & Policy</a></li>
                    </ul>
                </div>
                <!-- Right Section: Get in Touch -->
                <div class="text-center md:text-left p-6">
                    <h3 class="text-lg font-semibold mb-4 text-white">Get in Touch</h3>
                    <div class="flex justify-center md:justify-start mb-4">
                        <a href="report.php" class="bg-orange-500 text-white font-semibold py-2 px-4 rounded-full hover:bg-orange-600 transition duration-300 focus:outline-none shadow-lg hover:shadow-orange-500/50">
                            Report Problem & Feedback
                        </a>
                    </div>
                    <div class="flex justify-center md:justify-start mb-4">
        <a href="tel:0711513555" 
           class="bg-orange-500 text-white font-semibold py-2 px-4 rounded-full 
                  hover:bg-orange-600 transition duration-300 focus:outline-none 
                  shadow-lg hover:shadow-orange-500/50 flex items-center space-x-2">
            <i class="fas fa-phone"></i>
            <span>Contact Us - 0711513555</span>
        </a>
    </div>
                    <div class="flex flex-col md:flex-row justify-center md:justify-start items-center md:items-start space-y-2 md:space-y-0 md:space-x-4">
                        <a href="https://www.facebook.com/profile.php?id=61577917814994" target="_blank" class="bg-orange-500 text-white py-2 px-4 rounded-full hover:bg-orange-600 transition duration-300 shadow-lg hover:shadow-orange-500/50 flex items-center space-x-2">
                            <i class="fab fa-facebook"></i>
                            <span>Facebook</span>
                        </a>
                         <a href="https://www.tiktok.com/@markets.lk?_t=ZS-8yQJwsOskDP&_r=1" target="_blank" class="bg-orange-500 text-white py-2 px-4 rounded-full hover:bg-orange-600 transition duration-300 shadow-lg hover:shadow-orange-500/50 flex items-center space-x-2">
                            <i class="fab fa-tiktok"></i>
                            <span>TikTok</span>
                        </a>
                        
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center text-gray-400">
                © 2025 Markets.lk All rights reserved.
            </div>
        </div>
        <style>
    .wave-letter {
      display: inline-block;
      animation: wave 1.5s infinite;
    }

    @keyframes wave {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-10px);
      }
    }
            footer {
                border: 2px solid #ff6200;
                box-shadow: 0 0 10px #ff6200;
            }
            @media (max-width: 768px) {
                footer .grid {
                    text-align: center;
                }
                footer .flex {
                    justify-content: center !important;
                }
            }
            .glow-button {
                box-shadow: 0 0 15px 5px rgba(249, 115, 22, 0.7);
                transition: all 0.3s ease;
            }
            .glow-button:hover {
                box-shadow: 0 0 25px 10px rgba(249, 115, 22, 0.9);
            }
        </style>
    </footer>
<script>
// Sidebar toggle
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const closeSidebar = document.getElementById('closeSidebar');
const loginBtnMobile = document.getElementById('loginBtnMobile');
const accountBtnMobile = document.getElementById('accountBtnMobile');

// Popup elements
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

// Category scroll arrows
const categoryContainer = document.querySelector('.categories-container');
const leftArrow = document.querySelector('.category-arrow-left');
const rightArrow = document.querySelector('.category-arrow-right');

// Scroll left
leftArrow.addEventListener('click', () => {
    categoryContainer.scrollBy({ left: -200, behavior: 'smooth' });
});

// Scroll right
rightArrow.addEventListener('click', () => {
    categoryContainer.scrollBy({ left: 200, behavior: 'smooth' });
});

// Sidebar toggle
menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
});

closeSidebar.addEventListener('click', () => {
    sidebar.classList.remove('open');
});

// Sync mobile buttons with desktop
loginBtnMobile.addEventListener('click', () => {
    loginBtn.click();
    sidebar.classList.remove('open');
});

accountBtnMobile.addEventListener('click', () => {
    accountBtn.click();
    sidebar.classList.remove('open');
});

// Popup toggle
loginBtn.addEventListener('click', () => {
    loginPopup.classList.remove('hidden');
    loginForm.classList.remove('hidden');
    registerForm.classList.add('hidden');
    accountPopup.classList.add('hidden');
});

accountBtn.addEventListener('click', async () => {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user) {
        // Update account details
        document.getElementById('accountFirstName').textContent = user.firstName || 'N/A';
        document.getElementById('accountLastName').textContent = user.lastName || 'N/A';
        document.getElementById('accountEmail').textContent = user.email;

        // Fetch user's recent ads
        try {
            const response = await fetch(`api/user-ads.php?email=${encodeURIComponent(user.email)}`, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();
            const recentAdsContainer = document.getElementById('recentAds');
            const noAdsMessage = document.getElementById('noAdsMessage');
            recentAdsContainer.innerHTML = '';

            if (response.ok && data.success && data.ads.length > 0) {
                noAdsMessage.classList.add('hidden');
                data.ads.forEach(ad => {
                    const adElement = `
                        <a href="ad-details.php?id=${ad.id}" class="block">
                            <div class="ad-card flex" data-ad-id="${ad.id}">
                                <div class="ad-image-container">
                                    <img src="${ad.image_path || 'images/placeholder.jpg'}" alt="${ad.title}" class="ad-image">
                                </div>
                                <div class="ad-details">
                                    <div>
                                        <h3 class="font-semibold text-gray-800 uppercase">${ad.title}</h3>
                                        <p class="text-gray-600">${ad.category_name}</p>
                                        <p class="text-gray-600">${ad.district_name}, ${ad.area_name}</p>
                                        <p class="font-bold text-orange-600 price">Rs ${parseFloat(ad.sale_price).toLocaleString()}</p>
                                    </div>
                                    <div class="ad-details-footer">
                                        <div class="view-count-container">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span class="view-count-text">Views</span>
                                            <span class="view-count-number">${ad.view_count || 0}</span>
                                        </div>
                                        <div class="space-x-2">
                                            <button class="edit-ad-btn cyber-btn px-3 py-1 text-sm font-semibold" data-ad-id="${ad.id}" data-ad-data='${JSON.stringify(ad)}'>Edit</button>
                                            <button class="delete-ad-btn cyber-btn px-3 py-1 text-sm font-semibold" data-ad-id="${ad.id}">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>`;
                    recentAdsContainer.innerHTML += adElement;
                });

                // Add event listeners for edit buttons
                document.querySelectorAll('.edit-ad-btn').forEach(button => {
                    button.addEventListener('click', async (e) => {
                        e.preventDefault();
                        const adId = e.target.getAttribute('data-ad-id');
                        const adData = JSON.parse(e.target.getAttribute('data-ad-data'));

                        const editForm = `
                            <div id="editAdForm" class="space-y-6">
                                <h2 class="text-2xl font-bold text-center bg-clip-text text-transparent bg-gradient-to-r from-[#ff6200] to-[#00f6ff]">Edit Ad</h2>
                                <div class="space-y-4">
                                    <input id="editTitle" type="text" placeholder="Title" value="${adData.title}" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                                    <input id="editSalePrice" type="number" step="0.01" placeholder="Sale Price" value="${parseFloat(adData.sale_price).toFixed(2)}" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                                    <textarea id="editDescription" placeholder="Description" class="w-full px-4 py-3 cyber-input placeholder-gray-400" rows="5">${adData.description || ''}</textarea>
                                    <input id="editWhatsappNumber" type="text" placeholder="WhatsApp Number" value="${adData.whatsapp_number || ''}" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                                    <input id="editTelephone" type="text" placeholder="Telephone" value="${adData.telephone || ''}" class="w-full px-4 py-3 cyber-input placeholder-gray-400">
                                </div>
                                <div class="flex space-x-4">
                                    <button id="submitEditAd" class="w-full cyber-btn py-3 font-semibold" data-ad-id="${adId}">Save Changes</button>
                                    <button id="cancelEditAd" class="w-full social-btn py-3 font-semibold">Cancel</button>
                                </div>
                            </div>`;

                        accountPopup.innerHTML = editForm;
                        loginPopup.classList.remove('hidden');
                        loginForm.classList.add('hidden');
                        registerForm.classList.add('hidden');
                        accountPopup.classList.remove('hidden');

                        document.getElementById('cancelEditAd').addEventListener('click', async () => {
                            loginPopup.classList.add('hidden');
                            loginForm.classList.add('hidden');
                            registerForm.classList.add('hidden');
                            accountPopup.classList.add('hidden');
                            accountPopup.innerHTML = '';
                            accountBtn.dispatchEvent(new Event('click'));
                        });

                        document.getElementById('submitEditAd').addEventListener('click', async () => {
                            const updatedAd = {
                                ad_id: adId,
                                title: document.getElementById('editTitle').value,
                                sale_price: parseFloat(document.getElementById('editSalePrice').value),
                                description: document.getElementById('editDescription').value,
                                whatsapp_number: document.getElementById('editWhatsappNumber').value,
                                telephone: document.getElementById('editTelephone').value,
                                email: user.email
                            };

                            if (!updatedAd.title || !updatedAd.sale_price || !updatedAd.description || !updatedAd.whatsapp_number || !updatedAd.telephone) {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'All fields are required',
                                    icon: 'error',
                                    confirmButtonText: 'OK',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                                    }
                                });
                                return;
                            }

                            try {
                                const response = await fetch('api/update-ad.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify(updatedAd)
                                });
                                const data = await response.json();
                                if (response.ok && data.success) {
                                    Swal.fire({
                                        title: 'Success',
                                        text: 'Ad updated successfully!',
                                        icon: 'success',
                                        confirmButtonText: 'OK',
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                                        }
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: data.message || 'Failed to update ad',
                                        icon: 'error',
                                        confirmButtonText: 'OK',
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                                        }
                                    });
                                }
                            } catch (error) {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Error updating ad: ' + error.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK',
                                    buttonsStyling: false,
                                    customClass: {
                                        confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                                    }
                                });
                            }
                        });
                    });
                });

                document.querySelectorAll('.delete-ad-btn').forEach(button => {
                    button.addEventListener('click', async (e) => {
                        e.preventDefault();
                        const adId = e.target.getAttribute('data-ad-id');
                        Swal.fire({
                            title: 'Are you sure?',
                            text: 'This will permanently delete the ad.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, delete it',
                            cancelButtonText: 'Cancel',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'cyber-btn px-4 py-2 font-semibold',
                                cancelButton: 'social-btn px-4 py-2 font-semibold'
                            }
                        }).then(async (result) => {
                            if (result.isConfirmed) {
                                try {
                                    const response = await fetch('api/delete-ad.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({ ad_id: adId })
                                    });
                                    const data = await response.json();
                                    if (response.ok && data.success) {
                                        Swal.fire({
                                            title: 'Deleted',
                                            text: 'Ad deleted successfully!',
                                            icon: 'success',
                                            confirmButtonText: 'OK',
                                            buttonsStyling: false,
                                            customClass: {
                                                confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                                            }
                                        });
                                        document.querySelector(`.ad-card[data-ad-id="${adId}"]`).closest('a').remove();
                                        if (!recentAdsContainer.querySelector('.ad-card')) {
                                            noAdsMessage.classList.remove('hidden');
                                        }
                                    } else {
                                        Swal.fire({
                                            title: 'Error',
                                            text: data.message || 'Failed to delete ad',
                                            icon: 'error',
                                            confirmButtonText: 'OK',
                                            buttonsStyling: false,
                                            customClass: {
                                                confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                                            }
                                        });
                                    }
                                } catch (error) {
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'Error deleting ad: ' + error.message,
                                        icon: 'error',
                                        confirmButtonText: 'OK',
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                                        }
                                    });
                                }
                            }
                        });
                    });
                });
            } else {
                noAdsMessage.classList.remove('hidden');
            }
        } catch (error) {
            Swal.fire({
                title: 'Error',
                text: 'Error fetching ads: ' + error.message,
                icon: 'error',
                confirmButtonText: 'OK',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                }
            });
        }

        loginPopup.classList.remove('hidden');
        loginForm.classList.add('hidden');
        registerForm.classList.add('hidden');
        accountPopup.classList.remove('hidden');
    }
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
    window.location.href = window.location.pathname;
});

// Helper function to convert timestamp to "time ago" format
function timeSince(timestamp) {
    const datetime = new Date(timestamp);
    const now = new Date();
    const interval = Math.floor((now - datetime) / 1000);

    if (interval < 60) return 'just now';
    if (interval < 3600) return Math.floor(interval / 60) + ' minute' + (Math.floor(interval / 60) > 1 ? 's' : '') + ' ago';
    if (interval < 86400) return Math.floor(interval / 3600) + ' hour' + (Math.floor(interval / 3600) > 1 ? 's' : '') + ' ago';
    if (interval < 2592000) return Math.floor(interval / 86400) + ' day' + (Math.floor(interval / 86400) > 1 ? 's' : '') + ' ago';
    if (interval < 31536000) return Math.floor(interval / 2592000) + ' month' + (Math.floor(interval / 2592000) > 1 ? 's' : '') + ' ago';
    return Math.floor(interval / 31536000) + ' year' + (Math.floor(interval / 31536000) > 1 ? 's' : '') + ' ago';
}

// Scroll to All Listings section
function scrollToListings() {
    const listingsSection = document.querySelector('section.py-12.bg-gray-50');
    if (listingsSection) {
        listingsSection.scrollIntoView({ behavior: 'smooth' });
    }
}

// Search handling
document.getElementById('searchButton').addEventListener('click', async () => {
    const searchInput = document.querySelector('header input').value.trim();
    const searchButton = document.getElementById('searchButton');

    if (!searchInput) {
        window.location.reload();
        return;
    }

    searchButton.disabled = true;
    searchButton.textContent = 'search...';

    try {
        const response = await fetch('api/search-ads.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ search: searchInput })
        });
        const data = await response.json();
        if (response.ok) {
            const listingsContainer = document.querySelector('.grid.grid-cols-1.gap-6');
            listingsContainer.innerHTML = '';
            if (data.ads.length > 0) {
                data.ads.forEach(ad => {
                    const adElement = `
                        <a href="ad-details.php?id=${ad.id}" class="block">
                            <div class="ad-card flex">
                                <div class="ad-image-container">
                                    <img src="${ad.image_path || 'images/placeholder.jpg'}" alt="${ad.title}" class="ad-image">
                                </div>
                                <div class="ad-details">
                                    <div>
                                        <h3 class="font-semibold text-gray-800 uppercase">${ad.title}</h3>
                                        <p class="text-gray-600">${ad.category_name}</p>
                                        <p class="text-gray-600">${ad.district_name}, ${ad.area_name}</p>
                                        <p class="font-bold text-orange-600 price">Rs ${parseFloat(ad.sale_price).toLocaleString()}</p>
                                    </div>
                                    <div class="ad-details-footer">
                                        <div class="time-ago">${timeSince(ad.created_at)}</div>
                                        <div class="view-count-container">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span class="view-count-text">Views</span>
                                            <span class="view-count-number">${ad.view_count || 0}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>`;
                    listingsContainer.innerHTML += adElement;
                });
            } else {
                listingsContainer.innerHTML = '<p class="text-center text-gray-500">No listings found</p>';
            }
            scrollToListings();
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message || 'Search failed',
                icon: 'error',
                confirmButtonText: 'OK',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                }
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Error',
            text: 'Error during search: ' + error.message,
            icon: 'error',
            confirmButtonText: 'OK',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'cyber-btn px-4 py-2 font-semibold'
            }
        });
    } finally {
        searchButton.disabled = false;
        searchButton.textContent = 'Search';
    }
});

// Add Enter key support for search
document.querySelector('header input').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        document.getElementById('searchButton').click();
    }
});

// Filter handling
const filterBtn = document.getElementById('filterBtn');
const filterPopup = document.getElementById('filterPopup');
const closeFilterPopup = document.getElementById('closeFilterPopup');

filterBtn.addEventListener('click', () => {
    filterPopup.classList.remove('hidden');
});

closeFilterPopup.addEventListener('click', () => {
    filterPopup.classList.add('hidden');
});

// Category button click handler
document.querySelectorAll('.container button[data-category-id]').forEach(button => {
    button.addEventListener('click', () => {
        const categoryId = button.getAttribute('data-category-id');
        // Update URL with category filter
        const url = new URL(window.location);
        url.searchParams.set('page', '1');
        url.searchParams.set('category', categoryId);
        url.searchParams.delete('location'); // Clear location filter if any
        window.location.href = url.toString();
    });
});

// Filter handling
document.getElementById('applyFilters').addEventListener('click', () => {
    const category = document.getElementById('category').value;
    const location = document.getElementById('location').value;

    // Update URL with filters
    const url = new URL(window.location);
    url.searchParams.set('page', '1');
    if (category) {
        url.searchParams.set('category', category);
    } else {
        url.searchParams.delete('category');
    }
    if (location) {
        url.searchParams.set('location', location);
    } else {
        url.searchParams.delete('location');
    }
    window.location.href = url.toString();
});

// Login handling
document.getElementById('submitLogin').addEventListener('click', async () => {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;

    // Check for admin credentials
    if (email === 'janith2310@gmail.com' && password === 'janith1234') {
        Swal.fire({
            title: 'Success',
            text: 'Admin login successful!',
            icon: 'success',
            confirmButtonText: 'OK',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'cyber-btn px-4 py-2 font-semibold'
            }
        }).then(() => {
            localStorage.setItem('user', JSON.stringify({
                email: email,
                firstName: 'Admin',
                lastName: ''
            }));
            window.location.href = 'admin.php';
        });
        return;
    }

    try {
        const response = await fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const data = await response.json();
        if (response.ok) {
            Swal.fire({
                title: 'Success',
                text: 'Login successful!',
                icon: 'success',
                confirmButtonText: 'OK',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                }
            });
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
            window.location.reload();
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message || 'Login failed',
                icon: 'error',
                confirmButtonText: 'OK',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                }
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Error',
            text: 'Error during login: ' + error.message,
            icon: 'error',
            confirmButtonText: 'OK',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'cyber-btn px-4 py-2 font-semibold'
            }
        });
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
        Swal.fire({
            title: 'Error',
            text: 'Passwords do not match',
            icon: 'error',
            confirmButtonText: 'OK',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'cyber-btn px-4 py-2 font-semibold'
            }
        });
        return;
    }

    try {
        const response = await fetch('api/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ firstName, lastName, email, password })
        });
        const data = await response.json();
        if (response.ok) {
            Swal.fire({
                title: 'Success',
                text: 'Registration successful! Please login.',
                icon: 'success',
                confirmButtonText: 'OK',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                }
            });
            registerForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
            accountPopup.classList.add('hidden');
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message || 'Registration failed',
                icon: 'error',
                confirmButtonText: 'OK',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                }
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Error',
            text: 'Error during registration: ' + error.message,
            icon: 'error',
            confirmButtonText: 'OK',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'cyber-btn px-4 py-2 font-semibold'
            }
        });
    }
});

// Post Your Ad handling
document.querySelectorAll('a[href="sell.php"][class*="rounded-full"]').forEach(button => {
    button.addEventListener('click', (e) => {
        e.preventDefault();
        const user = JSON.parse(localStorage.getItem('user'));
        if (user) {
            window.location.href = 'sell.php';
        } else {
            Swal.fire({
                title: 'Please Login',
                text: 'You need to log in before posting an ad.',
                icon: 'warning',
                confirmButtonText: 'Login Now',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'cyber-btn px-4 py-2 font-semibold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    loginPopup.classList.remove('hidden');
                    loginForm.classList.remove('hidden');
                    registerForm.classList.add('hidden');
                    accountPopup.classList.add('hidden');
                }
            });
        }
    });
});

// Initialize wave text
    document.addEventListener('DOMContentLoaded', () => {
      const text = "Find Everything You Need";
      const waveText = document.getElementById("wave-text");
      waveText.innerHTML = '';

      // Split into words, then characters
      text.split(" ").forEach((word, wordIndex) => {
        const wordSpan = document.createElement("span");
        wordSpan.style.display = "inline-block";
        wordSpan.style.marginRight = "8px";

        word.split("").forEach((char, charIndex) => {
          const span = document.createElement("span");
          span.className = "wave-letter";
          span.textContent = char;
          span.style.animationDelay = `${(wordIndex * 5 + charIndex) * 0.1}s`;
          wordSpan.appendChild(span);
        });

        waveText.appendChild(wordSpan);
      });

    // Check for filters in URL and scroll to listings
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('category') || urlParams.has('location')) {
        scrollToListings();
    }
});

// Initialize page
document.addEventListener('DOMContentLoaded', () => {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user) {
        loginBtn.classList.add('hidden');
        accountBtn.classList.remove('hidden');
        loginBtnMobile.classList.add('hidden');
        accountBtnMobile.classList.remove('hidden');
    } else {
        loginBtn.classList.remove('hidden');
        accountBtn.classList.add('hidden');
        loginBtnMobile.classList.remove('hidden');
        accountBtnMobile.classList.add('hidden');
    }
});
document.addEventListener('DOMContentLoaded', function () {
    // Custom styling for Choices.js
    const customStyles = {
        containerOuter: {
            background: '#0f0f2a',
            border: '1px solid #ff6200',
            borderRadius: '8px',
        },
        containerInner: {
            background: '#0f0f2a',
        },
        input: {
            background: '#0f0f2a',
            color: '#ffffff', // Changed from #00f6ff to #ffffff
        },
        dropdown: {
            background: '#0f0f2a',
            border: '1px solid #ff6200',
            borderRadius: '8px',
        },
        list: {
            background: '#0f0f2a',
        },
        listItems: {
            background: '#0f0f2a',
        },
        item: {
            background: '#0f0f2a',
            color: '#ffffff', // Changed from #00f6ff to #ffffff
        },
        itemSelectable: {
            background: '#0f0f2a',
            color: '#ffffff', // Changed from #00f6ff to #ffffff
        },
        itemChoice: {
            background: '#0f0f2a',
            color: '#ffffff', // Changed from #00f6ff to #ffffff
        },
        group: {
            background: '#0f0f2a',
            color: '#ff6200',
            fontWeight: 'bold',
            fontSize: '1.2rem',
            padding: '10px 5px',
            borderBottom: '2px solid rgba(255, 98, 0, 0.5)',
        },
        groupHeading: {
            color: '#ff6200',
            background: 'rgba(255, 98, 0, 0.1)',
            fontWeight: 'bold',
            fontSize: '1.2rem',
            padding: '10px 15px',
        },
    };

    // Initialize category dropdown with custom styles
    const categoryChoices = new Choices('#category', {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        position: 'bottom',
        maxHeight: 200,
        classNames: {
            containerOuter: 'choices',
            containerInner: 'choices__inner',
            input: 'choices__input',
            inputCloned: 'choices__input--cloned',
            list: 'choices__list',
            listItems: 'choices__list--multiple',
            listSingle: 'choices__list--single',
            listDropdown: 'choices__list--dropdown',
            item: 'choices__item',
            itemSelectable: 'choices__item--selectable',
            itemDisabled: 'choices__item--disabled',
            itemChoice: 'choices__item--choice',
            placeholder: 'choices__placeholder',
            group: 'choices__group',
            groupHeading: 'choices__heading',
            button: 'choices__button',
            activeState: 'is-active',
            focusState: 'is-focused',
            openState: 'is-open',
            disabledState: 'is-disabled',
            highlightedState: 'is-highlighted',
            selectedState: 'is-selected',
            flippedState: 'is-flipped',
            loadingState: 'is-loading',
            noResults: 'has-no-results',
            noChoices: 'has-no-choices',
        },
        callbackOnInit: function() {
            // Customize the dropdown after initialization
            const dropdown = document.querySelector('.choices__list--dropdown');
            if (dropdown) {
                dropdown.style.background = '#0f0f2a';
                dropdown.style.border = '1px solid #ff6200';
                dropdown.style.borderRadius = '8px';
            }
            
            // Style optgroup headings
            const groupHeadings = document.querySelectorAll('.choices__heading');
            groupHeadings.forEach(heading => {
                heading.style.color = '#ff6200';
                heading.style.fontWeight = 'bold';
                heading.style.fontSize = '1.2rem';
                heading.style.background = 'rgba(255, 98, 0, 0.1)';
                heading.style.padding = '10px 15px';
                heading.style.borderBottom = '2px solid rgba(255, 98, 0, 0.5)';
            });
            
            // Style options
            const options = document.querySelectorAll('.choices__list--dropdown .choices__item');
            options.forEach(option => {
                option.style.color = '#ffffff'; // Changed from #00f6ff to #ffffff
                option.style.background = '#0f0f2a';
                option.style.padding = '8px 15px';
                
                // Hover effect
                option.addEventListener('mouseenter', function() {
                    this.style.background = 'rgba(255, 98, 0, 0.2)';
                    this.style.color = '#ffffff';
                });
                
                option.addEventListener('mouseleave', function() {
                    this.style.background = '#0f0f2a';
                    this.style.color = '#ffffff'; // Changed from #00f6ff to #ffffff
                });
            });
        }
    });

    // Initialize location dropdown
    const locationChoices = new Choices('#location', {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        position: 'bottom',
        maxHeight: 200,
        callbackOnInit: function() {
            // Style the dropdown
            const dropdown = document.querySelectorAll('.choices__list--dropdown')[1];
            if (dropdown) {
                dropdown.style.background = '#0f0f2a';
                dropdown.style.border = '1px solid #ff6200';
                dropdown.style.borderRadius = '8px';
            }
            
            // Style options for location dropdown
            const options = document.querySelectorAll('.choices__list--dropdown .choices__item');
            options.forEach(option => {
                option.style.color = '#ffffff'; // Changed from #00f6ff to #ffffff
                option.style.background = '#0f0f2a';
                option.style.padding = '8px 15px';
                
                // Hover effect
                option.addEventListener('mouseenter', function() {
                    this.style.background = 'rgba(255, 98, 0, 0.2)';
                    this.style.color = '#ffffff';
                });
                
                option.addEventListener('mouseleave', function() {
                    this.style.background = '#0f0f2a';
                    this.style.color = '#ffffff'; // Changed from #00f6ff to #ffffff
                });
            });
        }
    });
    
    // Add CSS to override Choices.js defaults
    const style = document.createElement('style');
    style.textContent = `
        .choices__inner {
            background: #0f0f2a !important;
            border: 1px solid #ff6200 !important;
            border-radius: 8px !important;
            min-height: 48px !important;
            padding: 8px 16px !important;
        }
        
        .choices__list--dropdown {
            background: #0f0f2a !important;
            border: 1px solid #ff6200 !important;
            border-radius: 8px !important;
        }
        
        /* Changed from #00f6ff to #ffffff */
        .choices__list--dropdown .choices__item {
            color: #ffffff !important;
            background: #0f0f2a !important;
            padding: 8px 15px !important;
        }
        
        .choices__list--dropdown .choices__item:hover {
            background: rgba(255, 98, 0, 0.2) !important;
            color: #ffffff !important;
        }
        
        .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background: rgba(255, 98, 0, 0.3) !important;
            color: #ffffff !important;
        }
        
        /* Group headings stay orange */
        .choices__heading {
            color: #ff6200 !important;
            font-weight: bold !important;
            font-size: 1.2rem !important;
            background: rgba(255, 98, 0, 0.1) !important;
            padding: 10px 15px !important;
            border-bottom: 2px solid rgba(255, 98, 0, 0.5) !important;
            margin: 0 !important;
        }
        
        /* Changed from #00f6ff to #ffffff */
        .choices__input {
            background: #0f0f2a !important;
            color: #ffffff !important;
            border-bottom: 1px solid rgba(255, 98, 0, 0.5) !important;
        }
        
        /* Placeholder stays orange */
        .choices__placeholder {
            color: #ff6200 !important;
            opacity: 0.9 !important;
        }
        
        /* Selected item text color */
        .choices__list--single .choices__item {
            color: #ffffff !important;
        }
        
        /* Dropdown text color - Changed from #00f6ff to #ffffff */
        .choices__list--dropdown .choices__item {
            color: #ffffff !important;
        }
        
        /* Button color (clear button) */
        .choices__button {
            border-color: #ff6200 !important;
        }
        
        /* Search input placeholder */
        .choices__input::placeholder {
            color: rgba(255, 98, 0, 0.7) !important;
        }
        
        /* No results/choices text */
        .has-no-results .choices__item,
        .has-no-choices .choices__item {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        
        /* Active/hover state for selected items */
        .choices__item--selectable.is-highlighted {
            background: rgba(255, 98, 0, 0.3) !important;
            color: #ffffff !important;
        }
    `;
    document.head.appendChild(style);
});
</script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Choices('#category', {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: '',
                position: 'bottom',
                maxHeight: 200
            });

            new Choices('#location', {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: '',
                position: 'bottom',
                maxHeight: 200
            });
        });
    </script>
</body>
</html>