<?php
// reset_password.php
session_start();
include 'config/db.php';

$error = "";
$success = "";
$valid_token = false;
$token = "";

// Check if token is provided in URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token and check if it's not expired (1 hour validity)
    $stmt = $conn->prepare("SELECT patient_id, full_name, email, token_created_at FROM patients WHERE token = ? AND is_verified = 1 LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if token is still valid (1 hour = 3600 seconds)
        $token_created = strtotime($user['token_created_at']);
        $current_time = time();
        $time_diff = $current_time - $token_created;
        
        if ($time_diff <= 3600) { // 1 hour validity
            $valid_token = true;
        } else {
            $error = "This password reset link has expired. Please request a new one.";
        }
    } else {
        $error = "Invalid or already used password reset link.";
    }
    $stmt->close();
}

// Handle password reset form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['token'])) {
    $token = $_POST['token'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Verify token again before updating
        $stmt = $conn->prepare("SELECT patient_id, token_created_at FROM patients WHERE token = ? AND is_verified = 1 LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check token validity
            $token_created = strtotime($user['token_created_at']);
            $current_time = time();
            $time_diff = $current_time - $token_created;
            
            if ($time_diff <= 3600) {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password and clear token
                $update = $conn->prepare("UPDATE patients SET password_hash = ?, token = NULL, token_created_at = NULL WHERE patient_id = ?");
                $update->bind_param("si", $hashed_password, $user['patient_id']);
                
                if ($update->execute()) {
                    $_SESSION['success_msg'] = "Password reset successful! You can now login with your new password.";
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Database error. Please try again.";
                }
                $update->close();
            } else {
                $error = "This password reset link has expired. Please request a new one.";
            }
        } else {
            $error = "Invalid password reset link.";
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
    <title>Reset Password</title>
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
        input[type="password"] { width: 100%; padding: 15px 20px; padding-right: 45px; border-radius: 30px; border: none; outline: none; font-size: 1rem; color: #333; }
        .toggle-password { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: #aaa; cursor: pointer; }
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
            .right-panel { clip-path: none; padding: 40px 20px; height: auto; min-height: 100vh; }
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
            <h2>Reset Password</h2>
            <p class="subtitle">Enter your new password</p>

            <?php if(!empty($success)): ?>
                <div class="success-msg"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if(!empty($error)): ?>
                <div class="error-msg"><?php echo $error; ?></div>
                <div class="back-link">
                    <a href="forgot_password.php">Request New Reset Link</a>
                </div>
            <?php endif; ?>

            <?php if($valid_token): ?>
            <form action="" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="input-group">
                    <label>New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" placeholder="Enter New Password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label>Confirm New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Reset Password</button>

                <div class="back-link">
                    Remember your password? <a href="login.php">Login</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>
