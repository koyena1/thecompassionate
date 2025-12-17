<?php
// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "safe_space_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- NEW VISITOR TRACKING LOGIC ---
$visitor_ip = $_SERVER['REMOTE_ADDR'];
$today = date('Y-m-d');

// INSERT IGNORE prevents duplicate entries for the same IP on the same day
$track_stmt = $conn->prepare("INSERT IGNORE INTO site_visitors (ip_address, visit_date) VALUES (?, ?)");
$track_stmt->bind_param("ss", $visitor_ip, $today);
$track_stmt->execute();
$track_stmt->close();
?>