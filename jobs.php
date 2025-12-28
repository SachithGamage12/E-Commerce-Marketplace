<?php
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

// Pagination logic
$jobsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $jobsPerPage;

// Count total jobs
$countResult = $conn->query("SELECT COUNT(*) as total FROM jobs");
$totalJobs = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalJobs / $jobsPerPage);

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <style>
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
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
        .filter-section {
            background: linear-gradient(145deg, #1a1a3d, #2a2a5e);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 0 20px rgba(255, 98, 0, 0.3);
        }
        .job-card {
            background: linear-gradient(145deg, rgba(255, 98, 0, 0.1), rgba(255, 98, 0, 0.2));
            border: 1px solid #ff6200;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            max-width: 800px;
            min-height: 160px;
            display: flex;
        }
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .job-image-container {
            width: 140px;
            height: 140px;
            flex-shrink: 0;
            margin: 10px;
        }
        .job-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 5px;
        }
        .job-details {
            padding: 10px;
            font-size: 0.9rem;
            line-height: 1.3;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .job-details h3 {
            font-size: 1rem;
            margin-bottom: 4px;
        }
        .job-details .salary {
            font-size: 1.1rem;
            margin-bottom: 4px;
        }
        .job-details p {
            margin-bottom: 4px;
        }
        .sidebar {
            transition: transform 0.3s ease-in-out;
            transform: translateX(-100%);
        }
        .sidebar.open {
            transform: translateX(0);
        }
       
       /* Responsive wave text */
.wave-text {
    display: inline-block;
    text-align: center;
    overflow-wrap: break-word;
    word-break: keep-all;
    line-height: 1.4;
}

.wave-letter {
    display: inline-block;
    animation: waveLetter 1.5s infinite;
    white-space: nowrap;
}

/* Keep words together */
.wave-space {
    display: inline-block;
    width: 0.5em;
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
    .wave-text {
        font-size: 1.5rem; /* Smaller font on mobile */
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 2px;
    }
    
    .wave-letter {
        animation-duration: 2s; /* Slower animation on mobile */
    }
    
    .wave-word {
        display: inline-flex;
        justify-content: center;
    }
}

/* Tablet responsive */
@media (max-width: 768px) {
    .wave-text {
        font-size: 2rem;
    }
}

@keyframes waveLetter {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
    </style>
</head>
<body class="font-chakra bg-gray-100">
    <!-- Hero Section -->
    <header class="bg-gradient-to-r from-orange-400 to-orange-600 text-white">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <!-- Logo and Brand -->
            <div class="flex items-center space-x-2">
                <img src="images/image.png" alt="MarketHub Logo" class="h-14 w-19 object-contain">
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
                <a href="index.php" class="nav-btn flex items-center px-4 py-2 text-black font-semibold bg-gradient-to-r from-yellow-100 to-yellow-200 rounded-full hover:from-yellow-200 hover:to-yellow-300 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 2L2 9h2v9h4v-6h4v6h4V9h2L10 2z" />
                    </svg>
                    Home
                </a>
               <a href="index.php?open=addb" class="nav-btn relative inline-flex items-center px-6 py-2 text-black font-bold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] neon-yellow-border transition-all duration-300">
    <span class="absolute -top-2 -left-2 bg-green-400 text-xs font-bold text-white px-2 py-1 rounded-full animated-free">Free</span>
    Post Your Ad
</a>

            </div>
        </nav>
        <!-- Mobile Sidebar -->
        <div id="sidebar" class="sidebar fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-orange-600 to-orange-400 text-white transform -translate-x-full md:hidden z-50">
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
                <a href="index.php?open=addb" class="nav-btn relative inline-flex items-center px-6 py-2 text-black font-bold bg-[#20f8ff] rounded-full hover:bg-[#15B7D6] neon-yellow-border transition-all duration-300">
    <span class="absolute -top-2 -left-2 bg-green-400 text-xs font-bold text-white px-2 py-1 rounded-full animated-free">Free</span>
    Post Your Ad
</a>

                
            </div>
        </div>
        <div class="container mx-auto px-6 py-16 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4" id="wave-text"></h1>
            <p class="text-xl font-medium text-gray-600 leading-relaxed mb-8">
                Discover and apply for jobs in your local area.
            </p>
            <div class="max-w-xl mx-auto bg-white rounded-full shadow-lg flex items-center p-2">
                <input type="text" id="searchInput" placeholder="Search for jobs..." class="w-full px-4 py-2 text-gray-700 focus:outline-none rounded-l-full">
                <button id="searchBtn" class="bg-blue-600 text-white px-6 py-2 rounded-r-full hover:bg-blue-700">Search</button>
            </div>
        </div>
    </header>

    <!-- Main Content Section -->
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Left Sidebar (Filters) -->
                <div class="md:w-1/4">
                    <div class="filter-section sticky top-4">
                        <h2 class="text-xl font-bold text-white mb-6">Filter Jobs</h2>
                        <!-- Category Filter -->
                        <div class="mb-6">
                            <label for="category" class="block text-sm font-medium text-gray-200 mb-2">Category</label>
                            <select id="category" class="w-full px-4 py-3 cyber-input text-white">
                                <option value="">All Categories</option>
                                <?php
                                $sql = "SELECT id, name FROM job_categories WHERE parent_id IS NOT NULL ORDER BY name ASC";
                                $result = $conn->query($sql);
                                if ($result === FALSE) {
                                    die("Query failed: " . $conn->error);
                                }
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
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
                                        echo '<option value="' . htmlspecialchars($row['name']) . '">' . htmlspecialchars($row['name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <!-- Filter Button -->
                        <button id="applyFilters" class="w-full cyber-btn py-3 font-semibold">Apply Filters</button>
                    </div>
                </div>
                <!-- Right Content (Job Listings) -->
                <div class="md:w-3/4">
                    <h2 class="text-3xl font-bold mb-8">Job Listings</h2>
                    <div id="jobListings" class="grid grid-cols-1 gap-6">
                        <?php
                        $sql = "SELECT j.*, jc.name as category_name, d.name as district_name, a.name as area_name,
                               (SELECT image_path FROM job_images WHERE job_id = j.id LIMIT 1) as image_path
                        FROM jobs j
                        JOIN job_categories jc ON j.job_position_id = jc.id
                        JOIN districts d ON j.district = d.name
                        JOIN areas a ON j.area = a.name
                        JOIN users u ON j.user_id = u.id
                        ORDER BY j.created_at DESC
                        LIMIT $jobsPerPage OFFSET $offset";

                        $result = $conn->query($sql);
                        if ($result === FALSE) {
                            die("Query failed: " . $conn->error);
                        }
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $imagePath = !empty($row['image_path']) ? $row['image_path'] : 'images/placeholder.jpg';
                                $description = htmlspecialchars($row['job_description'] ?? '');
                                $shortDesc = strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description;
                                $salary = $row['salary'] ? 'LKR ' . number_format($row['salary'], 0) : 'Negotiable';
                                ?>
                                <a href="job-details.php?id=<?php echo $row['id']; ?>" class="block">
                                    <div class="job-card flex">
                                        <!-- Image Section -->
                                        <div class="job-image-container">
                                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($row['job_title']); ?>" class="job-image">
                                        </div>
                                        <!-- Details Section -->
                                        <div class="job-details">
                                            <div>
                                                <h3 class="font-semibold text-gray-800 uppercase"><?php echo htmlspecialchars($row['job_title']); ?></h3>
                                                <p class="text-gray-600"><?php echo htmlspecialchars($row['company']); ?></p>
                                                <p class="text-gray-600"><?php echo htmlspecialchars($row['category_name']); ?></p>
                                                <p class="text-gray-600"><?php echo htmlspecialchars($row['district_name'] . ', ' . $row['area_name']); ?></p>
                                                <p class="font-bold text-orange-600 salary"><?php echo $salary; ?></p>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <p class="text-gray-500"><?php echo timeAgo($row['created_at']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <?php
                            }
                        } else {
                            echo '<p class="text-center text-gray-500">No job listings found</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-6 flex justify-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded">Previous</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="px-4 py-2 rounded <?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-100 hover:bg-gray-200'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded">Next</a>
            <?php endif; ?>
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
         .wave-letter {
            display: inline-block;
            animation: wave 1.5s infinite;
        }
        @keyframes wave {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .wave-letter:nth-child(1) { animation-delay: 0s; }
        .wave-letter:nth-child(2) { animation-delay: 0.1s; }
        .wave-letter:nth-child(3) { animation-delay: 0.2s; }
        .wave-letter:nth-child(4) { animation-delay: 0.3s; }
        .wave-letter:nth-child(5) { animation-delay: 0.4s; }
        .wave-letter:nth-child(6) { animation-delay: 0.5s; }
        .wave-letter:nth-child(7) { animation-delay: 0.6s; }
        .wave-letter:nth-child(8) —

 { animation-delay: 0.7s; }
        .wave-letter:nth-child(9) { animation-delay: 0.8s; }
        .wave-letter:nth-child(10) { animation-delay: 0.9s; }
        .wave-letter:nth-child(11) { animation-delay: 1s; }
        .wave-letter:nth-child(12) { animation-delay: 1.1s; }
        .wave-letter:nth-child(13) { animation-delay: 1.2s; }
        .wave-letter:nth-child(14) { animation-delay: 1.3s; }
    </style>
</footer>

    <script>
        // Sidebar toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const closeSidebar = document.getElementById('closeSidebar');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        closeSidebar.addEventListener('click', () => {
            sidebar.classList.remove('open');
        });

        // Filter handling
        document.getElementById('applyFilters').addEventListener('click', async () => {
            const category = document.getElementById('category').value;
            const location = document.getElementById('location').value;

            try {
                const response = await fetch('api/filter-jobs.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        category: category || '', 
                        location: location || ''
                    })
                });
                const data = await response.json();
                if (response.ok) {
                    const listingsContainer = document.getElementById('jobListings');
                    listingsContainer.innerHTML = '';
                    if (data.jobs.length > 0) {
                        data.jobs.forEach(job => {
                            const salary = job.salary ? `LKR ${parseFloat(job.salary).toLocaleString()}` : 'Negotiable';
                            const imagePath = job.image_path || 'images/placeholder.jpg';
                            const jobElement = `
                                <a href="job-details.php?id=${job.id}" class="block">
                                    <div class="job-card flex">
                                        <div class="job-image-container">
                                            <img src="${imagePath}" alt="${job.job_title}" class="job-image">
                                        </div>
                                        <div class="job-details">
                                            <div>
                                                <h3 class="font-semibold text-gray-800 uppercase">${job.job_title}</h3>
                                                <p class="text-gray-600">${job.company}</p>
                                                <p class="text-gray-600">${job.category_name}</p>
                                                <p class="text-gray-600">${job.district_name}, ${job.area_name}</p>
                                                <p class="font-bold text-orange-600 salary">${salary}</p>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <p class="text-gray-500">${timeAgo(job.created_at)}</p>
                                            </div>
                                        </div>
                                    </div>
                                </a>`;
                            listingsContainer.innerHTML += jobElement;
                        });
                    } else {
                        listingsContainer.innerHTML = '<p class="text-center text-gray-500">No job listings found</p>';
                    }
                } else {
                    alert(data.message || 'Error applying filters');
                }
            } catch (error) {
                alert('Error applying filters: ' + error.message);
            }
        });

        // Search handling
        document.getElementById('searchBtn').addEventListener('click', async () => {
            const searchInput = document.getElementById('searchInput').value.trim();
            if (!searchInput) {
                window.location.reload();
                return;
            }

            try {
                const response = await fetch('api/search-jobs.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ search: searchInput })
                });
                const data = await response.json();
                if (response.ok) {
                    const listingsContainer = document.getElementById('jobListings');
                    listingsContainer.innerHTML = '';
                    if (data.jobs.length > 0) {
                        data.jobs.forEach(job => {
                            const salary = job.salary ? `LKR ${parseFloat(job.salary).toLocaleString()}` : 'Negotiable';
                            const imagePath = job.image_path || 'images/placeholder.jpg';
                            const jobElement = `
                                <a href="job-details.php?id=${job.id}" class="block">
                                    <div class="job-card flex">
                                        <div class="job-image-container">
                                            <img src="${imagePath}" alt="${job.job_title}" class="job-image">
                                        </div>
                                        <div class="job-details">
                                            <div>
                                                <h3 class="font-semibold text-gray-800 uppercase">${job.job_title}</h3>
                                                <p class="text-gray-600">${job.company}</p>
                                                <p class="text-gray-600">${job.category_name}</p>
                                                <p class="text-gray-600">${job.district_name}, ${job.area_name}</p>
                                                <p class="font-bold text-orange-600 salary">${salary}</p>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <p class="text-gray-500">${timeAgo(job.created_at)}</p>
                                            </div>
                                        </div>
                                    </div>
                                </a>`;
                            listingsContainer.innerHTML += jobElement;
                        });
                    } else {
                        listingsContainer.innerHTML = '<p class="text-center text-gray-500">No job listings found</p>';
                    }
                } else {
                    alert(data.message || 'Search failed');
                }
            } catch (error) {
                alert('Error during search: ' + error.message);
            }
        });

        // Helper function to convert timestamp to "time ago" format
        function timeAgo(timestamp) {
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

        // Wave text animation
        const text = "Find  Your  Dream  Job";
        const waveText = document.getElementById("wave-text");
        text.split("").forEach((char, index) => {
            const span = document.createElement("span");
            span.className = "wave-letter";
            span.textContent = char === " " ? "\u00A0" : char;
            waveText.appendChild(span);
        });
        // Responsive wave text animation
function initWaveText() {
    const text = "Find Your Dream Job";
    const waveText = document.getElementById("wave-text");
    
    // Clear existing content
    waveText.innerHTML = '';
    
    // Split text into words to keep them together
    const words = text.split(" ");
    
    words.forEach((word, wordIndex) => {
        // Create a container for each word
        const wordSpan = document.createElement('span');
        wordSpan.className = 'wave-word';
        
        // Add each letter of the word
        for (let i = 0; i < word.length; i++) {
            const span = document.createElement("span");
            span.className = "wave-letter";
            span.textContent = word[i];
            
            // Calculate delay based on character position
            const delay = (wordIndex * word.length + i) * 0.1;
            span.style.animationDelay = `${delay}s`;
            
            wordSpan.appendChild(span);
        }
        
        waveText.appendChild(wordSpan);
        
        // Add space between words (except after last word)
        if (wordIndex < words.length - 1) {
            const spaceSpan = document.createElement('span');
            spaceSpan.className = 'wave-space';
            spaceSpan.innerHTML = '&nbsp;';
            waveText.appendChild(spaceSpan);
        }
    });
}

// Initialize wave text on load
initWaveText();

// Re-initialize on window resize for responsiveness
window.addEventListener('resize', initWaveText);
    </script>
</body>
</html>