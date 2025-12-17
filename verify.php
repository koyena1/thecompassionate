<?php
// verify.php
session_start();
include 'config/db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists and user is NOT verified yet
    // Matches your DB columns: 'token' and 'is_verified'
    $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE token = ? AND is_verified = 0 LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['patient_id'];

        // Update user to verified and remove token
        $update = $conn->prepare("UPDATE patients SET is_verified = 1, token = NULL WHERE patient_id = ?");
        $update->bind_param("i", $user_id);
        
        if ($update->execute()) {
            $_SESSION['success_msg'] = "Email verified successfully! You can now login.";
            header("Location: login.php");
            exit();
        } else {
            echo "Error updating record.";
        }
    } else {
        // Simple error output matching your site colors
        echo "<body style='background-color: #589167; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif;'>";
        echo "<div><h2>Invalid or Expired Link</h2><p>This verification link may have already been used.</p><a href='login.php' style='color:white;'>Return to Login</a></div>";
        echo "</body>";
    }
} else {
    echo "No token provided.";
}
?>