<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Markets.lk - Your Modern Marketplace</title>
    <link rel="icon" type="image/png" href="images/image.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .cyber-btn {
            background: linear-gradient(90deg, #ff6200, #00f6ff);
            border-radius: 8px;
            color: #ffffff;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
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
        .icon-pulse {
            animation: pulse 2s infinite ease-in-out;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .side-panel {
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        }
        .side-panel.open {
            transform: translateX(0);
        }
        .close-btn {
            background: linear-gradient(90deg, #ff6200, #00f6ff);
            color: #ffffff;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .close-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 10px rgba(0, 246, 255, 0.3);
        }
    </style>
</head>
<body class="font-chakra bg-gray-100">
    <!-- Navbar -->
    <header class="bg-gradient-to-r from-orange-400 to-orange-600 text-white">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
             <div class="flex items-center space-x-2">
                    <img src="images/image.png" alt="MarketHub Logo" class="h-14 w-19 object-contain">
                    <a href="index.php">
        <img src="images/mk.png" alt="Markets.lk Logo" class="h-14 object-contain">
    </a>

                </div>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="nav-btn flex items-center px-4 py-2 text-black font-semibold bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 2L2 9h2v9h4v-6h4v6h4V9h2L10 2z" />
                    </svg>
                    Home
                </a>
                
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <section class="container mx-auto px-4 sm:px-6 py-12">
    <h2 id="welcomeMessage" class="text-2xl font-bold text-center mb-8">Welcome Sachith Gamage! Let's post an ad.</h2>
    <p class="text-center text-gray-600 mb-8">Choose any option below</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 max-w-5xl mx-auto">
        <!-- Sell Something -->
        <div class="card p-6 sm:p-8 flex flex-col h-full bg-white shadow-lg rounded-lg">
            <div class="flex items-center mb-4">
                <svg class="w-8 h-8 mr-3 text-green-500 icon-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18l-2 12H5L3 3zm5 12h8m-8 4a2 2 0 100 4 2 2 0 000-4zm8 0a2 2 0 100 4 2 2 0 000-4z"></path>
                </svg>
                <h3 class="text-lg sm:text-xl font-semibold">Sell something</h3>
            </div>
            <p class="text-gray-600 mb-2 text-sm sm:text-base">List an item, property, or service for sale</p>
            <p class="text-gray-600 mb-4 flex-grow text-sm sm:text-base">Put a property up for rent</p>
            <a href="post_ad.php" class="cyber-btn w-full py-2 text-center block mt-auto text-sm sm:text-base">Select</a>
        </div>
        <!-- Post a Job Vacancy -->
        <div class="card p-6 sm:p-8 flex flex-col h-full bg-white shadow-lg rounded-lg">
            <div class="flex items-center mb-4">
                <svg class="w-8 h-8 mr-3 text-blue-500 icon-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h-2m-2 0h-2m-2 0H7m4-14h2m-2 4h2m-6 8h12"></path>
                </svg>
                <h3 class="text-lg sm:text-xl font-semibold">Post a job vacancy</h3>
            </div>
            <p class=" text-gray-600 mb-2 text-sm sm:text-base">Advertise a job opportunity in Sri Lanka</p>
            <p class="text-gray-600 mb-4 flex-grow text-sm sm:text-base">Promote a job opening abroad</p>
            <a href="job_posting.php" class="cyber-btn w-full py-2 text-center block mt-auto text-sm sm:text-base">Select</a>
        </div>
    </div>
    <div class="text-center mt-8">
        <a href="about-us.php" class="text-blue-600 hover:underline mr-4 text-sm sm:text-base">About Us</a>
        <a href="about-us.php" class="text-blue-600 hover:underline text-sm sm:text-base">See our rules</a>
    </div>
</section>

    <!-- Right Side Panel for Main Category Selection -->
    <div id="mainCategoryPanel" class="side-panel fixed top-0 right-0 h-full w-64 sm:w-80 bg-white shadow-lg z-50 p-4 sm:p-6">
        <button id="closePanelBtn" class="close-btn absolute top-4 right-4">×</button>
        <h3 class="text-lg sm:text-xl font-semibold mb-4">Select a Main Category</h3>
        <div id="mainCategoriesPanel" class="space-y-2 mb-4 max-h-[calc(100vh-150px)] overflow-y-auto"></div>
        <div class="flex justify-end">
            <button id="proceedBtn" class="cyber-btn px-4 py-2 hidden">Proceed</button>
        </div>
    </div>

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

    <script>
        // Check login status and update welcome message
        window.onload = function () {
            const user = JSON.parse(localStorage.getItem('user'));
            if (user) {
                const welcomeMessage = document.getElementById('welcomeMessage');
                welcomeMessage.textContent = `Welcome ${user.firstName || 'User'} ${user.lastName || ''}! Let's post an ad.`;
            } else {
                window.location.href = 'api/login.php'; // Redirect to login if not logged in
            }
        };

        // Account button functionality
        document.getElementById('accountBtn').addEventListener('click', () => {
            window.location.href = 'account.php'; // Redirect to account page
        });

        // Side Panel functionality
        const mainCategoryPanel = document.getElementById('mainCategoryPanel');
        const sellSomethingBtn = document.getElementById('sellSomethingBtn');
        const closePanelBtn = document.getElementById('closePanelBtn');
        const proceedBtn = document.getElementById('proceedBtn');
        const mainCategoriesPanelDiv = document.getElementById('mainCategoriesPanel');

        sellSomethingBtn.addEventListener('click', async () => {
            mainCategoryPanel.classList.add('open');
            await fetchMainCategories();
        });

        closePanelBtn.addEventListener('click', () => {
            mainCategoryPanel.classList.remove('open');
            mainCategoriesPanelDiv.innerHTML = '';
            proceedBtn.classList.add('hidden');
        });

        async function fetchMainCategories() {
            try {
                const response = await fetch('api/categories.php?action=main');
                const categories = await response.json();
                mainCategoriesPanelDiv.innerHTML = categories.map(cat => `
                    <button class="w-full flex items-center text-left px-4 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm sm:text-base" data-id="${cat.id}">
                        <span class="mr-2 text-lg">${cat.icon}</span>
                        <span>${cat.name}</span>
                    </button>
                `).join('');
                mainCategoriesPanelDiv.querySelectorAll('button').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const mainCategoryId = btn.dataset.id;
                        proceedBtn.dataset.mainCategoryId = mainCategoryId;
                        proceedBtn.classList.remove('hidden');
                        // Highlight selected button
                        mainCategoriesPanelDiv.querySelectorAll('button').forEach(b => b.classList.remove('bg-gray-200'));
                        btn.classList.add('bg-gray-200');
                    });
                });
            } catch (error) {
                console.error('Error fetching main categories:', error);
            }
        }

        proceedBtn.addEventListener('click', () => {
            const mainCategoryId = proceedBtn.dataset.mainCategoryId;
            if (mainCategoryId) {
                window.location.href = `post_ad.php?main_category_id=${mainCategoryId}`;
            }
        });
    </script>
</body>
</html>