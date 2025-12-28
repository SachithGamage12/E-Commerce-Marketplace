<?php
// track-view.php
session_start();

// Manual database connection
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

if (isset($_GET['id'])) {
    $adId = (int)$_GET['id'];
    
    // Get user ID from session if available
    $userId = null;
    if (isset($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
    } elseif (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
        $userId = (int)$_SESSION['user']['id'];
    }
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    
    // Prevent duplicate views from same IP within 24 hours (optional)
    $preventDuplicate = true; // Set to false if you want to track all views
    
    if ($preventDuplicate) {
        // Check if this IP has viewed this ad in the last 24 hours
        $checkSql = "SELECT id FROM ad_views 
                     WHERE ad_id = ? AND ip_address = ? 
                     AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                     LIMIT 1";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("is", $adId, $ipAddress);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            // No view from this IP in last 24 hours, insert new view
            $insertSql = "INSERT INTO ad_views (ad_id, user_id, ip_address, viewed_at) 
                         VALUES (?, ?, ?, NOW())";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("iis", $adId, $userId, $ipAddress);
            $insertStmt->execute();
            $insertStmt->close();
        }
        $checkStmt->close();
    } else {
        // Track all views (no duplicate prevention)
        $insertSql = "INSERT INTO ad_views (ad_id, user_id, ip_address, viewed_at) 
                     VALUES (?, ?, ?, NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iis", $adId, $userId, $ipAddress);
        $insertStmt->execute();
        $insertStmt->close();
    }
}

// Close connection
$conn->close();
?>