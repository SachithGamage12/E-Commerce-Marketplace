<?php
session_start();
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to post an ad.']);
    exit;
}
// Database connection
$host = 'localhost';
$dbname = 'markets_markethub';
$username = 'markets_root';
$password = 'Sun123flower@';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_submit'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    $display_name = trim($_POST['display_name'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $subcategory_id = (int)($_POST['subcategory_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $sale_price = (float)($_POST['sale_price'] ?? 0.0);
    $address = trim($_POST['address'] ?? '');
    $whatsapp_number = trim($_POST['whatsapp_number'] ?? '');
    $description = trim($_POST['description'] ?? '');
    // Use the authenticated user_id from session
    $user_id = $_SESSION['user_id'];
    // Validate inputs
    $phonePattern = '/^(?:\+94|0)(?:(?:70|71|72|74|75|76|77|78|81)\d{7}|38\d{7})$/';
    $mobilePattern = '/^(?:\+94|0)(?:70|71|72|74|75|76|77|78|81)\d{7}$/';
    if (!preg_match($phonePattern, $telephone)) {
        $response['message'] = "Please enter a valid Sri Lankan mobile or landline number (e.g., +947XXXXXXXX or 038XXXXXXX).";
    } elseif (!empty($whatsapp_number) && !preg_match($mobilePattern, $whatsapp_number)) {
        $response['message'] = "Please enter a valid Sri Lankan mobile number for WhatsApp (e.g., +947XXXXXXXX).";
    } elseif ($subcategory_id <= 0) {
        $response['message'] = "Please select a valid subcategory.";
    } elseif (empty($display_name) || empty($district) || empty($area) || empty($title)) {
        $response['message'] = "Please fill all required fields.";
    } elseif ($sale_price > 0 && $sale_price <= 10) {
        $response['message'] = "Sale price must be greater than LKR 10 if provided.";
    } else {
        try {
            $pdo->beginTransaction();
            // Insert ad into ads table
            $stmt = $pdo->prepare("INSERT INTO ads (user_id, display_name, telephone, email, district, area, subcategory_id, title, sale_price, address, whatsapp_number, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $display_name, $telephone, $email, $district, $area, $subcategory_id, $title, $sale_price, $address, $whatsapp_number, $description]);
            $ad_id = $pdo->lastInsertId();
            $pdo->commit();
            $response['success'] = true;
            $response['message'] = "Ad posted successfully! Please upload images for your ad.";
            $_SESSION['ad_id'] = $ad_id;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $response['message'] = "Failed to post ad: " . $e->getMessage();
            error_log("Database error: " . $e->getMessage());
        }
    }
    echo json_encode($response);
    exit;
}
// Fetch districts and categories
try {
    $stmt = $pdo->query("SELECT id, name FROM districts ORDER BY name");
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $districts = [];
    $error_message = "Failed to fetch districts: " . $e->getMessage();
}
try {
    $stmt = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
    $main_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $main_categories = [];
    $error_message = "Failed to fetch categories: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Markets.lk - Your Modern Marketplace</title>
    <link rel="icon" type="image/png" href="images/image.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <style>
        .cyber-btn {
            background: linear-gradient(90deg, #ff6200, #00f6ff);
            border-radius: 8px;
            color: #ffffff;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            padding: 0.5rem 1rem;
        }
        .cyber-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 246, 255, 0.3);
        }
        .card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.85));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        .stepper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .step {
            display: flex;
            align-items: center;
            margin: 0 0.5rem;
        }
        .step-icon {
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            background-color: #d1d5db;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 0.875rem;
        }
        .step-icon.active {
            background-color: #8b5cf6;
        }
        .step-line {
            width: 2rem;
            height: 2px;
            background-color: #d1d5db;
            margin: 0 0.25rem;
        }
        .step-line.active {
            background-color: #8b5cf6;
        }
        .form-section {
            margin-bottom: 1.5rem;
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .form-group input:disabled,
        .form-group select:disabled {
            background-color: #f3f4f6;
            cursor: not-allowed;
        }
        .image-upload-placeholder {
            border: 2px dashed #d1d5db;
            padding: 1rem;
            text-align: center;
            color: #6b7280;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            position: relative;
        }
        .border-red-500 {
            border-color: #ef4444;
        }
        .error-message {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: none;
        }
        .error-message.active {
            display: block;
        }
        .custom-editor-toolbar {
            border: 1px solid #d1d5db;
            border-bottom: none;
            border-radius: 0.375rem 0.375rem 0 0;
            padding: 0.5rem;
            background: #f9fafb;
        }
        .custom-editor-toolbar button {
            padding: 0.25rem 0.5rem;
            margin-right: 0.25rem;
            background: #e5e7eb;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        .custom-editor {
            border: 1px solid #d1d5db;
            border-radius: 0 0 0.375rem 0.375rem;
            min-height: 200px;
            padding: 0.75rem;
            outline: none;
        }
        .custom-editor:focus {
            border-color: #8b5cf6;
        }
        .image-preview {
            max-width: 100%;
            max-height: 100px;
            object-fit: cover;
            border-radius: 0.25rem;
        }
        .video-preview {
            max-width: 100%;
            max-height: 100px;
            object-fit: cover;
            border-radius: 0.25rem;
        }
        .image-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(239, 68, 68, 0.8);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
            z-index: 10;
        }
        .upload-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .file-count {
            font-size: 0.75rem;
            color: #666;
            margin-top: 0.5rem;
        }
        @media (max-width: 640px) {
            .stepper {
                gap: 0.5rem;
            }
            .step {
                margin: 0 0.25rem;
            }
            .step-icon {
                width: 1.25rem;
                height: 1.25rem;
                font-size: 0.75rem;
            }
            .step-line {
                display: none;
            }
            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 0.5rem;
            }
            .form-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            .cyber-btn {
                padding: 0.5rem;
                font-size: 0.875rem;
            }
            .upload-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            pointer-events: auto;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #ff6200;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .media-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .video-icon {
            font-size: 24px;
            color: #ff6200;
            margin-right: 8px;
        }
    </style>
