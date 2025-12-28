<?php
// Page configuration
$page_title = "Privacy Policy - Markets.lk";
$logo_path = "images/image.png"; // Adjust path as needed
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
        /* Privacy Policy Styling */
        .policy-container {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .policy-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .policy-section:last-child {
            border-bottom: none;
        }
        .policy-section h2 {
            color: #1a1a3d;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .policy-section h3 {
            color: #ff6200;
            font-size: 1.25rem;
            font-weight: 600;
            margin: 1.5rem 0 0.5rem 0;
        }
        .policy-section p, .policy-section li {
            color: #374151;
            line-height: 1.6;
            margin-bottom: 0.75rem;
        }
        .policy-section ul {
            list-style-type: disc;
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }
        .policy-section li {
            margin-bottom: 0.5rem;
        }
        .last-updated {
            background: linear-gradient(90deg, #ff6200, #ff8f00);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .contact-info {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #ff6200;
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
                    <img src="images/mk.png" alt="Markets.lk Logo" class="h-14 object-contain">
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
                <a href="sell.php" class="relative inline-flex items-center px-6 py-2 text-black font-bold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] transition-all duration-300 pulse-glow">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10m0 0l-4-4m4 4l-4 4m-6 4h10m0 0l-4-4m4 4l-4 4"/>
                    </svg>
                    <span class="absolute -top-2 -left-2 bg-green-400 text-xs font-bold text-white px-2 py-1 rounded-full">Free</span>
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
                <a href="sell.php" class="relative inline-flex items-center px-6 py-2 text-black font-bold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] transition-all duration-300 pulse-glow">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10m0 0l-4-4m4 4l-4 4m-6 4h10m0 0l-4-4m4 4l-4 4"/>
                    </svg>
                    <span class="absolute -top-2 -left-2 bg-green-400 text-xs font-bold text-white px-2 py-1 rounded-full">Free</span>
                    Post Your Ad
                </a>
            </div>
        </div>
    </header>

    <!-- Privacy Policy Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4 animate-fadeInUp">Privacy Policy</h1>
                <div class="last-updated animate-fadeInUp" style="animation-delay: 0.2s;">
                    Last updated: 13 December 2025
                </div>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto animate-fadeInUp" style="animation-delay: 0.4s;">
                    Welcome to markets.lk. Your privacy is important to us. This Privacy Policy explains how we collect, use, and protect your information when you use our website.
                </p>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto animate-fadeInUp" style="animation-delay: 0.6s;">
                    By accessing or using <strong>https://markets.lk</strong>, you agree to this Privacy Policy.
                </p>
            </div>
            
            <div class="policy-container animate-fadeInUp" style="animation-delay: 0.8s;">
                
                <div class="policy-section">
                    <h2>1. Information We Collect</h2>
                    <p>We collect information to provide and improve our services.</p>
                    
                    <h3>a) Personal Information</h3>
                    <p>When you register, post advertisements, or contact us, we may collect:</p>
                    <ul>
                        <li>Name</li>
                        <li>Email address</li>
                        <li>Phone number / WhatsApp number</li>
                        <li>Location details (district, area)</li>
                        <li>Account login information</li>
                        <li>Any details you choose to include in advertisements</li>
                    </ul>
                    
                    <h3>b) Advertisement Content</h3>
                    <ul>
                        <li>Images and videos uploaded by users</li>
                        <li>Ad titles, descriptions, prices, and contact information</li>
                    </ul>
                    
                    <h3>c) Technical Information</h3>
                    <ul>
                        <li>IP address</li>
                        <li>Browser and device information</li>
                        <li>Usage and interaction data</li>
                        <li>Cookies used for website functionality</li>
                    </ul>
                </div>
                
                <div class="policy-section">
                    <h2>2. How We Use Your Information</h2>
                    <p>Your information is used to:</p>
                    <ul>
                        <li>Create and manage user accounts</li>
                        <li>Publish and display advertisements</li>
                        <li>Enable communication between buyers and sellers</li>
                        <li>Maintain website security and performance</li>
                        <li>Improve user experience</li>
                        <li>Prevent fraud and misuse</li>
                        <li>Respond to support requests</li>
                        <li>Comply with applicable laws in Sri Lanka</li>
                    </ul>
                </div>
                
                <div class="policy-section">
                    <h2>3. Information Visibility</h2>
                    <p>Information you include in your advertisements (such as phone numbers, images, and descriptions) will be publicly visible on markets.lk.</p>
                    <p><strong>Please avoid sharing sensitive personal information in ads.</strong></p>
                </div>
                
                <div class="policy-section">
                    <h2>4. Cookies</h2>
                    <p>markets.lk uses cookies strictly for:</p>
                    <ul>
                        <li>User login sessions</li>
                        <li>Website functionality</li>
                        <li>Performance and security purposes</li>
                    </ul>
                    <p>You may disable cookies in your browser settings; however, some website features may not function correctly.</p>
                </div>
                
                <div class="policy-section">
                    <h2>5. Data Storage & Security</h2>
                    <p>We take reasonable measures to protect your personal information, including:</p>
                    <ul>
                        <li>Secure server environments</li>
                        <li>Limited access to stored data</li>
                        <li>Regular monitoring for unauthorized access</li>
                    </ul>
                    <p>Despite these efforts, no online system is completely secure.</p>
                </div>
                
                <div class="policy-section">
                    <h2>6. User Responsibility</h2>
                    <p>Users are responsible for:</p>
                    <ul>
                        <li>Accuracy of information posted in advertisements</li>
                        <li>Safeguarding account login credentials</li>
                        <li>Content uploaded to the platform</li>
                    </ul>
                    <p>markets.lk is not responsible for information users choose to make public.</p>
                </div>
                
                <div class="policy-section">
                    <h2>7. Children's Privacy</h2>
                    <p>markets.lk is intended for users 18 years and above.</p>
                    <p>We do not knowingly collect personal information from minors.</p>
                </div>
                
                <div class="policy-section">
                    <h2>8. Your Rights</h2>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access and update your personal information</li>
                        <li>Delete your account and associated data</li>
                        <li>Request removal of advertisements</li>
                    </ul>
                    <p>These actions can be done through your user account or by contacting us.</p>
                </div>
                
                <div class="policy-section">
                    <h2>9. Changes to This Privacy Policy</h2>
                    <p>We may update this Privacy Policy periodically.</p>
                    <p>Any changes will be posted on this page with a revised date.</p>
                    <p><strong>Continued use of markets.lk indicates acceptance of the updated policy.</strong></p>
                </div>
                
                <div class="policy-section">
                    <h2>10. Contact Information</h2>
                    <p>For questions or concerns regarding this Privacy Policy:</p>
                    <div class="contact-info">
                        <p><strong>Website:</strong> <a href="https://markets.lk" class="text-blue-600 hover:underline">https://markets.lk</a></p>
                        <p><strong>Email:</strong> <a href="mailto:fastmarket.lk@gmail.com" class="text-blue-600 hover:underline">fastmarket.lk@gmail.com</a></p>
                        <p><strong>Mobile:</strong> 0711513555</p>
                        <p><strong>Country:</strong> Sri Lanka</p>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <!-- Footer Section -->
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
                        <li><a href="about-us.php#terms" class="text-gray-400 hover:text-orange-500 transition-colors">Terms of Service</a></li>
                        <li><a href="privacy.php" class="text-gray-400 hover:text-orange-500 transition-colors">Privacy & Policy</a></li>
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
                © 2025 Markets.lk All rights reserved.<br>
                <small class="text-xs mt-2 block">This privacy policy was last updated on 13 December 2025</small>
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

    <!-- JavaScript for Sidebar Toggle -->
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