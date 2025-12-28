<?php
// Page configuration
$page_title = "About Us - Markets.lk";
$logo_path = "images/image.png"; // Adjust path as needed

// Database connection
$servername = "localhost";
$dbname = 'markets_markethub';
$username = 'markets_root';
$password = 'Sun123flower@';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $feedback_type = htmlspecialchars(trim($_POST['feedback_type']));
    $message = htmlspecialchars(trim($_POST['message']));
    $submission_date = date('Y-m-d H:i:s');

    if (!empty($name) && !empty($email) && !empty($feedback_type) && !empty($message)) {
        try {
            $stmt = $conn->prepare("INSERT INTO feedback (name, email, feedback_type, message, submission_date) VALUES (:name, :email, :feedback_type, :message, :submission_date)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':feedback_type', $feedback_type);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':submission_date', $submission_date);
            $stmt->execute();
            $success_message = "Thank you for your feedback! It has been submitted successfully.";
        } catch(PDOException $e) {
            error_log("Feedback submission failed: " . $e->getMessage());
            $error_message = "An error occurred while submitting your feedback. Please try again.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="icon" type="image/png" href="images/image.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Custom Animations */
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeInUp {
            animation: fadeInUp 0.8s ease-out forwards;
        }
        @keyframes pulseGlow {
            0% { box-shadow: 0 0 10px #ff6200, 0 0 20px #ff6200; transform: scale(1); }
            50% { box-shadow: 0 0 15px #ff6200, 0 0 30px #ff6200; transform: scale(1.02); }
            100% { box-shadow: 0 0 10px #ff6200, 0 0 20px #ff6200; transform: scale(1); }
        }
        .pulse-glow {
            animation: pulseGlow 2s ease-in-out infinite;
        }
        /* Section Styling */
        .section-bg {
            background: linear-gradient(145deg, #1a1a3d, #2a2a5e);
            border: 2px solid #ff6200;
            box-shadow: 0 0 20px rgba(255, 98, 0, 0.3);
        }
        /* Button Styling */
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
        /* Responsive Image */
        .about-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            border: 2px solid #ff6200;
        }
        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body class="font-chakra bg-gray-100">
    <!-- Header Section -->
    <header class="bg-gradient-to-r from-orange-400 to-orange-600 text-white">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <!-- Logo and Brand -->
            <div class="flex items-center space-x-2">
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Markets Logo" class="h-14 w-19 object-contain">
                 <a href="index.php">
  <a href="index.php">
        <img src="images/mk.png" alt="Markets.lk Logo" class="h-14 object-contain">
    </a>
</a>
            </div>
            <!-- Hamburger Menu for Mobile -->
            <button id="menuToggle" class="md:hidden text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                </svg>
            </button>
            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="index.php" class="flex items-center px-4 py-2 text-black bg-gradient-to-r from-sky-100 to-blue-200 rounded-full hover:from-sky-200 hover:to-blue-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Home
                </a>
                <a href="jobs.php" class="flex items-center px-4 py-2 text-black bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Apply Jobs
                </a>
               <a href="index.php?open=addb" class="nav-btn relative inline-flex items-center px-6 py-2 text-black font-bold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] neon-yellow-border transition-all duration-300">
    <span class="absolute -top-2 -left-2 bg-green-400 text-xs font-bold text-white px-2 py-1 rounded-full animated-free">Free</span>
    Post Your Ad
</a>
            </div>
        </nav>
        <!-- Mobile Sidebar -->
        <div id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-orange-400 to-orange-600 text-white transform -translate-x-full md:hidden z-50">
            <div class="flex justify-between items-center p-4 border-b border-orange-300">
                <div class="flex items-center space-x-2">
                    <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Markets Logo" class="h-14 w-19 object-contain">
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
                <a href="index.php" class="flex items-center px-4 py-2 text-black bg-gradient-to-r from-sky-100 to-blue-200 rounded-full hover:from-sky-200 hover:to-blue-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Home
                </a>
                <a href="jobs.php" class="flex items-center px-4 py-2 text-black bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Apply Jobs
                </a>
               <a href="index.php?open=addb" class="nav-btn relative inline-flex items-center px-6 py-2 text-black font-bold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] neon-yellow-border transition-all duration-300">
    <span class="absolute -top-2 -left-2 bg-green-400 text-xs font-bold text-white px-2 py-1 rounded-full animated-free">Free</span>
    Post Your Ad
</a>
            </div>
        </div>
    </header>

    <!-- Report Problem or Feedback Section -->
    <section id="feedback" class="py-16 bg-gray-50">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-gray-800 text-center mb-12 animate-fadeInUp">Report a Problem or Share Feedback</h2>
            <div class="section-bg p-8 rounded-lg animate-fadeInUp" style="animation-delay: 0.2s;">
                <p class="text-gray-200 mb-4">We value your input! Let us know about any issues or share your suggestions to help us improve Markets.lk.</p>
                <?php if (isset($success_message)): ?>
                    <div class="bg-green-500 text-white p-4 rounded-lg mb-4"><?php echo $success_message; ?></div>
                <?php elseif (isset($error_message)): ?>
                    <div class="bg-red-500 text-white p-4 rounded-lg mb-4"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="name" class="block text-white font-semibold mb-2">Your Name</label>
                        <input type="text" id="name" name="name" class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-orange-400" required>
                    </div>
                    <div>
                        <label for="email" class="block text-white font-semibold mb-2">Your Email</label>
                        <input type="email" id="email" name="email" class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-orange-400" required>
                    </div>
                    <div>
                        <label for="feedback_type" class="block text-white font-semibold mb-2">Feedback Type</label>
                        <select id="feedback_type" name="feedback_type" class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-orange-400" required>
                            <option value="Problem">Report a Problem</option>
                            <option value="Feedback">Share Feedback</option>
                        </select>
                    </div>
                    <div>
                        <label for="message" class="block text-white font-semibold mb-2">Your Message</label>
                        <textarea id="message" name="message" rows="5" class="w-full p-3 rounded-lg bg-gray-700 text-white border border-gray-600 focus:outline-none focus:border-orange-400" required></textarea>
                    </div>
                    <button type="submit" name="submit_feedback" class="cyber-btn px-6 py-3 w-full md:w-auto">Submit Feedback</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
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
                    <li><a href="sell.php" class="text-gray-400 hover:text-orange-500 transition-colors">post your Ad</a></li>
                    <li><a href="job.php" class="text-gray-400 hover:text-orange-500 transition-colors">Apply Jobs</a></li>
                    <li><a href="about-us.php" class="text-gray-400 hover:text-orange-500 transition-colors">Terms of Service</a></li>
                </ul>
            </div>
            <!-- Right Section: Follow Us -->
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
        /* Wave Animation */
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
        /* Orange Border */
        footer {
            border: 2px solid #ff6200;
            box-shadow: 0 0 10px #ff6200;
        }
        /* Responsive Adjustments */
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

    <!-- JavaScript for Sidebar Toggle and Feedback Form -->
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const closeSidebar = document.getElementById('closeSidebar');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
        });

        closeSidebar.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
        });

        // Handle Post Your Ad button click
        document.querySelectorAll('a[href="sell.php"]').forEach(button => {
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
                            window.location.href = 'index.php';
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>