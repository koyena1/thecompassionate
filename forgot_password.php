<?php
// forgot_password.php
session_start();
include 'config/db.php';
require 'mailer.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string(trim($_POST['email']));
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email exists and account is verified
        $stmt = $conn->prepare("SELECT patient_id, full_name, is_verified FROM patients WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if email is verified
            if ($user['is_verified'] == 0) {
                $error = "Please verify your email address first before resetting password.";
            } else {
                // Generate reset token
                $token = bin2hex(random_bytes(50));
                $token_created = date('Y-m-d H:i:s');
                
                // Update user with reset token
                $update = $conn->prepare("UPDATE patients SET token = ?, token_created_at = ? WHERE patient_id = ?");
                $update->bind_param("ssi", $token, $token_created, $user['patient_id']);
                
                if ($update->execute()) {
                    // Send password reset email
                    if (sendPasswordResetEmail($email, $token, $user['full_name'])) {
                        $success = "Password reset link has been sent to your email address.";
                    } else {
                        $error = "Failed to send reset email. Please try again later.";
                    }
                } else {
                    $error = "Database error. Please try again.";
                }
            }
        } else {
            // For security, show success message even if email doesn't exist
            $success = "If an account exists with this email, a password reset link has been sent.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { height: 100vh; display: flex; overflow: hidden; }
        .left-panel { flex: 1; background-color: #ffffff; position: relative; display: flex; align-items: flex-end; justify-content: center; }
        .decoration-star { position: absolute; color: #589167; font-size: 24px; animation: twinkle 2s infinite ease-in-out; }
        .star-1 { top: 10%; left: 10%; font-size: 30px; }
        .star-2 { top: 20%; right: 20%; }
        .heartbeat-line { position: absolute; top: 50%; left: 0; width: 100%; opacity: 0.1; z-index: 1; }
        .doctor-img { max-width: 80%; height: auto; z-index: 2; filter: drop-shadow(0px 10px 15px rgba(0,0,0,0.1)); }
        .right-panel { flex: 1; background-color: #589167; display: flex; flex-direction: column; justify-content: center; padding: 0 100px; color: white; position: relative; clip-path: polygon(15% 0, 100% 0, 100% 100%, 0% 100%); }
        .white-star { color: white; opacity: 0.8; position: absolute; }
        .ws-1 { top: 20%; right: 10%; font-size: 40px; }
        .ws-2 { bottom: 30%; left: 15%; font-size: 30px; }
        .login-content { width: 100%; max-width: 400px; margin-left: auto; margin-right: 50px; }
        h2 { font-size: 2rem; margin-bottom: 10px; font-weight: 600; }
        .subtitle { margin-bottom: 40px; font-size: 0.9rem; opacity: 0.8; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.85rem; margin-left: 15px; }
        .input-wrapper { position: relative; }
        input[type="email"] { width: 100%; padding: 15px 20px; border-radius: 30px; border: none; outline: none; font-size: 1rem; color: #333; }
        .btn-submit { width: 100%; padding: 15px; border-radius: 30px; background: transparent; border: 1px solid white; color: white; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; margin-top: 10px; }
        .btn-submit:hover { background: white; color: #589167; }
        .back-link { text-align: center; margin-top: 20px; font-size: 0.8rem; }
        .back-link a { color: white; font-weight: bold; text-decoration: underline; }
        .error-msg { background: rgba(255,0,0,0.2); padding: 10px; border-radius: 10px; margin-bottom: 15px; font-size: 0.9rem; text-align: center; }
        .success-msg { background: rgba(0, 255, 0, 0.2); padding: 10px; border-radius: 10px; margin-bottom: 15px; font-size: 0.9rem; text-align: center; }
        @keyframes twinkle { 0% { opacity: 0.5; transform: scale(1); } 50% { opacity: 1; transform: scale(1.2); } 100% { opacity: 0.5; transform: scale(1); } }
        @media (max-width: 768px) {
            body { flex-direction: column; overflow-y: auto; }
            .left-panel { display: none; }
            .right-panel { clip-path: none; padding: 40px 20px; height: 100vh; }
            .login-content { margin: 0 auto; }
        }
    </style>
</head>
<body>

    <div class="left-panel">
        <i class="fas fa-star decoration-star star-1"></i>
        <i class="fas fa-star decoration-star star-2"></i>
        <svg class="heartbeat-line" viewBox="0 0 500 150" fill="none" stroke="#589167" stroke-width="2">
             <path d="M0,75 L150,75 L170,20 L190,130 L210,75 L500,75" />
        </svg>
        <img src="https://png.pngtree.com/png-vector/20230928/ourmid/pngtree-young-afro-professional-doctor-png-image_10148632.png" alt="Doctor" class="doctor-img">
    </div>

    <div class="right-panel">
        <i class="fas fa-star white-star ws-1"></i>
        <i class="fas fa-star white-star ws-2"></i>

        <div class="login-content">
            <h2>Forgot Password?</h2>
            <p class="subtitle">Enter your email to reset your password</p>

            <?php if(!empty($success)): ?>
                <div class="success-msg"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if(!empty($error)): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" placeholder="Enter Your Email" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Send Reset Link</button>

                <div class="back-link">
                    Remember your password? <a href="login.php">Login</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
