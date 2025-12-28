<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

$conn = new mysqli('localhost', 'markets_root', 'Sun123flower@', 'markets_markethub');
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Handle delete user request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    header('Content-Type: application/json');
    $user_id = intval($_POST['user_id']);
    if ($user_id <= 0) {
        error_log("Invalid user_id: $user_id");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $check = $conn->prepare("SELECT id FROM users WHERE id = ?");
        if (!$check) {
            throw new Exception("Check prepare failed: " . $conn->error);
        }
        $check->bind_param("i", $user_id);
        if (!$check->execute()) {
            throw new Exception("Check execute failed: " . $check->error);
        }
        $check->store_result();
        if ($check->num_rows == 0) {
            $conn->rollback();
            error_log("User not found: user_id $user_id");
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            $check->close();
            exit;
        }
        $check->close();

        $stmt_ads = $conn->prepare("DELETE FROM ads WHERE user_id = ?");
        if (!$stmt_ads) {
            throw new Exception("Ads delete prepare failed: " . $conn->error);
        }
        $stmt_ads->bind_param("i", $user_id);
        if (!$stmt_ads->execute()) {
            throw new Exception("Ads delete execute failed: " . $stmt_ads->error);
        }
        $stmt_ads->close();

        $stmt_jobs = $conn->prepare("DELETE FROM jobs WHERE user_id = ?");
        if (!$stmt_jobs) {
            throw new Exception("Jobs delete prepare failed: " . $conn->error);
        }
        $stmt_jobs->bind_param("i", $user_id);
        if (!$stmt_jobs->execute()) {
            throw new Exception("Jobs delete execute failed: " . $stmt_jobs->error);
        }
        $stmt_jobs->close();

        $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        if (!$stmt_user) {
            throw new Exception("User delete prepare failed: " . $conn->error);
        }
        $stmt_user->bind_param("i", $user_id);
        if (!$stmt_user->execute()) {
            throw new Exception("User delete execute failed: " . $stmt_user->error);
        }
        if ($stmt_user->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'User and their ads/jobs deleted successfully']);
        } else {
            $conn->rollback();
            error_log("No user deleted for user_id: $user_id");
            echo json_encode(['success' => false, 'error' => 'No user deleted']);
        }
        $stmt_user->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete user error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle delete ad request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_ad') {
    header('Content-Type: application/json');
    $ad_id = intval($_POST['ad_id']);
    if ($ad_id <= 0) {
        error_log("Invalid ad_id: $ad_id");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid ad ID']);
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM ads WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Ad delete prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $ad_id);
        if (!$stmt->execute()) {
            throw new Exception("Ad delete execute failed: " . $stmt->error);
        }
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Ad deleted successfully']);
        } else {
            error_log("No ad deleted for ad_id: $ad_id");
            echo json_encode(['success' => false, 'error' => 'No ad deleted']);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Ad delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle delete job request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_job') {
    header('Content-Type: application/json');
    $job_id = intval($_POST['job_id']);
    if ($job_id <= 0) {
        error_log("Invalid job_id: $job_id");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid job ID']);
        exit;
    }

    $conn->begin_transaction();
    try {
        // Delete related images first
        $stmt_images = $conn->prepare("DELETE FROM job_images WHERE job_id = ?");
        if (!$stmt_images) {
            throw new Exception("Image delete prepare failed: " . $conn->error);
        }
        $stmt_images->bind_param("i", $job_id);
        if (!$stmt_images->execute()) {
            throw new Exception("Image delete execute failed: " . $stmt_images->error);
        }
        $stmt_images->close();

        // Delete the job
        $stmt_job = $conn->prepare("DELETE FROM jobs WHERE id = ?");
        if (!$stmt_job) {
            throw new Exception("Job delete prepare failed: " . $conn->error);
        }
        $stmt_job->bind_param("i", $job_id);
        if (!$stmt_job->execute()) {
            throw new Exception("Job delete execute failed: " . $stmt_job->error);
        }
        if ($stmt_job->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Job deleted successfully']);
        } else {
            $conn->rollback();
            error_log("No job deleted for job_id: $job_id");
            echo json_encode(['success' => false, 'error' => 'No job deleted']);
        }
        $stmt_job->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Job delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle fetch all ads request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_all_ads') {
    header('Content-Type: application/json');
    try {
        $query = "
            SELECT 
                a.id, a.display_name, a.telephone, a.email, a.district, a.area, 
                a.title, a.sale_price, a.address, a.whatsapp_number, a.description, 
                a.created_at, a.subcategory_id, 
                (SELECT image_path 
                 FROM ad_images ai 
                 WHERE ai.ad_id = a.id 
                 ORDER BY ai.created_at ASC 
                 LIMIT 1) as image_path
            FROM ads a
        ";
        $params = [];
        $types = "";

        if (!empty($_POST['search'])) {
            $search = "%" . $conn->real_escape_string($_POST['search']) . "%";
            $query .= " WHERE a.title LIKE ?";
            $params[] = $search;
            $types .= "s";
        }

        $query .= " ORDER BY a.created_at DESC";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Fetch ads prepare failed: " . $conn->error);
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            throw new Exception("Fetch ads execute failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $ads = [];
        while ($row = $result->fetch_assoc()) {
            $ads[] = $row;
        }
        echo json_encode(['success' => true, 'ads' => $ads]);
        $stmt->close();
    } catch (Exception $e) {
        error_log("Fetch all ads error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle fetch all jobs request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_all_jobs') {
    header('Content-Type: application/json');
    try {
        $query = "
            SELECT 
                j.id, j.company, j.telephone, j.email, j.district, j.area, 
                j.job_title, j.salary, j.company_details, j.whatsapp_number, j.job_description, 
                j.created_at, j.job_position_id, 
                (SELECT image_path 
                 FROM job_images ji 
                 WHERE ji.job_id = j.id 
                 ORDER BY ji.created_at ASC 
                 LIMIT 1) as image_path
            FROM jobs j
        ";
        $params = [];
        $types = "";

        if (!empty($_POST['search'])) {
            $search = "%" . $conn->real_escape_string($_POST['search']) . "%";
            $query .= " WHERE j.job_title LIKE ?";
            $params[] = $search;
            $types .= "s";
        }

        $query .= " ORDER BY j.created_at DESC";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Fetch jobs prepare failed: " . $conn->error);
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            throw new Exception("Fetch jobs execute failed: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $jobs = [];
        while ($row = $result->fetch_assoc()) {
            $jobs[] = $row;
        }
        echo json_encode(['success' => true, 'jobs' => $jobs]);
        $stmt->close();
    } catch (Exception $e) {
        error_log("Fetch all jobs error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle add category request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    header('Content-Type: application/json');
    $name = trim($_POST['name']);
    $type = $_POST['type']; // 'main', 'sub', or 'job'
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    if (empty($name)) {
        error_log("Category name is empty");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Category name is required']);
        exit;
    }

    try {
        if ($type === 'main') {
            $table = 'categories';
            $stmt = $conn->prepare("INSERT INTO categories (name, parent_id) VALUES (?, NULL)");
        } elseif ($type === 'sub') {
            $table = 'sub_categories';
            if (!$parent_id) {
                error_log("Parent ID is required for subcategory");
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Parent category is required']);
                exit;
            }
            $stmt = $conn->prepare("INSERT INTO sub_categories (name, main_category_id) VALUES (?, ?)");
        } elseif ($type === 'job') {
            $table = 'job_categories';
            $stmt = $conn->prepare("INSERT INTO job_categories (name, parent_id) VALUES (?, ?)");
        } else {
            error_log("Invalid category type: $type");
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid category type']);
            exit;
        }

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        if ($type === 'main') {
            $stmt->bind_param("s", $name);
        } else {
            $stmt->bind_param("si", $name, $parent_id);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        echo json_encode(['success' => true, 'message' => 'Category added successfully']);
        $stmt->close();
    } catch (Exception $e) {
        error_log("Add category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle delete category request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_category') {
    header('Content-Type: application/json');
    $category_id = intval($_POST['category_id']);
    $type = $_POST['type']; // 'main', 'sub', or 'job'

    if ($category_id <= 0) {
        error_log("Invalid category_id: $category_id");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
        exit;
    }

    $conn->begin_transaction();
    try {
        if ($type === 'main') {
            $table = 'categories';
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND parent_id IS NULL");
        } elseif ($type === 'sub') {
            $table = 'sub_categories';
            $stmt = $conn->prepare("DELETE FROM sub_categories WHERE id = ?");
        } elseif ($type === 'job') {
            $table = 'job_categories';
            $stmt = $conn->prepare("DELETE FROM job_categories WHERE id = ?");
        } else {
            error_log("Invalid category type: $type");
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid category type']);
            exit;
        }

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $category_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
        } else {
            $conn->rollback();
            error_log("No category deleted for category_id: $category_id, type: $type");
            echo json_encode(['success' => false, 'error' => 'No category deleted']);
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle fetch all categories request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_all_categories') {
    header('Content-Type: application/json');
    $type = $_POST['type']; // 'main', 'sub', or 'job'
    $search = isset($_POST['search']) ? "%" . $conn->real_escape_string($_POST['search']) . "%" : null;

    try {
        $categories = [];
        if ($type === 'main') {
            $query = "SELECT id, name FROM categories WHERE parent_id IS NULL";
            if ($search) {
                $query .= " AND name LIKE ?";
            }
            $stmt = $conn->prepare($query);
            if ($search) {
                $stmt->bind_param("s", $search);
            }
        } elseif ($type === 'sub') {
            $query = "
                SELECT s.id, s.name, s.main_category_id, c.name AS parent_name
                FROM sub_categories s
                JOIN categories c ON s.main_category_id = c.id
            ";
            if ($search) {
                $query .= " WHERE s.name LIKE ?";
            }
            $stmt = $conn->prepare($query);
            if ($search) {
                $stmt->bind_param("s", $search);
            }
        } elseif ($type === 'job') {
            $query = "SELECT id, name, parent_id FROM job_categories";
            if ($search) {
                $query .= " WHERE name LIKE ?";
            }
            $stmt = $conn->prepare($query);
            if ($search) {
                $stmt->bind_param("s", $search);
            }
        } else {
            error_log("Invalid category type: $type");
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid category type']);
            exit;
        }

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }

        echo json_encode(['success' => true, 'categories' => $categories]);
        $stmt->close();
    } catch (Exception $e) {
        error_log("Fetch categories error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_feedback') {
    header('Content-Type: application/json');
    $feedback_id = intval($_POST['feedback_id']);
    if ($feedback_id <= 0) {
        error_log("Invalid feedback_id: $feedback_id");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid feedback ID']);
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Feedback delete prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $feedback_id);
        if (!$stmt->execute()) {
            throw new Exception("Feedback delete execute failed: " . $stmt->error);
        }
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Feedback deleted successfully']);
        } else {
            error_log("No feedback deleted for feedback_id: $feedback_id");
            echo json_encode(['success' => false, 'error' => 'No feedback deleted']);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Feedback delete error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>fastmarket.lk - Your Modern Marketplace</title>
    <link rel="icon" type="image/png" href="images/image.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #111827;
            color: #f3f4f6;
        }
        .sidebar {
            transition: all 0.3s ease;
            background-color: rgba(31, 41, 55, 0.95);
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .sidebar a:hover {
            background-color: #4f46e5;
            transform: scale(1.05);
        }
        .card {
            background-color: rgba(31, 41, 55, 0.9);
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .view-btn {
            background-color: #3b82f6;
            color: white;
        }
        .view-btn:hover {
            background-color: #2563eb;
        }
        .delete-btn {
            background-color: #ef4444;
            color: white;
        }
        .delete-btn:hover {
            background-color: #dc2626;
        }
        .user-table {
            width: 100%;
            text-align: left;
            border-collapse: collapse;
        }
        .user-table th, .user-table td {
            padding: 1.5rem;
        }
        .user-table th {
            background-color: #1f2937;
            font-size: 0.75rem;
            font-weight: 600;
            color: #d1d5db;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .user-table tbody tr {
            border-bottom: 1px solid #374151;
            transition: background-color 0.15s;
        }
        .user-table tbody tr:hover {
            background-color: rgba(55, 65, 81, 0.5);
        }
        .ad-card, .job-card {
            background-color: #1f2937;
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .ad-card:hover, .job-card:hover {
            transform: scale(1.02);
        }
        .ad-image, .job-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: #374151;
        }
        .search-bar {
            background-color: #1f2937;
            border: 1px solid #374151;
            color: #f3f4f6;
            padding: 0.75rem;
            border-radius: 0.5rem;
            width: 100%;
            max-width: 500px;
        }
        .search-bar:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.3);
        }
        .animate-pulse-hover:hover {
            animation: pulse 1s infinite;
        }
        .feedback-card {
    display: block;
    width: 100%;
}
@media (max-width: 640px) {
    .feedback-card {
        padding: 1rem;
    }
    .feedback-card h3 {
        font-size: 1rem;
        line-height: 1.5;
    }
    .feedback-card p {
        font-size: 0.875rem;
        line-height: 1.4;
    }
    .feedback-card strong {
        display: block;
        margin-bottom: 0.25rem;
    }
}
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            .sidebar.active {
                width: 280px;
            }
            .user-table {
                display: block;
                overflow-x: auto;
            }
            .user-table thead {
                display: none;
            }
            .user-table tbody tr {
                display: block;
                margin-bottom: 1.5rem;
                padding: 1rem;
                background-color: #1f2937;
                border-radius: 0.5rem;
            }
            .user-table td {
                display: block;
                text-align: left;
                padding: 0.5rem 1rem;
                position: relative;
            }
            .user-table td:before {
                display: block;
                font-weight: 600;
                color: #d1d5db;
                margin-bottom: 0.25rem;
                content: attr(data-label);
            }
            .user-table td:last-child {
                display: flex;
                justify-content: flex-end;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="sidebar fixed inset-y-0 left-0 w-64 flex-shrink-0 z-50">
            <div class="p-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-teal-400">fastmarket.lk Admin</h1>
                <button class="md:hidden text-white" onclick="$('.sidebar').toggleClass('active')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nav class="mt-4">
                <a href="#dashboard" class="" onclick="showSection('dashboard')"><i class="fas fa-tachometer-alt mr-3"></i> Dashboard</a>
                <a href="#ads" class="" onclick="showSection('ads')"><i class="fas fa-ad mr-3"></i> Ads</a>
                <a href="#jobs" class="" onclick="showSection('jobs')"><i class="fas fa-briefcase mr-3"></i> Jobs</a>
                <a href="#users" class="" onclick="showSection('users')"><i class="fas fa-users mr-3"></i> Users</a>
                <a href="#categories" class="" onclick="showSection('categories')"><i class="fas fa-folder-tree mr-3"></i> Categories</a>
                <a href="#feedback" class="" onclick="showSection('feedback')"><i class="fas fa-comment-dots mr-3"></i> Feedback</a>
                <a href="index.php" class=""><i class="fas fa-sign-out-alt mr-3"></i> Logout</a>
            </nav>
            <div class="p-6">
                <button id="theme-toggle" class="btn bg-indigo-500 text-white hover:bg-indigo-600 w-full animate-pulse-hover">
                    <i class="fas fa-moon mr-2"></i> Toggle Dark Mode
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 ml-0 md:ml-64 p-8">
            <button class="md:hidden mb-6 p-3 bg-indigo-600 text-white rounded-lg animate-pulse-hover" onclick="$('.sidebar').toggleClass('active')">
                <i class="fas fa-bars"></i> Menu
            </button>

            <!-- Dashboard Section -->
            <section id="dashboard" class="mb-12">
                <h2 class="text-3xl font-bold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-teal-400">Dashboard</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="card p-6">
                        <div class="flex items-center">
                            <i class="fas fa-ad text-4xl text-indigo-400 mr-4"></i>
                            <div>
                                <h3 class="text-lg font-semibold">Total Ads</h3>
                                <p class="text-3xl font-bold"><?php echo $conn->query("SELECT COUNT(*) FROM ads")->fetch_row()[0]; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="card p-6">
                        <div class="flex items-center">
                            <i class="fas fa-briefcase text-4xl text-teal-400 mr-4"></i>
                            <div>
                                <h3 class="text-lg font-semibold">Total Jobs</h3>
                                <p class="text-3xl font-bold"><?php echo $conn->query("SELECT COUNT(*) FROM jobs")->fetch_row()[0]; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="card p-6">
                        <div class="flex items-center">
                            <i class="fas fa-users text-4xl text-purple-400 mr-4"></i>
                            <div>
                                <h3 class="text-lg font-semibold">Total Users</h3>
                                <p class="text-3xl font-bold"><?php echo $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0]; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card p-6 mt-6">
                    <h3 class="text-lg font-semibold mb-4">Analytics Overview</h3>
                    <canvas id="analyticsChart"></canvas>
                </div>
                <button class="btn view-btn animate-pulse-hover mt-4" onclick="showSection('ads')"><i class="fas fa-ad mr-2"></i> View Ads</button>
                <button class="btn view-btn animate-pulse-hover mt-4 ml-2" onclick="showSection('categories')"><i class="fas fa-folder-tree mr-2"></i> Manage Categories</button>
            </section>

            <!-- Users Section -->
            <section id="users" class="mb-12 hidden">
                <h2 class="text-3xl font-bold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-teal-400">Users</h2>
                <div class="card p-6">
                    <div class="overflow-x-auto">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Email</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="user-table-body">
                                <?php
                                $result = $conn->query("SELECT id, firstName, lastName, email, created_at FROM users ORDER BY created_at DESC");
                                if ($result) {
                                    if ($result->num_rows === 0) {
                                        echo "<tr><td colspan='6'>No users found</td></tr>";
                                    } else {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr data-user-id='" . htmlspecialchars($row['id']) . "'>";
                                            echo "<td data-label='ID'>" . htmlspecialchars($row['id']) . "</td>";
                                            echo "<td data-label='First Name'>" . htmlspecialchars($row['firstName']) . "</td>";
                                            echo "<td data-label='Last Name'>" . htmlspecialchars($row['lastName']) . "</td>";
                                            echo "<td data-label='Email'>" . htmlspecialchars($row['email']) . "</td>";
                                            echo "<td data-label='Created At'>" . htmlspecialchars($row['created_at']) . "</td>";
                                            echo "<td data-label='Action'>";
                                            echo "<button class='btn delete-btn animate-pulse-hover' onclick='confirmDelete(" . $row['id'] . ")'>Delete</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    }
                                    $result->free();
                                } else {
                                    error_log("User query failed: " . $conn->error);
                                    echo "<tr><td colspan='6'>Error loading users: " . htmlspecialchars($conn->error) . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Ads Section -->
            <section id="ads" class="mb-12 hidden">
                <h2 class="text-3xl font-bold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-teal-400">All Ads</h2>
                <div class="card p-6">
                    <div class="mb-6 flex justify-center">
                        <input type="text" id="searchAds" class="search-bar" placeholder="Search ads by title..." onkeyup="searchAds()">
                    </div>
                    <div id="adsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
                </div>
            </section>

            <!-- Jobs Section -->
            <section id="jobs" class="mb-12 hidden">
                <h2 class="text-3xl font-bold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-teal-400">View Job Ads</h2>
                <div class="card p-6">
                    <div class="mb-6 flex justify-center">
                        <input type="text" id="searchJobs" class="search-bar" placeholder="Search jobs by title..." onkeyup="searchJobs()">
                    </div>
                    <div id="jobsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
                </div>
            </section>

            <!-- Categories Section -->
            <section id="categories" class="mb-12 hidden">
                <h2 class="text-3xl font-bold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-teal-400">Manage Categories</h2>
                <div class="card p-6">
                    <!-- Add Category Form -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">Add New Category</h3>
                        <div class="flex flex-col md:flex-row gap-4">
                            <input type="text" id="categoryName" class="search-bar" placeholder="Enter category name...">
                            <select id="categoryType" class="search-bar">
                                <option value="main">Main Category</option>
                                <option value="sub">Sub Category</option>
                                <option value="job">Job Category</option>
                            </select>
                            <select id="parentCategory" class="search-bar hidden">
                                <option value="">Select Parent Category</option>
                                <!-- Populated dynamically -->
                            </select>
                            <button class="btn bg-indigo-500 text-white hover:bg-indigo-600 animate-pulse-hover" onclick="addCategory()">
                                <i class="fas fa-plus mr-2"></i> Add Category
                            </button>
                        </div>
                    </div>
                    <!-- Search Bar -->
                    <div class="mb-6 flex justify-center">
                        <input type="text" id="searchCategories" class="search-bar" placeholder="Search categories by name..." onkeyup="searchCategories()">
                    </div>
                    <!-- Categories Table -->
                    <div class="overflow-x-auto">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Parent</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="category-table-body">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            <!-- Feedback Section -->
<section id="feedback" class="mb-12 hidden">
                <h2 class="text-3xl font-bold mb-6 bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-teal-400 text-center md:text-left">Feedback & Reports</h2>
                <div class="card p-4 sm:p-6">
                    <?php
                    $feedback_query = "SELECT id, name, email, feedback_type, message, submission_date FROM feedback ORDER BY submission_date DESC";
                    $feedback_result = $conn->query($feedback_query);
                    if ($feedback_result) {
                        if ($feedback_result->num_rows === 0) {
                            echo '<p class="text-gray-400 text-center text-sm sm:text-base">No feedback or reports submitted yet.</p>';
                        } else {
                            echo '<div class="space-y-4 sm:space-y-6">';
                            while ($feedback = $feedback_result->fetch_assoc()) {
                                echo '<div class="feedback-card bg-gray-700 p-4 sm:p-6 rounded-lg border border-gray-600 transition-all duration-200 hover:bg-gray-600" data-feedback-id="' . htmlspecialchars($feedback['id']) . '">';
                                echo '<h3 class="text-lg sm:text-xl font-semibold text-white mb-2 break-words">' . htmlspecialchars($feedback['feedback_type']) . ' from ' . htmlspecialchars($feedback['name']) . '</h3>';
                                echo '<p class="text-gray-200 mb-2 text-sm sm:text-base"><strong>Email:</strong> <span class="break-all">' . htmlspecialchars($feedback['email']) . '</span></p>';
                                echo '<p class="text-gray-200 mb-2 text-sm sm:text-base"><strong>Submitted:</strong> ' . htmlspecialchars($feedback['submission_date']) . '</p>';
                                echo '<p class="text-gray-200 mb-4 text-sm sm:text-base"><strong>Message:</strong> ' . nl2br(htmlspecialchars($feedback['message'])) . '</p>';
                                echo '<button class="btn delete-btn animate-pulse-hover w-full sm:w-auto" onclick="deleteFeedback(' . htmlspecialchars($feedback['id']) . ')">Delete Feedback</button>';
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                        $feedback_result->free();
                    } else {
                        error_log("Feedback query failed: " . $conn->error);
                        echo '<p class="text-red-400 text-center text-sm sm:text-base">Error loading feedback: ' . htmlspecialchars($conn->error) . '</p>';
                    }
                    ?>
                </div>
            </section>
        </div>
    </div>
<!-- Feedback Section -->

    <script>
        // Show/hide sections
      function showSection(sectionId) {
            $('#dashboard, #users, #ads, #jobs, #categories, #feedback').addClass('hidden');
            $(`#${sectionId}`).removeClass('hidden');
            $('.sidebar').removeClass('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (sectionId === 'ads') {
                fetchAds();
            } else if (sectionId === 'jobs') {
                fetchJobs();
            } else if (sectionId === 'categories') {
                fetchCategories();
                populateParentCategories();
            }
        }
        // Fetch ads (with optional search term)
        function fetchAds(searchTerm = '') {
            $.ajax({
                url: window.location.href,
                type: 'POST',
                dataType: 'json',
                data: { action: 'fetch_all_ads', search: searchTerm },
                beforeSend: function() {
                    console.log('Fetching ads with search:', searchTerm);
                    $('#adsContainer').html('<p class="text-gray-400">Loading ads...</p>');
                },
                success: function(response) {
                    console.log('Fetch ads response:', response);
                    const adsContainer = $('#adsContainer');
                    adsContainer.empty();
                    if (response && response.success) {
                        if (response.ads.length === 0) {
                            adsContainer.append('<p class="text-gray-400">No ads found.</p>');
                        } else {
                            response.ads.forEach(ad => {
                                const imagePath = ad.image_path || 'https://via.placeholder.com/300x200.png?text=No+Image';
                                const adHtml = `
                                    <div class="ad-card" data-ad-id="${ad.id}">
                                        <img src="${imagePath}" alt="${ad.title}" class="ad-image">
                                        <div class="p-4">
                                            <h3 class="text-lg font-semibold text-indigo-400 mb-2">${ad.title}</h3>
                                            <p class="text-sm"><strong>Sale Price:</strong> Rs.${parseFloat(ad.sale_price).toFixed(2)}</p>
                                            <p class="text-sm"><strong>District:</strong> ${ad.district}</p>
                                            <p class="text-sm"><strong>Area:</strong> ${ad.area}</p>
                                            <p class="text-sm truncate"><strong>Description:</strong> ${ad.description}</p>
                                            <p class="text-sm text-gray-400 mt-2"><strong>Created:</strong> ${new Date(ad.created_at).toLocaleDateString()}</p>
                                            <button class="btn delete-btn animate-pulse-hover mt-4 w-full" onclick="deleteAd(${ad.id})">Delete Ad</button>
                                        </div>
                                    </div>
                                `;
                                adsContainer.append(adHtml);
                            });
                        }
                    } else {
                        const errorMsg = response && response.error ? response.error : 'Failed to fetch ads';
                        console.error('Fetch ads failed:', errorMsg);
                        adsContainer.html(`<p class="text-red-400">Error: ${errorMsg}</p>`);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Fetch ads AJAX error:', status, error, xhr.responseText);
                    $('#adsContainer').html(`<p class="text-red-400">Error: Failed to fetch ads: ${xhr.responseJSON?.error || error}</p>`);
                }
            });
        }

        // Search ads by title
        function searchAds() {
            const searchTerm = $('#searchAds').val().trim();
            fetchAds(searchTerm);
        }

        // Fetch jobs (with optional search term)
        function fetchJobs(searchTerm = '') {
            $.ajax({
                url: window.location.href,
                type: 'POST',
                dataType: 'json',
                data: { action: 'fetch_all_jobs', search: searchTerm },
                beforeSend: function() {
                    console.log('Fetching jobs with search:', searchTerm);
                    $('#jobsContainer').html('<p class="text-gray-400">Loading jobs...</p>');
                },
                success: function(response) {
                    console.log('Fetch jobs response:', response);
                    const jobsContainer = $('#jobsContainer');
                    jobsContainer.empty();
                    if (response && response.success) {
                        if (response.jobs.length === 0) {
                            jobsContainer.append('<p class="text-gray-400">No jobs found.</p>');
                        } else {
                            response.jobs.forEach(job => {
                                const imagePath = job.image_path || 'https://via.placeholder.com/300x200.png?text=No+Image';
                                const jobHtml = `
                                    <div class="job-card" data-job-id="${job.id}">
                                        <img src="${imagePath}" alt="${job.job_title}" class="job-image">
                                        <div class="p-4">
                                            <h3 class="text-lg font-semibold text-teal-400 mb-2">${job.job_title}</h3>
                                            <p class="text-sm"><strong>Company:</strong> ${job.company}</p>
                                            <p class="text-sm"><strong>Salary:</strong> Rs.${job.salary ? parseFloat(job.salary).toFixed(2) : 'N/A'}</p>
                                            <p class="text-sm"><strong>District:</strong> ${job.district}</p>
                                            <p class="text-sm"><strong>Area:</strong> ${job.area}</p>
                                            <p class="text-sm truncate"><strong>Description:</strong> ${job.job_description || 'N/A'}</p>
                                            <p class="text-sm text-gray-400 mt-2"><strong>Created:</strong> ${new Date(job.created_at).toLocaleDateString()}</p>
                                            <button class="btn delete-btn animate-pulse-hover mt-4 w-full" onclick="deleteJob(${job.id})">Delete Job</button>
                                        </div>
                                    </div>
                                `;
                                jobsContainer.append(jobHtml);
                            });
                        }
                    } else {
                        const errorMsg = response && response.error ? response.error : 'Failed to fetch jobs';
                        console.error('Fetch jobs failed:', errorMsg);
                        jobsContainer.html(`<p class="text-red-400">Error: ${errorMsg}</p>`);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Fetch jobs AJAX error:', status, error, xhr.responseText);
                    $('#jobsContainer').html(`<p class="text-red-400">Error: Failed to fetch jobs: ${xhr.responseJSON?.error || error}</p>`);
                }
            });
        }

        // Search jobs by title
        function searchJobs() {
            const searchTerm = $('#searchJobs').val().trim();
            fetchJobs(searchTerm);
        }

        // Delete ad
        function deleteAd(adId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This ad will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        dataType: 'json',
                        data: { action: 'delete_ad', ad_id: adId },
                        beforeSend: function() {
                            console.log('Deleting ad_id:', adId);
                        },
                        success: function(response) {
                            console.log('Delete ad response:', response);
                            if (response && response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message || 'Ad has been deleted.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                $(`div[data-ad-id="${adId}"]`).fadeOut(500, function() {
                                    $(this).remove();
                                    if ($('#adsContainer').children().length === 0) {
                                        $('#adsContainer').append('<p class="text-gray-400">No ads found.</p>');
                                    }
                                });
                            } else {
                                const errorMsg = response && response.error ? response.error : 'Failed to delete ad';
                                console.error('Delete ad failed:', errorMsg);
                                Swal.fire('Error', errorMsg, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete ad AJAX error:', status, error, xhr.responseText);
                            Swal.fire('Error', 'Failed to delete ad: ' + (xhr.responseJSON?.error || error), 'error');
                        }
                    });
                }
            });
        }
 function deleteFeedback(feedbackId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This feedback will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        dataType: 'json',
                        data: { action: 'delete_feedback', feedback_id: feedbackId },
                        beforeSend: function() {
                            console.log('Deleting feedback_id:', feedbackId);
                        },
                        success: function(response) {
                            console.log('Delete feedback response:', response);
                            if (response && response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message || 'Feedback has been deleted.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                $(`div[data-feedback-id="${feedbackId}"]`).fadeOut(500, function() {
                                    $(this).remove();
                                    if ($('.feedback-card').length === 0) {
                                        $('.card.p-4.sm\\:p-6').append('<p class="text-gray-400 text-center text-sm sm:text-base">No feedback or reports submitted yet.</p>');
                                    }
                                });
                            } else {
                                const errorMsg = response && response.error ? response.error : 'Failed to delete feedback';
                                console.error('Delete feedback failed:', errorMsg);
                                Swal.fire('Error', errorMsg, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete feedback AJAX error:', status, error, xhr.responseText);
                            Swal.fire('Error', 'Failed to delete feedback: ' + (xhr.responseJSON?.error || error), 'error');
                        }
                    });
                }
            });
        }
        // Delete job
        function deleteJob(jobId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This job will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        dataType: 'json',
                        data: { action: 'delete_job', job_id: jobId },
                        beforeSend: function() {
                            console.log('Deleting job_id:', jobId);
                        },
                        success: function(response) {
                            console.log('Delete job response:', response);
                            if (response && response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message || 'Job has been deleted.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                $(`div[data-job-id="${jobId}"]`).fadeOut(500, function() {
                                    $(this).remove();
                                    if ($('#jobsContainer').children().length === 0) {
                                        $('#jobsContainer').append('<p class="text-gray-400">No jobs found.</p>');
                                    }
                                });
                            } else {
                                const errorMsg = response && response.error ? response.error : 'Failed to delete job';
                                console.error('Delete job failed:', errorMsg);
                                Swal.fire('Error', errorMsg, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete job AJAX error:', status, error, xhr.responseText);
                            Swal.fire('Error', 'Failed to delete job: ' + (xhr.responseJSON?.error || error), 'error');
                        }
                    });
                }
            });
        }

        // Delete user confirmation
        function confirmDelete(userId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This user and all their ads/jobs will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        dataType: 'json',
                        data: { action: 'delete_user', user_id: userId },
                        beforeSend: function() {
                            console.log('Deleting user_id:', userId);
                        },
                        success: function(response) {
                            console.log('Delete user response:', response);
                            if (response && response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message || 'User has been deleted.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                $(`tr[data-user-id="${userId}"]`).fadeOut(500, function() {
                                    $(this).remove();
                                    if ($('#user-table-body tr').length === 0) {
                                        $('#user-table-body').append('<tr><td colspan="6">No users found</td></tr>');
                                    }
                                });
                            } else {
                                const errorMsg = response && response.error ? response.error : 'Failed to delete user';
                                console.error('Delete user failed:', errorMsg);
                                Swal.fire('Error', errorMsg, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete user AJAX error:', status, error, xhr.responseText);
                            Swal.fire('Error', 'Failed to delete user: ' + (xhr.responseJSON?.error || error), 'error');
                        }
                    });
                }
            });
        }

        // Toggle parent category dropdown based on category type
        $('#categoryType').change(function() {
            const type = $(this).val();
            if (type === 'sub' || type === 'job') {
                $('#parentCategory').removeClass('hidden');
                populateParentCategories(type);
            } else {
                $('#parentCategory').addClass('hidden');
            }
        });

        // Populate parent categories dropdown
        function populateParentCategories(type = 'sub') {
            const parentSelect = $('#parentCategory');
            parentSelect.html('<option value="">Select Parent Category</option>');
            $.ajax({
                url: window.location.href,
                type: 'POST',
                dataType: 'json',
                data: { action: 'fetch_all_categories', type: type === 'sub' ? 'main' : 'job' },
                success: function(response) {
                    if (response && response.success && response.categories) {
                        response.categories.forEach(category => {
                            parentSelect.append(`<option value="${category.id}">${category.name}</option>`);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Populate parent categories error:', status, error);
                }
            });
        }

        // Add new category
        function addCategory() {
            const name = $('#categoryName').val().trim();
            const type = $('#categoryType').val();
            const parent_id = $('#parentCategory').val();

            if (!name) {
                Swal.fire('Error', 'Category name is required', 'error');
                return;
            }

            if ((type === 'sub' || type === 'job') && !parent_id) {
                Swal.fire('Error', 'Please select a parent category', 'error');
                return;
            }

            $.ajax({
                url: window.location.href,
                type: 'POST',
                dataType: 'json',
                data: { action: 'add_category', name: name, type: type, parent_id: parent_id },
                beforeSend: function() {
                    console.log('Adding category:', name, type, parent_id);
                },
                success: function(response) {
                    console.log('Add category response:', response);
                    if (response && response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message || 'Category added successfully.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#categoryName').val('');
                        $('#parentCategory').val('');
                        fetchCategories();
                    } else {
                        const errorMsg = response && response.error ? response.error : 'Failed to add category';
                        console.error('Add category failed:', errorMsg);
                        Swal.fire('Error', errorMsg, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Add category AJAX error:', status, error, xhr.responseText);
                    Swal.fire('Error', 'Failed to add category: ' + (xhr.responseJSON?.error || error), 'error');
                }
            });
        }

        // Fetch categories
        function fetchCategories(searchTerm = '') {
            const typeFilter = $('#categoryType').val() || 'main';
            $.ajax({
                url: window.location.href,
                type: 'POST',
                dataType: 'json',
                data: { action: 'fetch_all_categories', type: typeFilter, search: searchTerm },
                beforeSend: function() {
                    console.log('Fetching categories with type:', typeFilter, 'search:', searchTerm);
                    $('#category-table-body').html('<tr><td colspan="5" class="text-gray-400">Loading categories...</td></tr>');
                },
                success: function(response) {
                    console.log('Fetch categories response:', response);
                    const tableBody = $('#category-table-body');
                    tableBody.empty();
                    if (response && response.success) {
                        if (response.categories.length === 0) {
                            tableBody.append('<tr><td colspan="5" class="text-gray-400">No categories found</td></tr>');
                        } else {
                            response.categories.forEach(category => {
                                const parentName = category.parent_name || category.parent_id || 'None';
                                const type = typeFilter === 'main' ? 'Main Category' : typeFilter === 'sub' ? 'Sub Category' : 'Job Category';
                                const rowHtml = `
                                    <tr data-category-id="${category.id}" data-type="${typeFilter}">
                                        <td data-label="ID">${category.id}</td>
                                        <td data-label="Name">${category.name}</td>
                                        <td data-label="Type">${type}</td>
                                        <td data-label="Parent">${parentName}</td>
                                        <td data-label="Action">
                                            <button class="btn delete-btn animate-pulse-hover" onclick="deleteCategory(${category.id}, '${typeFilter}')">Delete</button>
                                        </td>
                                    </tr>
                                `;
                                tableBody.append(rowHtml);
                            });
                        }
                    } else {
                        const errorMsg = response && response.error ? response.error : 'Failed to fetch categories';
                        console.error('Fetch categories failed:', errorMsg);
                        tableBody.html(`<tr><td colspan="5" class="text-red-400">Error: ${errorMsg}</td></tr>`);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Fetch categories AJAX error:', status, error, xhr.responseText);
                    $('#category-table-body').html(`<tr><td colspan="5" class="text-red-400">Error: Failed to fetch categories: ${xhr.responseJSON?.error || error}</td></tr>`);
                }
            });
        }

        // Search categories
        function searchCategories() {
            const searchTerm = $('#searchCategories').val().trim();
            fetchCategories(searchTerm);
        }

        // Delete category
        function deleteCategory(categoryId, type) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This category will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        dataType: 'json',
                        data: { action: 'delete_category', category_id: categoryId, type: type },
                        beforeSend: function() {
                            console.log('Deleting category_id:', categoryId, 'type:', type);
                        },
                        success: function(response) {
                            console.log('Delete category response:', response);
                            if (response && response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message || 'Category has been deleted.',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                $(`tr[data-category-id="${categoryId}"]`).fadeOut(500, function() {
                                    $(this).remove();
                                    if ($('#category-table-body tr').length === 0) {
                                        $('#category-table-body').append('<tr><td colspan="5" class="text-gray-400">No categories found</td></tr>');
                                    }
                                });
                            } else {
                                const errorMsg = response && response.error ? response.error : 'Failed to delete category';
                                console.error('Delete category failed:', errorMsg);
                                Swal.fire('Error', errorMsg, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete category AJAX error:', status, error, xhr.responseText);
                            Swal.fire('Error', 'Failed to delete category: ' + (xhr.responseJSON?.error || error), 'error');
                        }
                    });
                }
            });
        }
        function showSection(sectionId) {
    $('#dashboard, #users, #ads, #jobs, #categories, #feedback').addClass('hidden');
    $(`#${sectionId}`).removeClass('hidden');
    $('.sidebar').removeClass('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (sectionId === 'ads') {
        fetchAds();
    } else if (sectionId === 'jobs') {
        fetchJobs();
    } else if (sectionId === 'categories') {
        fetchCategories();
        populateParentCategories();
    }
}

        // Close mobile menu on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.sidebar') && !e.target.closest('button')) {
                $('.sidebar').removeClass('active');
            }
        });

        // Dark mode toggle
        $('#theme-toggle').click(function() {
            $('body').toggleClass('bg-gray-100 text-gray-900');
            $('body').toggleClass('bg-gray-900 text-gray-100');
            $('.card').toggleClass('bg-white bg-gray-800 bg-opacity-90');
            $('.sidebar').toggleClass('bg-gray-700 bg-gray-800 bg-opacity-95');
        });

        // Fade-in animation
        document.querySelectorAll('#dashboard, #users, #ads, #jobs, #categories').forEach(el => {
            el.style.opacity = 0;
            setTimeout(() => {
                el.style.transition = 'opacity 0.5s ease';
                el.style.opacity = 1;
            }, 100);
        });
    </script>

    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                datasets: [{
                    label: 'Ads Created',
                    data: [10, 20, 15, 25, 30],
                    borderColor: '#4f46e5',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>