</head>
<body class="font-chakra bg-gray-100">
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>
    <header class="bg-gradient-to-r from-orange-400 to-orange-600 text-white">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row justify-between items-center">
            <a href="index.php">
                <img src="images/mk.png" alt="Markets.lk Logo" class="h-14 object-contain">
            </a>
            <div class="navbar-nav flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <a href="index.php" class="flex items-center px-4 py-2 text-black bg-gradient-to-r from-sky-100 to-blue-200 rounded-full hover:from-sky-200 hover:to-blue-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 2L2 9h2v9h4v-6h4v6h4V9h2L10 2z" />
                    </svg>
                    Home
                </a>
            </div>
        </nav>
    </header>
    <section class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 max-w-3xl">
        <div id="message-container">
            <?php if (isset($success_message)): ?>
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded text-sm sm:text-base"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded text-sm sm:text-base"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
        </div>
        <form id="postAdForm" method="POST" action="post_ad.php" enctype="multipart/form-data" class="card p-4 sm:p-6">
            <div class="stepper">
                <div class="step">
                    <div class="step-icon active" data-step="1">1</div>
                </div>
                <div class="step-line" data-line="1"></div>
                <div class="step">
                    <div class="step-icon" data-step="2">2</div>
                </div>
                <div class="step-line" data-line="2"></div>
                <div class="step">
                    <div class="step-icon" data-step="3">3</div>
                </div>
                <div class="step-line" data-line="3"></div>
                <div class="step">
                    <div class="step-icon" data-step="4">4</div>
                </div>
            </div>
            <div id="step-1" class="form-section active">
                <h3 class="text-lg sm:text-xl font-semibold mb-4">Basic Details *</h3>
                <div class="form-group">
                    <label>Display Name *</label>
                    <input type="text" name="display_name" id="display_name" required>
                </div>
                <div class="form-group">
                    <label>Display Telephone Number *</label>
                    <input type="text" name="telephone" id="telephone" required>
                    <span id="telephone-error" class="error-message">Please enter a valid Sri Lankan mobile or landline number (e.g., +947XXXXXXXX or 038XXXXXXX).</span>
                </div>
                <div class="form-group">
                    <label>Display Email (Enter email you wish the customer to communicate with you)</label>
                    <input type="email" name="email" id="email">
                </div>
                <div class="form-group">
                    <label>Select Your District *</label>
                    <select name="district" id="district" required>
                        <option value="">Select a district</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?php echo htmlspecialchars($district['name']); ?>"><?php echo htmlspecialchars($district['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Your Area *</label>
                    <select name="area" id="area" required>
                        <option value="">Select a district first</option>
                    </select>
                </div>
            </div>
            <div id="step-2" class="form-section">
                <h3 class="text-lg sm:text-xl font-semibold mb-4">Primary Details *</h3>
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" id="category" required>
                        <option value="">Select a category</option>
                        <?php foreach ($main_categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sub Category *</label>
                    <select name="subcategory_id" id="subcategory" required>
                        <option value="">Select a category first</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="Keep it short!" required>
                </div>
                <div class="form-group">
                    <label>Sale Price Per Unit (LKR)</label>
                    <input type="number" step="0.01" name="sale_price" id="sale_price" placeholder="10.00">
                    <span id="sale-price-error" class="error-message">Sale price must be greater than LKR 10 if provided.</span>
                </div>
                <div class="form-group">
                    <label>Use this number for WhatsApp</label>
                    <input type="text" name="whatsapp_number" id="whatsapp_number">
                    <span id="whatsapp-error" class="error-message">Please enter a valid Sri Lankan mobile number for WhatsApp (e.g., +947XXXXXXXX).</span>
                </div>
                <div class="form-group">
                    <label>Address (Display address to gain trust)</label>
                    <textarea name="address" rows="3"></textarea>
                </div>
            </div>
            <div id="step-3" class="form-section">
                <h3 class="text-lg sm:text-xl font-semibold mb-4">Description</h3>
                <div class="form-group">
                    <div class="custom-editor-toolbar">
                        <button type="button" onclick="formatText('bold')">Bold</button>
                        <button type="button" onclick="formatText('italic')">Italic</button>
                        <button type="button" onclick="formatText('underline')">Underline</button>
                        <button type="button" onclick="formatText('bullet')">Bullet List</button>
                    </div>
                    <div class="custom-editor" id="description" contenteditable="true"></div>
                    <textarea name="description" id="description-hidden" style="display: none;"></textarea>
                </div>
            </div>
            <div id="step-4" class="form-section">
                <h3 class="text-lg sm:text-xl font-semibold mb-4">Upload Images & Videos</h3>
                <p class="text-sm text-gray-600 mb-4">Maximum 5 images (JPG/PNG) and 1 video (MP4/MOV/AVI, max 50MB). Files will be automatically optimized for web viewing.</p>
                
                <div class="upload-buttons">
                    <div class="image-upload-placeholder">
                        <p>Select Images (Max 5)</p>
                        <input type="file" name="images[]" id="image-upload" multiple accept="image/jpeg,image/png" style="display: none;">
                        <button type="button" onclick="document.getElementById('image-upload').click()" class="cyber-btn mt-2">Select Images</button>
                        <div class="file-count" id="image-count">0 images selected</div>
                    </div>
                    
                    <div class="image-upload-placeholder">
                        <p>Select Video (Max 1)</p>
                        <input type="file" name="video" id="video-upload" accept="video/mp4,video/mov,video/avi" style="display: none;">
                        <button type="button" onclick="document.getElementById('video-upload').click()" class="cyber-btn mt-2 bg-gradient-to-r from-purple-600 to-pink-600">Select Video</button>
                        <div class="file-count" id="video-count">No video selected</div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" id="media-previews"></div>
            </div>
            <input type="hidden" name="final_submit" value="1">
            <div class="form-actions flex flex-col sm:flex-row justify-between mt-4 gap-2">
                <button type="button" id="prevBtn" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400" style="display: none;">Previous</button>
                <button type="button" id="nextBtn" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Next</button>
            </div>
        </form>
    </section>
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
                    <p class="text-gray-400 text-center md:text-left">
                        Your trusted marketplace for buying and selling locally.
                    </p>
                </div>
                <div class="text-center md:text-left">
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-400 hover:text-orange-500 transition-colors">Home</a></li>
                        <li><a href="about-us.php" class="text-gray-400 hover:text-orange-500 transition-colors">About Us</a></li>
                        <li><a href="sell.php" class="text-gray-400 hover:text-orange-500 transition-colors">Post your Ad</a></li>
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
            .wave-path {
                animation: wave 8s ease-in-out infinite;
            }
            @keyframes wave {
                0% { d: path('M0,60 C360,120 1080,0 1440,60 L1440,100 L0,100 Z'); }
                50% { d: path('M0,40 C360,100 1080,20 1440,80 L1440,100 L0,100 Z'); }
                100% { d: path('M0,60 C360,120 1080,0 1440,60 L1440,100 L0,100 Z'); }
            }
            footer {
                border: 2px solid #ff6200;
                box-shadow: 0 0 10px #ff6200;
            }
            @media (max-width: 768px) {
                footer .grid { text-align: center; }
                footer .flex { justify-content: center !important; }
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
        // Simulate user data and prefill
        const user = JSON.parse(localStorage.getItem('user')) || {
            firstName: 'Sachith',
            lastName: 'Gamage',
            telephone: '0741773588',
            email: 'udarassachith41@gmail.com'
        };
        window.onload = function () {
            if (user) {
                const displayName = `${user.firstName || ''} ${user.lastName || ''}`.trim();
                document.getElementById('display_name').value = displayName;
                document.getElementById('telephone').value = user.telephone || '';
                document.getElementById('email').value = user.email || '';
                document.getElementById('whatsapp_number').value = user.telephone || '';
            } else {
                window.location.href = 'api/login.php';
            }
        };
        // Fetch areas
        document.getElementById('district').addEventListener('change', function () {
            const districtName = this.value;
            const areaSelect = document.getElementById('area');
            areaSelect.innerHTML = '<option value="">Loading areas...</option>';
            areaSelect.disabled = true; // Disable area dropdown while loading
            if (!districtName) {
                areaSelect.innerHTML = '<option value="">Select a district first</option>';
                areaSelect.disabled = false;
                return;
            }
            fetch('get_areas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `district_name=${encodeURIComponent(districtName)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch areas');
                }
                return response.json();
            })
            .then(data => {
                areaSelect.innerHTML = '<option value="">Select an area</option>';
                if (data.error) {
                    areaSelect.innerHTML += `<option value="">Error: ${data.error}</option>`;
                } else if (data.areas && data.areas.length > 0) {
                    data.areas.forEach(area => {
                        areaSelect.innerHTML += `<option value="${area.name}">${area.name}</option>`;
                    });
                } else {
                    areaSelect.innerHTML += `<option value="">No areas available</option>`;
                }
                areaSelect.disabled = false; // Re-enable dropdown after loading
            })
            .catch(error => {
                areaSelect.innerHTML = '<option value="">Failed to load areas</option>';
                areaSelect.disabled = false;
                console.error('Error fetching areas:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to load areas: ' + error.message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    buttonsStyling: false,
                    customClass: {
                        popup: 'rounded-xl bg-white shadow-xl',
                        title: 'text-xl font-semibold text-gray-800 font-chakra',
                        htmlContainer: 'text-gray-600 text-base',
                        confirmButton: 'px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all font-chakra'
                    }
                });
            });
        });
        // Fetch subcategories
        document.getElementById('category').addEventListener('change', function () {
            const categoryId = this.value;
            const subcategorySelect = document.getElementById('subcategory');
            subcategorySelect.innerHTML = '<option value="">Loading subcategories...</option>';
            if (!categoryId) {
                subcategorySelect.innerHTML = '<option value="">Select a category first</option>';
                return;
            }
            fetch('get_subcategories.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `category_id=${encodeURIComponent(categoryId)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                subcategorySelect.innerHTML = '<option value="">Select a subcategory</option>';
                if (data.error) {
                    subcategorySelect.innerHTML += `<option value="">Error: ${data.error}</option>`;
                } else if (data.subcategories.length === 0) {
                    subcategorySelect.innerHTML += `<option value="">No subcategories available</option>`;
                } else {
                    data.subcategories.forEach(subcategory => {
                        subcategorySelect.innerHTML += `<option value="${subcategory.id}">${subcategory.name}</option>`;
                    });
                }
            })
            .catch(error => {
                subcategorySelect.innerHTML = '<option value="">Failed to load subcategories</option>';
                console.error('Error fetching subcategories:', error);
            });
        });
        // Validate Sri Lankan mobile and landline number
        const telephoneInput = document.getElementById('telephone');
        const telephoneError = document.getElementById('telephone-error');
        const whatsappInput = document.getElementById('whatsapp_number');
        const whatsappError = document.getElementById('whatsapp-error');
        const phoneRegex = /^(?:\+94|0)(?:(?:70|71|72|74|75|76|77|78|81)\d{7}|38\d{7})$/;
        const mobileRegex = /^(?:\+94|0)(?:70|71|72|74|75|76|77|78|81)\d{7}$/;
        function validateTelephone() {
            const value = telephoneInput.value.trim();
            if (value && !phoneRegex.test(value)) {
                telephoneInput.classList.add('border-red-500');
                telephoneError.classList.add('active');
                return false;
            } else {
                telephoneInput.classList.remove('border-red-500');
                telephoneError.classList.remove('active');
                return true;
            }
        }
        function validateWhatsApp() {
            const value = whatsappInput.value.trim();
            if (value && !mobileRegex.test(value)) {
                whatsappInput.classList.add('border-red-500');
                whatsappError.classList.add('active');
                return false;
            } else {
                whatsappInput.classList.remove('border-red-500');
                whatsappError.classList.remove('active');
                return true;
            }
        }
        telephoneInput.addEventListener('input', function () {
            validateTelephone();
        });
        whatsappInput.addEventListener('input', function () {
            validateWhatsApp();
        });
        // Sale price validation
        const salePriceInput = document.getElementById('sale_price');
        const salePriceError = document.getElementById('sale-price-error');
        function validatePrice(showError = false) {
            const number = parseFloat(salePriceInput.value) || 0;
            if (showError && number > 0 && number <= 10) {
                salePriceInput.classList.add('border-red-500');
                salePriceError.classList.add('active');
                return false;
            } else {
                salePriceInput.classList.remove('border-red-500');
                salePriceError.classList.remove('active');
                return true;
            }
        }
        salePriceInput.addEventListener('input', function () {
            validatePrice();
        });
        salePriceInput.addEventListener('blur', function () {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
            validatePrice(true);
        });
        // Custom editor formatting
        function formatText(command) {
            const editor = document.getElementById('description');
            if (command === 'bullet') {
                const selection = window.getSelection();
                if (selection.rangeCount) {
                    const range = selection.getRangeAt(0);
                    const ul = document.createElement('ul');
                    const li = document.createElement('li');
                    const selectedText = range.toString();
                    li.textContent = selectedText || 'List item';
                    ul.appendChild(li);
                    range.deleteContents();
                    range.insertNode(ul);
                }
            } else {
                document.execCommand(command, false, null);
            }
            updateHiddenDescription();
        }
        function updateHiddenDescription() {
            const editor = document.getElementById('description');
            const hiddenInput = document.getElementById('description-hidden');
            hiddenInput.value = editor.innerHTML;
        }
        document.getElementById('description').addEventListener('input', updateHiddenDescription);
        // Media upload handling
        const imageUpload = document.getElementById('image-upload');
        const videoUpload = document.getElementById('video-upload');
        const mediaPreviews = document.getElementById('media-previews');
        const imageCountElement = document.getElementById('image-count');
        const videoCountElement = document.getElementById('video-count');
        
        let selectedImages = [];
        let selectedVideo = null;
        let imageCount = 0;
        let videoCount = 0;
        
        // Image upload handling
        imageUpload.addEventListener('change', function () {
            const files = Array.from(this.files);
            
            if (imageCount + files.length > 5) {
                Swal.fire({
                    title: 'Maximum Exceeded',
                    text: 'Maximum 5 images allowed.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                this.value = '';
                return;
            }
            
            files.forEach((file, index) => {
                if (imageCount >= 5) return;
                
                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    Swal.fire({
                        title: 'Invalid Format',
                        text: 'Only JPG and PNG images are allowed.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                
                // Check file size (max 10MB per image)
                if (file.size > 10 * 1024 * 1024) {
                    Swal.fire({
                        title: 'File Too Large',
                        text: 'Image must be less than 10MB. File will be compressed.',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                }
                
                selectedImages.push(file);
                
                // Compress and preview image
                compressImage(file, 800, 0.7).then(compressedFile => {
                    createImagePreview(compressedFile, imageCount);
                    imageCount++;
                    updateImageCount();
                });
            });
            
            this.value = '';
        });
        
        // Video upload handling
        videoUpload.addEventListener('change', function () {
            const file = this.files[0];
            
            if (!file) return;
            
            if (selectedVideo) {
                Swal.fire({
                    title: 'Replace Video?',
                    text: 'You can only upload one video. Replace the current video?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, replace',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        handleVideoUpload(file);
                    } else {
                        this.value = '';
                    }
                });
            } else {
                handleVideoUpload(file);
            }
        });
        
        function handleVideoUpload(file) {
            const allowedTypes = ['video/mp4', 'video/mov', 'video/avi'];
            
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({
                    title: 'Invalid Format',
                    text: 'Only MP4, MOV, and AVI videos are allowed.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                videoUpload.value = '';
                return;
            }
            
            // Check file size (max 50MB)
            if (file.size > 50 * 1024 * 1024) {
                Swal.fire({
                    title: 'File Too Large',
                    text: 'Video must be less than 50MB. Please compress your video before uploading.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                videoUpload.value = '';
                return;
            }
            
            selectedVideo = file;
            videoCount = 1;
            
            // Create video preview
            createVideoPreview(file);
            updateVideoCount();
        }
        
        // Image compression function
        function compressImage(file, maxWidth = 800, quality = 0.7) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                
                reader.onload = function(event) {
                    const img = new Image();
                    img.src = event.target.result;
                    
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
                        
                        // Calculate new dimensions
                        if (width > maxWidth) {
                            height = (height * maxWidth) / width;
                            width = maxWidth;
                        }
                        
                        canvas.width = width;
                        canvas.height = height;
                        
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        
                        // Convert to blob with quality
                        canvas.toBlob(function(blob) {
                            const compressedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            resolve(compressedFile);
                        }, 'image/jpeg', quality);
                    };
                    
                    img.onerror = reject;
                };
                
                reader.onerror = reject;
            });
        }
        
        // Create image preview
        function createImagePreview(file, index) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewDiv = document.createElement('div');
                previewDiv.classList = 'image-upload-placeholder relative';
                previewDiv.innerHTML = `
                    <img src="${e.target.result}" class="image-preview" alt="Preview">
                    <span class="image-remove" onclick="removeImage(this, ${index})">×</span>
                `;
                mediaPreviews.appendChild(previewDiv);
            };
            reader.readAsDataURL(file);
        }
        
        // Create video preview
        function createVideoPreview(file) {
            const url = URL.createObjectURL(file);
            const previewDiv = document.createElement('div');
            previewDiv.classList = 'image-upload-placeholder relative';
            previewDiv.innerHTML = `
                <div class="media-container">
                    <span class="video-icon">▶️</span>
                    <video class="video-preview" controls>
                        <source src="${url}" type="${file.type}">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <span class="image-remove" onclick="removeVideo()">×</span>
                <p class="text-xs mt-2">${file.name}</p>
            `;
            mediaPreviews.appendChild(previewDiv);
        }
        
        // Update image count display
        function updateImageCount() {
            imageCountElement.textContent = `${imageCount} image${imageCount !== 1 ? 's' : ''} selected`;
        }
        
        // Update video count display
        function updateVideoCount() {
            videoCountElement.textContent = selectedVideo ? '1 video selected' : 'No video selected';
        }
        
        // Remove image
        function removeImage(element, index) {
            element.parentElement.remove();
            selectedImages.splice(index, 1);
            imageCount--;
            updateImageCount();
            
            // Update remove buttons indices
            document.querySelectorAll('.image-remove').forEach((btn, i) => {
                if (btn.onclick) {
                    const currentOnclick = btn.getAttribute('onclick');
                    if (currentOnclick && currentOnclick.includes('removeImage')) {
                        btn.setAttribute('onclick', `removeImage(this, ${i})`);
                    }
                }
            });
        }
        
        // Remove video
        function removeVideo() {
            selectedVideo = null;
            videoCount = 0;
            updateVideoCount();
            
            // Remove video preview
            const videoPreviews = document.querySelectorAll('.media-container');
            videoPreviews.forEach(container => {
                if (container.querySelector('video')) {
                    container.closest('.image-upload-placeholder').remove();
                }
            });
            
            videoUpload.value = '';
        }
        // Step navigation
        let currentStep = 1;
        const totalSteps = 4;
        function updateStepper() {
            document.querySelectorAll('.step-icon').forEach(icon => {
                const step = parseInt(icon.getAttribute('data-step'));
                icon.classList.toggle('active', step <= currentStep);
            });
            document.querySelectorAll('.step-line').forEach(line => {
                const lineStep = parseInt(line.getAttribute('data-line'));
                line.classList.toggle('active', lineStep < currentStep);
            });
        }
        function showStep(step) {
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.toggle('active', section.id === `step-${step}`);
            });
            document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'block';
            document.getElementById('nextBtn').textContent = step === totalSteps ? 'Submit' : 'Next';
            updateStepper();
        }
        function uploadMedia(adId) {
            if (selectedImages.length === 0 && !selectedVideo) {
                return Promise.resolve({ success: true });
            }
            
            const formData = new FormData();
            
            // Add compressed images
            selectedImages.forEach(file => {
                formData.append('images[]', file);
            });
            
            // Add video if exists
            if (selectedVideo) {
                formData.append('video', selectedVideo);
            }
            
            formData.append('ad_id', adId);
            
            return fetch('upload_images.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Media upload failed: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.errors.join(', '));
                }
                return data;
            });
        }
        document.getElementById('nextBtn').addEventListener('click', () => {
            const currentSection = document.getElementById(`step-${currentStep}`);
            const requiredFields = currentSection.querySelectorAll('[required]');
            let isValid = true;
            requiredFields.forEach(field => {
                if (!field.value.trim() || (field.tagName === 'SELECT' && field.value === '')) {
                    isValid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            if (currentStep === 1) {
                if (!validateTelephone()) {
                    isValid = false;
                }
                const areaSelect = document.getElementById('area');
                if (areaSelect.disabled) {
                    isValid = false;
                    Swal.fire({
                        title: 'Please Wait',
                        text: 'Areas are still loading. Please try again in a moment.',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'rounded-xl bg-white shadow-xl',
                            title: 'text-xl font-semibold text-gray-800 font-chakra',
                            htmlContainer: 'text-gray-600 text-base',
                            confirmButton: 'px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-all font-chakra'
                        }
                    });
                }
            }
            if (currentStep === 2) {
                const subcategory = document.getElementById('subcategory');
                if (!subcategory.value || subcategory.value === '') {
                    isValid = false;
                    subcategory.classList.add('border-red-500');
                } else {
                    subcategory.classList.remove('border-red-500');
                }
                // Only validate price if it has a value
                if (salePriceInput.value.trim() && !validatePrice(true)) {
                    isValid = false;
                }
                // Only validate WhatsApp if it has a value
                if (whatsappInput.value.trim() && !validateWhatsApp()) {
                    isValid = false;
                }
            }
            if (isValid) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                } else {
                    const form = document.getElementById('postAdForm');
                    const formData = new FormData(form);
                    const loadingOverlay = document.getElementById('loading-overlay');
                    document.getElementById('nextBtn').disabled = true;
                    document.getElementById('nextBtn').textContent = 'Submitting...';
                    loadingOverlay.classList.add('active');
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Form submission failed: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            form.style.display = 'none';
                            return fetch('get_ad_id.php')
                                .then(res => {
                                    if (!res.ok) {
                                        throw new Error('Failed to retrieve ad ID: ' + res.statusText);
                                    }
                                    return res.json();
                                })
                                .then(adData => {
                                    if (adData.ad_id) {
                                        return uploadMedia(adData.ad_id);
                                    } else {
                                        throw new Error(adData.error || 'No ad ID found');
                                    }
                                })
                                .then(uploadResult => {
                                    if (uploadResult.success) {
                                        loadingOverlay.classList.remove('active');
                                        document.querySelector('footer').style.display = 'none';
                                        Swal.fire({
                                            title: 'Success!',
                                            text: 'Your ad has been posted successfully!',
                                            icon: 'success',
                                            confirmButtonText: 'OK',
                                            buttonsStyling: false,
                                            customClass: {
                                                popup: 'rounded-xl bg-white shadow-xl',
                                                title: 'text-xl font-semibold text-gray-800 font-chakra',
                                                htmlContainer: 'text-gray-600 text-base',
                                                confirmButton: 'px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all font-chakra'
                                            },
                                            showClass: {
                                                popup: 'animate__animated animate__fadeInDown animate__faster'
                                            },
                                            hideClass: {
                                                popup: 'animate__animated animate__fadeOutUp animate__faster'
                                            },
                                            backdrop: true,
                                            allowOutsideClick: false
                                        }).then(() => {
                                            window.location.href = 'index.php';
                                        });
                                    }
                                });
                        } else {
                            loadingOverlay.classList.remove('active');
                            document.querySelector('footer').style.display = 'none';
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Failed to post ad.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                buttonsStyling: false,
                                customClass: {
                                    popup: 'rounded-xl bg-white shadow-xl',
                                    title: 'text-xl font-semibold text-gray-800 font-chakra',
                                    htmlContainer: 'text-gray-600 text-base',
                                    confirmButton: 'px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all font-chakra'
                                },
                                backdrop: true,
                                allowOutsideClick: false
                            });
                        }
                    })
                    .catch(error => {
                        loadingOverlay.classList.remove('active');
                        document.querySelector('footer').style.display = 'none';
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to submit: ' + error.message,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            buttonsStyling: false,
                            customClass: {
                                popup: 'rounded-xl bg-white shadow-xl',
                                title: 'text-xl font-semibold text-gray-800 font-chakra',
                                htmlContainer: 'text-gray-600 text-base',
                                confirmButton: 'px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all font-chakra'
                            },
                            backdrop: true,
                            allowOutsideClick: false
                        });
                    })
                    .finally(() => {
                        document.getElementById('nextBtn').disabled = false;
                        document.getElementById('nextBtn').textContent = 'Submit';
                    });
                }
            }
        });
        document.getElementById('prevBtn').addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        });
        showStep(currentStep);
    </script>
</body>
</html>