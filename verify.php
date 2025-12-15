<?php
// verify.php
session_start();
require_once 'config/db.php';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // 1. Find user with this token
    $stmt = $conn->prepare("SELECT patient_id, full_name, email FROM patients WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 2. VERIFY USER & CLEAR TOKEN
        $update = $conn->prepare("UPDATE patients SET is_verified = 1, token = NULL WHERE token = ?");
        $update->bind_param("s", $token);
        $update->execute();
        
        // 3. AUTO LOGIN (Session Set)
        $_SESSION['user_id'] = $user['patient_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = 'patient';
        $_SESSION['full_name'] = $user['full_name'];

        // 4. REDIRECT to Dashboard
        header("Location: patient/dashboard.php");
        exit();

    } else {
        // Token invalid or already used
        echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
                <h2 style='color:red;'>Link Expired</h2>
                <p>This link has already been used or is invalid.</p>
                <a href='login.php'>Go to Login</a>
              </div>";
    }
} else {
    header("Location: login.php");
    exit();
}
?>