<?php
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

// Check if user is admin (simplified; implement your own authentication)
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch all feedback
$stmt = $conn->prepare("SELECT id, name, email, feedback_type, message, submission_date FROM feedback ORDER BY submission_date DESC");
$stmt->execute();
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Feedback - fastmarket.lk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .section-bg {
            background: linear-gradient(145deg, #1a1a3d, #2a2a5e);
            border: 2px solid #ff6200;
            box-shadow: 0 0 20px rgba(255, 98, 0, 0.3);
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
    </style>
</head>
<body class="font-chakra bg-gray-100">
    <div class="container mx-auto px-6 py-16">
        <h1 class="text-3xl font-bold text-gray-800 text-center mb-12">Admin - Feedback & Reports</h1>
        <div class="section-bg p-8 rounded-lg">
            <?php if (empty($feedbacks)): ?>
                <p class="text-gray-200 text-center">No feedback or reports submitted yet.</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($feedbacks as $feedback): ?>
                        <div class="bg-gray-700 p-6 rounded-lg border border-gray-600">
                            <h3 class="text-xl font-semibold text-white mb-2"><?php echo htmlspecialchars($feedback['feedback_type']); ?> from <?php echo htmlspecialchars($feedback['name']); ?></h3>
                            <p class="text-gray-200 mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($feedback['email']); ?></p>
                            <p class="text-gray-200 mb-2"><strong>Submitted:</strong> <?php echo htmlspecialchars($feedback['submission_date']); ?></p>
                            <p class="text-gray-200"><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($feedback['message'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <a href="index.php" class="inline-block cyber-btn px-6 py-3 mt-6">Back to Home</a>
        </div>
    </div>
</body>
</html>