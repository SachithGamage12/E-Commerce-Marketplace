<?php
// Start session
session_start();

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
    die("Connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_submit'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = "You must be logged in to post a job.";
        echo json_encode($response);
        exit;
    }

    $user_id = (int)$_SESSION['user_id']; // Get user_id from session

    $company = trim($_POST['company'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $job_position_id = (int)($_POST['job_position_id'] ?? 0);
    $job_title = trim($_POST['job_title'] ?? '');
    $salary = (float)($_POST['salary'] ?? 0.0);
    $company_details = trim($_POST['company_details'] ?? '');
    $whatsapp_number = trim($_POST['whatsapp_number'] ?? '');
    $job_description = trim($_POST['job_description'] ?? '');

    // Validate inputs
    $phonePattern = '/^(?:\+94|0)(?:70|71|72|74|75|76|77|78|81)\d{7}$/';
    if (!preg_match($phonePattern, $telephone)) {
        $response['message'] = "Please enter a valid Sri Lankan mobile number.";
    } elseif ($job_position_id <= 0) {
        $response['message'] = "Please select a valid job position.";
    } elseif (empty($company) || empty($email) || empty($district) || empty($area) || empty($job_title) || empty($company_details) || empty($whatsapp_number)) {
        $response['message'] = "Please fill all required fields.";
    } else {
        try {
            $pdo->beginTransaction();
            // Insert job into jobs table
            $stmt = $pdo->prepare("INSERT INTO jobs (user_id, company, telephone, email, district, area, job_position_id, job_title, salary, company_details, whatsapp_number, job_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $company, $telephone, $email, $district, $area, $job_position_id, $job_title, $salary, $company_details, $whatsapp_number, $job_description]);
            $job_id = $pdo->lastInsertId();

            $pdo->commit();
            $response['success'] = true;
            $response['message'] = "Job posted successfully! Please upload vacancy images.";
            $_SESSION['job_id'] = $job_id;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $response['message'] = "Failed to post job: " . $e->getMessage();
            error_log("Database error: " . $e->getMessage());
        }
    }

    echo json_encode($response);
    exit;
}
// Fetch districts and job categories
try {
    $stmt = $pdo->query("SELECT id, name FROM districts ORDER BY name");
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $districts = [];
    $error_message = "Failed to fetch districts: " . $e->getMessage();
}

try {
    // Fetch job categories, ensuring "Other" (id=9999) is included
    $stmt = $pdo->query("SELECT id, name FROM job_categories WHERE parent_id IS NULL ORDER BY CASE WHEN id = 9999 THEN 1 ELSE 0 END, name");
    $main_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $main_categories = [];
    $error_message = "Failed to fetch job categories: " . $e->getMessage();
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
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #8b5cf6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
        }
        @media (max-width: 768px) {
            .navbar-nav {
                flex-direction: column;
                gap: 0.5rem;
            }
            .social-icons {
                justify-content: center;
            }
        }
    </style>
</head>
<body class="font-chakra bg-gray-100">
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

        <form id="postJobForm" method="POST" action="job_posting.php" enctype="multipart/form-data" class="card p-4 sm:p-6">
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
                    <label>Company *</label>
                    <input type="text" name="company" id="company" required>
                </div>
                <div class="form-group">
                    <label>Telephone Number *</label>
                    <input type="text" name="telephone" id="telephone" required>
                    <span id="telephone-error" class="error-message">Please enter a valid Sri Lankan mobile number.</span>
                </div>
                <div class="form-group">
                    <label>Email * (Enter email for candidate communication)</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <div class="form-group">
                    <label>Select Your District *</label>
                    <select name="district" id="district" required>
                        <option value="">Select a district</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?php echo htmlspecialchars($district['name']); ?>"><?php echo htmlspecialchars($district['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span id="district-error" class="error-message">Please select a valid district.</span>
                </div>
                <div class="form-group">
                    <label>Select Your Area *</label>
                    <select name="area" id="area" required>
                        <option value="">Select a district first</option>
                    </select>
                    <span id="area-error" class="error-message">Please select a valid area.</span>
                </div>
            </div>

            <div id="step-2" class="form-section">
                <h3 class="text-lg sm:text-xl font-semibold mb-4">Primary Details *</h3>
                <div class="form-group">
                    <label>Job Category *</label>
                    <select name="job_category" id="job_category" required>
                        <option value="">Select a job category</option>
                        <?php foreach ($main_categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Job Position *</label>
                    <select name="job_position_id" id="job_position" required>
                        <option value="">Select a job category first</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Job Title *</label>
                    <input type="text" name="job_title" placeholder="Keep it short!" required>
                </div>
                <div class="form-group">
                    <label>Salary (LKR)</label>
                    <input type="number" step="0.01" name="salary" id="salary" placeholder="50000.00">
                </div>
                <div class="form-group">
                    <label>Use this number for WhatsApp *</label>
                    <input type="text" name="whatsapp_number" id="whatsapp_number" required>
                </div>
                <div class="form-group">
                    <label>Company Details *</label>
                    <textarea name="company_details" rows="3" required></textarea>
                </div>
            </div>

            <div id="step-3" class="form-section">
                <h3 class="text-lg sm:text-xl font-semibold mb-4">Job Description</h3>
                <div class="form-group">
                    <div class="custom-editor-toolbar">
                        <button type="button" onclick="formatText('bold')">Bold</button>
                        <button type="button" onclick="formatText('italic')">Italic</button>
                        <button type="button" onclick="formatText('underline')">Underline</button>
                        <button type="button" onclick="formatText('bullet')">Bullet List</button>
                    </div>
                    <div class="custom-editor" id="job_description" contenteditable="true"></div>
                    <textarea name="job_description" id="job_description-hidden" style="display: none;"></textarea>
                </div>
            </div>

            <div id="step-4" class="form-section">
                <h3 class="text-lg sm:text-xl font-semibold mb-4">Upload Vacancy Images (Minimum 2)</h3>
                <div class="image-upload-placeholder mb-4">
                    <p>Browse your computer (Min 2, Max 5 images, JPG/PNG, 2MB each)</p>
                    <input type="file" name="images[]" id="image-upload" multiple accept="image/jpeg,image/png" style="display: none;">
                    <button type="button" onclick="document.getElementById('image-upload').click()" class="cyber-btn mt-2">Select Images</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" id="image-previews"></div>
            </div>

            <input type="hidden" name="final_submit" value="1">

            <div class="form-actions flex flex-col sm:flex-row justify-between mt-4 gap-2">
                <button type="button" id="prevBtn" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400" style="display: none;">Previous</button>
                <button type="button" id="nextBtn" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Next</button>
            </div>
        </form>

        <!-- Loading Overlay -->
        <div id="loading-overlay" class="loading-overlay">
            <div class="spinner"></div>
        </div>
    </section>

    <footer class="bg-black text-white py-12 relative overflow-hidden">
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
                © 2025 Markets.lk  All rights reserved.<br>
            </div>
        </div>
        <style>
            .wave-path {
                animation: wave 8s ease-in-out infinite;
            }
            @keyframes wave {
                0% {
                    d: path('M0,60 C360,120 1080,0 1440,60 L1440,100 L0,100 Z');
                }
                50% {
                    d: path('M0,40 C360,100 1080,20 1440,80 L1440,100 L0,100 Z');
                }
                100% {
                    d: path('M0,60 C360,120 1080,0 1440,60 L1440,100 L0,100 Z');
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
        // Simulate user data and prefill
        const user = JSON.parse(localStorage.getItem('user')) || {
            firstName: 'Sachith',
            lastName: 'Gamage',
            telephone: '0741773588',
            email: 'udarassachith41@gmail.com'
        };

        window.onload = function () {
            if (user) {
                document.getElementById('telephone').value = user.telephone || '';
                document.getElementById('email').value = user.email || '';
                document.getElementById('whatsapp_number').value = user.telephone || '';
            } else {
                window.location.href = 'api/login.php';
            }
        };

        // Account button (if present)
        const accountBtn = document.getElementById('accountBtn');
        if (accountBtn) {
            accountBtn.addEventListener('click', () => {
                window.location.href = 'account.php';
            });
        }

        // Fetch areas
        document.getElementById('district').addEventListener('change', function () {
            const districtName = this.value;
            const areaSelect = document.getElementById('area');
            const areaError = document.getElementById('area-error');
            areaSelect.innerHTML = '<option value="">Loading areas...</option>';
            areaSelect.disabled = true;

            // Clear error message
            areaError.classList.remove('active');
            areaSelect.classList.remove('border-red-500');

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
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                areaSelect.innerHTML = '<option value="">Select an area</option>';
                areaSelect.disabled = false;

                if (data.error) {
                    areaSelect.innerHTML = '<option value="">No areas available</option>';
                    areaError.textContent = `Error: ${data.error}`;
                    areaError.classList.add('active');
                    console.error('Error fetching areas:', data.error);
                } else if (data.areas && data.areas.length > 0) {
                    data.areas.forEach(area => {
                        areaSelect.innerHTML += `<option value="${area.name}">${area.name}</option>`;
                    });
                } else {
                    areaSelect.innerHTML = '<option value="">No areas available for this district</option>';
                    areaError.textContent = 'No areas found for the selected district.';
                    areaError.classList.add('active');
                }
            })
            .catch(error => {
                areaSelect.innerHTML = '<option value="">Failed to load areas</option>';
                areaSelect.disabled = false;
                areaError.textContent = 'Failed to load areas. Please try again.';
                areaError.classList.add('active');
                console.error('Error fetching areas:', error);
            });
        });

        // Fetch job positions
        document.getElementById('job_category').addEventListener('change', function () {
            const categoryId = this.value;
            const jobPositionSelect = document.getElementById('job_position');
            jobPositionSelect.innerHTML = '<option value="">Loading job positions...</option>';
            jobPositionSelect.disabled = true;

            if (!categoryId) {
                jobPositionSelect.innerHTML = '<option value="">Select a job category first</option>';
                jobPositionSelect.disabled = false;
                return;
            }

            fetch('get_job_positions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `category_id=${encodeURIComponent(categoryId)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                jobPositionSelect.innerHTML = '<option value="">Select a job position</option>';
                jobPositionSelect.disabled = false;

                if (data.error) {
                    jobPositionSelect.innerHTML = `<option value="">Error: ${data.error}</option>`;
                    console.error('Error fetching job positions:', data.error);
                } else if (data.job_positions && data.job_positions.length > 0) {
                    data.job_positions.forEach(position => {
                        jobPositionSelect.innerHTML += `<option value="${position.id}">${position.name}</option>`;
                    });
                } else {
                    jobPositionSelect.innerHTML = '<option value="">No job positions available</option>';
                }
            })
            .catch(error => {
                jobPositionSelect.innerHTML = '<option value="">Failed to load job positions</option>';
                jobPositionSelect.disabled = false;
                console.error('Error fetching job positions:', error);
            });
        });

        // Validate Sri Lankan mobile number
        const telephoneInput = document.getElementById('telephone');
        const telephoneError = document.getElementById('telephone-error');
        const whatsappInput = document.getElementById('whatsapp_number');
        const phoneRegex = /^(?:\+94|0)(?:70|71|72|74|75|76|77|78|81)\d{7}$/;

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

        telephoneInput.addEventListener('input', function () {
            validateTelephone();
            whatsappInput.value = this.value;
        });

        // Custom editor formatting
        function formatText(command) {
            const editor = document.getElementById('job_description');
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
            const editor = document.getElementById('job_description');
            const hiddenInput = document.getElementById('job_description-hidden');
            hiddenInput.value = editor.innerHTML;
        }

        document.getElementById('job_description').addEventListener('input', updateHiddenDescription);

        // Image upload handling (client-side validation and preview)
        const imageUpload = document.getElementById('image-upload');
        const imagePreviews = document.getElementById('image-previews');
        let imageCount = 0;
        let selectedFiles = [];

        imageUpload.addEventListener('change', function () {
            const files = Array.from(this.files);
            if (imageCount + files.length > 5) {
                alert('Maximum 5 images allowed.');
                this.value = '';
                return;
            }

            files.forEach((file, index) => {
                if (imageCount >= 5) return;
                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    alert('Only JPG and PNG images are allowed.');
                    return;
                }

                selectedFiles.push(file);
                const reader = new FileReader();
                reader.onload = function (e) {
                    const previewDiv = document.createElement('div');
                    previewDiv.classList = 'image-upload-placeholder relative';
                    previewDiv.innerHTML = `
                        <img src="${e.target.result}" class="image-preview" alt="Preview">
                        <span class="image-remove" onclick="removeImage(this, ${imageCount})">×</span>
                    `;
                    imagePreviews.appendChild(previewDiv);
                    imageCount++;
                };
                reader.readAsDataURL(file);
            });

            this.value = '';
        });

        function removeImage(element, index) {
            element.parentElement.remove();
            selectedFiles.splice(index, 1);
            imageCount--;
            // Reindex remove buttons
            document.querySelectorAll('.image-remove').forEach((btn, i) => {
                btn.setAttribute('onclick', `removeImage(this, ${i})`);
            });
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

        function uploadImages(jobId) {
            if (selectedFiles.length < 2) {
                return Promise.reject(new Error('Minimum 2 images required.'));
            }

            const formData = new FormData();
            selectedFiles.forEach(file => {
                formData.append('images[]', file);
            });
            formData.append('job_id', jobId);

            return fetch('upload_job_images.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Image upload failed: ' + response.statusText);
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

        function updateMessage(message, isSuccess) {
            const messageContainer = document.getElementById('message-container');
            messageContainer.innerHTML = `
                <div class="mb-4 p-4 ${isSuccess ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'} rounded text-sm sm:text-base">
                    ${message}
                </div>
            `;
        }

        document.getElementById('nextBtn').addEventListener('click', () => {
            const currentSection = document.getElementById(`step-${currentStep}`);
            const requiredFields = currentSection.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
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
                const district = document.getElementById('district');
                const area = document.getElementById('area');
                const districtError = document.getElementById('district-error');
                const areaError = document.getElementById('area-error');

                if (!district.value) {
                    isValid = false;
                    district.classList.add('border-red-500');
                    districtError.classList.add('active');
                } else {
                    district.classList.remove('border-red-500');
                    districtError.classList.remove('active');
                }

                if (!area.value || area.value === 'No areas available' || area.value === 'Failed to load areas') {
                    isValid = false;
                    area.classList.add('border-red-500');
                    areaError.classList.add('active');
                } else {
                    area.classList.remove('border-red-500');
                    areaError.classList.remove('active');
                }
            }

            if (currentStep === 2) {
                const jobPosition = document.getElementById('job_position');
                if (!jobPosition.value || jobPosition.value === 'No job positions available' || jobPosition.value === 'Failed to load job positions') {
                    isValid = false;
                    jobPosition.classList.add('border-red-500');
                } else {
                    jobPosition.classList.remove('border-red-500');
                }
            }

            if (currentStep === 4 && selectedFiles.length < 2) {
                isValid = false;
                document.querySelector('.image-upload-placeholder').classList.add('border-red-500');
                alert('Please upload at least 2 images.');
            } else {
                document.querySelector('.image-upload-placeholder').classList.remove('border-red-500');
            }

            if (isValid) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                } else {
                    const form = document.getElementById('postJobForm');
                    const formData = new FormData(form);
                    const loadingOverlay = document.getElementById('loading-overlay');
                    document.getElementById('nextBtn').disabled = true;
                    document.getElementById('nextBtn').textContent = 'Submitting...';
                    loadingOverlay.style.display = 'flex'; // Show loading spinner

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
                            form.style.display = 'none'; // Hide form after successful submission
                            return fetch('get_job_id.php')
                                .then(res => {
                                    if (!res.ok) {
                                        throw new Error('Failed to retrieve job ID: ' + res.statusText);
                                    }
                                    return res.json();
                                })
                                .then(jobData => {
                                    if (jobData.job_id) {
                                        return uploadImages(jobData.job_id);
                                    } else {
                                        throw new Error(jobData.error || 'No job ID found');
                                    }
                                })
                                .then(uploadResult => {
                                    if (uploadResult.success) {
                                        // Hide loading spinner and footer before showing SweetAlert2
                                        loadingOverlay.style.display = 'none';
                                        document.querySelector('footer').style.display = 'none';
                                        // Show SweetAlert2 success message
                                        Swal.fire({
                                            title: 'Success!',
                                            text: 'Your job has been posted successfully!',
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
                                            // Redirect to homepage or job listing page
                                            window.location.href = 'index.php';
                                        });
                                    }
                                });
                        } else {
                            // Hide loading spinner and footer before showing SweetAlert2
                            loadingOverlay.style.display = 'none';
                            document.querySelector('footer').style.display = 'none';
                            // Show error message using SweetAlert2
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Failed to post job.',
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
                        // Hide loading spinner and footer before showing SweetAlert2
                        loadingOverlay.style.display = 'none';
                        document.querySelector('footer').style.display = 'none';
                        // Show error message using SweetAlert2
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