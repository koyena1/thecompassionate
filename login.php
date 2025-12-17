<?php
// login.php
session_start();

// --- DATABASE CONNECTION ---
require_once 'config/db.php';
// ---------------------------

$error = "";
$success = "";

// Check for success message coming from verify.php
if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. FIRST CHECK: Is the user an ADMIN?
    $sql_admin = "SELECT admin_id, full_name, email, password_hash FROM admin_users WHERE email = ?";
    $stmt = $conn->prepare($sql_admin);
    
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result_admin = $stmt->get_result();

        if ($result_admin->num_rows === 1) {
            // --- ADMIN FOUND ---
            $user = $result_admin->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                // Admin Login Success
                $_SESSION['user_id'] = $user['admin_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = 'admin';
                $_SESSION['full_name'] = $user['full_name'];

                header("Location: admin/dashboard.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            // 2. SECOND CHECK: If not Admin, is the user a PATIENT?
            $stmt->close(); 

            // Include 'is_verified' in the select
            $sql_patient = "SELECT patient_id, full_name, email, password_hash, is_verified FROM patients WHERE email = ?";
            $stmt = $conn->prepare($sql_patient);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result_patient = $stmt->get_result();

            if ($result_patient->num_rows === 1) {
                // --- PATIENT FOUND ---
                $patient = $result_patient->fetch_assoc();

                if (password_verify($password, $patient['password_hash'])) {
                    
                    // --- CHECK IF EMAIL IS VERIFIED ---
                    if ($patient['is_verified'] == 1) {
                        // Patient Login Success
                        $_SESSION['user_id'] = $patient['patient_id'];
                        $_SESSION['email'] = $patient['email'];
                        $_SESSION['role'] = 'patient';
                        $_SESSION['full_name'] = $patient['full_name'];

                        header("Location: patient/dashboard.php");
                        exit();
                    } else {
                        $error = "Please verify your email address before logging in.";
                    }

                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "No account found with that email.";
            }
        }
        if(isset($stmt) && $stmt) $stmt->close();
    } else {
        $error = "Database error.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Login Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Reusing your exact CSS */
        /* RESET & BASICS */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { height: 100vh; display: flex; overflow: hidden; }

        /* LEFT PANEL */
        .left-panel { flex: 1; background-color: #ffffff; position: relative; display: flex; align-items: flex-end; justify-content: center; }
        .decoration-star { position: absolute; color: #589167; font-size: 24px; animation: twinkle 2s infinite ease-in-out; }
        .star-1 { top: 10%; left: 10%; font-size: 30px; }
        .star-2 { top: 20%; right: 20%; }
        .heartbeat-line { position: absolute; top: 50%; left: 0; width: 100%; opacity: 0.1; z-index: 1; }
        .doctor-img { max-width: 80%; height: auto; z-index: 2; filter: drop-shadow(0px 10px 15px rgba(0,0,0,0.1)); }

        /* RIGHT PANEL */
        .right-panel { flex: 1; background-color: #589167; display: flex; flex-direction: column; justify-content: center; padding: 0 100px; color: white; position: relative; clip-path: polygon(15% 0, 100% 0, 100% 100%, 0% 100%); }
        .white-star { color: white; opacity: 0.8; position: absolute; }
        .ws-1 { top: 20%; right: 10%; font-size: 40px; }
        .ws-2 { bottom: 30%; left: 15%; font-size: 30px; }

        /* FORM STYLING */
        .login-content { width: 100%; max-width: 400px; margin-left: auto; margin-right: 50px; }
        h2 { font-size: 2rem; margin-bottom: 10px; font-weight: 600; }
        .subtitle { margin-bottom: 40px; font-size: 0.9rem; opacity: 0.8; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 0.85rem; margin-left: 15px; }
        .input-wrapper { position: relative; }
        input[type="email"], input[type="password"] { width: 100%; padding: 15px 20px; border-radius: 30px; border: none; outline: none; font-size: 1rem; color: #333; }
        .toggle-password { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: #aaa; cursor: pointer; }
        .form-options { display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; margin-bottom: 30px; margin-top: 10px; }
        .form-options a { color: white; text-decoration: none; opacity: 0.9; }
        .btn-login { width: 100%; padding: 15px; border-radius: 30px; background: transparent; border: 1px solid white; color: white; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; }
        .btn-login:hover { background: white; color: #8E3E8C; }
        .register-link { text-align: center; margin-top: 20px; font-size: 0.8rem; }
        .register-link a { color: white; font-weight: bold; text-decoration: underline; }
        
        .error-msg { background: rgba(255,0,0,0.2); padding: 10px; border-radius: 10px; margin-bottom: 15px; font-size: 0.9rem; text-align: center; }
        /* Added class for Success Message matching error design but green */
        .success-msg { background: rgba(0, 255, 0, 0.2); padding: 10px; border-radius: 10px; margin-bottom: 15px; font-size: 0.9rem; text-align: center; }

        /* ANIMATIONS */
        @keyframes twinkle { 0% { opacity: 0.5; transform: scale(1); } 50% { opacity: 1; transform: scale(1.2); } 100% { opacity: 0.5; transform: scale(1); } }

        /* RESPONSIVE */
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
        <svg class="heartbeat-line" viewBox="0 0 500 150" fill="none" stroke="#8E3E8C" stroke-width="2">
             <path d="M0,75 L150,75 L170,20 L190,130 L210,75 L500,75" />
        </svg>
        <img src="https://png.pngtree.com/png-vector/20230928/ourmid/pngtree-young-afro-professional-doctor-png-image_10148632.png" alt="Doctor" class="doctor-img">
    </div>

    <div class="right-panel">
        <i class="fas fa-star white-star ws-1"></i>
        <i class="fas fa-star white-star ws-2"></i>

        <div class="login-content">
            <h2>Welcome Back !</h2>
            <p class="subtitle">Login Your Account</p>

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

                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" placeholder="●●●●●●●●" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                    </div>
                </div>

                <div class="form-options">
                    <label><input type="checkbox"> Remember Me</label>
                    <a href="#">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login">Login</button>

                <div class="register-link">
                    Don't have an account? <a href="register.php">Register</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            var passwordField = document.getElementById("password");
            var icon = document.querySelector(".toggle-password");
            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordField.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>