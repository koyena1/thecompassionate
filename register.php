<?php
// register.php
session_start();

// --- 1. CLEAN ERROR REPORTING ---
// This hides the "Deprecated" warning but shows "Fatal" errors
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

// --- 2. CHECK DB ---
if (!file_exists('config/db.php')) {
    die("<div style='color:red; text-align:center; padding:20px;'>Error: config/db.php missing.</div>");
}
include 'config/db.php'; 

// --- 3. CHECK PHPMAILER ---
if (!file_exists('PHPMailer/src/Exception.php')) {
    die("<div style='color:red; text-align:center; padding:20px;'>Error: PHPMailer folder is missing.</div>");
}

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";
$success = "";
$name_val = "";
$email_val = "";
$whatsapp_val = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_val = $_POST['name'];
    $email_val = $_POST['email'];
    $whatsapp_val = $_POST['whatsapp'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $raw_whatsapp = $_POST['whatsapp'];

    try {
        // --- VALIDATION ---
        $whatsapp_clean = preg_replace('/[^0-9]/', '', $raw_whatsapp);
        if (strlen($whatsapp_clean) > 10 && substr($whatsapp_clean, 0, 2) == '91') $whatsapp_clean = substr($whatsapp_clean, 2);

        if (strlen($whatsapp_clean) !== 10) throw new Exception("Invalid 10-digit mobile number.");
        if (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) throw new Exception("Invalid email format.");
        if ($password !== $confirm_password) throw new Exception("Passwords do not match!");

        // --- DB CHECK ---
        $checkQuery = "SELECT * FROM patients WHERE email = '$email_val'";
        $result = $conn->query($checkQuery);
        if ($result->num_rows > 0) throw new Exception("Email address is already registered!");

        // --- INSERT USER ---
        $otp = rand(100000, 999999);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $token = $otp; 

        $sql = "INSERT INTO patients (full_name, email, phone_number, password_hash, token, is_verified) 
                VALUES ('$name_val', '$email_val', '$whatsapp_clean', '$hashed_password', '$token', 0)";

        if ($conn->query($sql) === TRUE) {
            
            // --- SEND EMAIL ---
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            
            // ========================================================
            // !!! PASTE YOUR 16-DIGIT APP PASSWORD HERE !!!
            // ========================================================
            $mail->Username   = 'singhasoma644@gmail.com'; 
            $mail->Password   = 'xxxx xxxx xxxx xxxx'; // <-- REMOVE SPACES IF YOU WANT, BUT PASTE THE REAL CODE HERE
            // ========================================================

            $mail->SMTPSecure = 'ssl'; 
            $mail->Port       = 465;

            // Fix for Localhost Certificate Issues
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->setFrom('singhasoma644@gmail.com', 'Safe Space Admin'); // Sender must match Username
            $mail->addAddress($email_val, $name_val);

            $mail->isHTML(true);
            $mail->Subject = 'Complete Your Registration';
            
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $path = dirname($_SERVER['PHP_SELF']);
            $encoded_email = urlencode($email_val);
            $verification_link = "$protocol://$host$path/verify_otp.php?email=$encoded_email";

            $mail->Body = "
                <div style='background-color: #f9f9f9; padding: 20px; font-family: sans-serif;'>
                    <div style='max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; text-align: center;'>
                        <h2 style='color: #589167;'>Welcome, $name_val!</h2>
                        <p>Your One-Time Password (OTP) is:</p>
                        <h1 style='color: #333; letter-spacing: 5px;'>$otp</h1>
                        <a href='$verification_link' style='display: inline-block; background-color: #589167; color: white; padding: 12px 25px; text-decoration: none; border-radius: 50px; font-weight: bold;'>Verify Account</a>
                    </div>
                </div>
            ";

            $mail->send();
            $success = "Registration successful! A verification link has been sent to <strong>$email_val</strong>. Please check your email to login.";

        } else {
            throw new Exception("Database Error: " . $conn->error);
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
        // If "Authentication failed", simplify message for user
        if (strpos($error, 'Authentication failed') !== false) {
            $error = "Authentication failed. Please check your Gmail App Password in the code.";
        }
        if(isset($email_val)) $conn->query("DELETE FROM patients WHERE email='$email_val'");
    } catch (\Throwable $e) {
        $error = "Critical Error: " . $e->getMessage();
        if(isset($email_val)) $conn->query("DELETE FROM patients WHERE email='$email_val'");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS */
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
        .ws-1 { top: 15%; right: 10%; font-size: 40px; }
        .ws-2 { bottom: 10%; left: 15%; font-size: 30px; }

        .login-content { width: 100%; max-width: 450px; margin-left: auto; margin-right: 50px; }
        h2 { font-size: 2rem; margin-bottom: 5px; font-weight: 600; }
        .subtitle { margin-bottom: 20px; font-size: 0.9rem; opacity: 0.8; }
        
        .input-group { margin-bottom: 15px; } 
        .input-group label { display: block; margin-bottom: 5px; font-size: 0.85rem; margin-left: 15px; }
        .input-wrapper { position: relative; }
        
        input { width: 100%; padding: 12px 20px; border-radius: 30px; border: none; outline: none; font-size: 0.95rem; color: #333; }
        input[type="password"] { padding-right: 45px; }
        
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #8E3E8C; font-size: 1rem; z-index: 10; }
        .btn-login { width: 100%; padding: 15px; border-radius: 30px; background: transparent; border: 1px solid white; color: white; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; margin-top: 10px; }
        .btn-login:hover { background: white; color: #8E3E8C; }
        .register-link { text-align: center; margin-top: 20px; font-size: 0.8rem; }
        .register-link a { color: white; font-weight: bold; text-decoration: underline; }

        .alert-msg { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; line-height: 1.5; text-align: left; }
        .error { background: #ffebee; color: #c62828; }
        .success { background-color: #4CAF50; color: white; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }

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
        <svg class="heartbeat-line" viewBox="0 0 500 150" fill="none" stroke="#8E3E8C" stroke-width="2">
             <path d="M0,75 L150,75 L170,20 L190,130 L210,75 L500,75" />
        </svg>
        <img src="https://png.pngtree.com/png-vector/20230928/ourmid/pngtree-young-afro-professional-doctor-png-image_10148632.png" alt="Doctor" class="doctor-img">
    </div>

    <div class="right-panel">
        <div class="login-content">
            <h2>New Account</h2>
            <p class="subtitle">Join our Medical Community</p>

            <?php if(!empty($success)): ?>
                <div class="alert-msg success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if(!empty($error)): ?>
                <div class="alert-msg error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-group">
                    <label>Full Name</label>
                    <div class="input-wrapper">
                        <input type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars($name_val); ?>" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email_val); ?>" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>WhatsApp Number</label>
                    <div class="input-wrapper">
                        <input type="tel" name="whatsapp" placeholder="Number" value="<?php echo htmlspecialchars($whatsapp_val); ?>" required>
                    </div>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" placeholder="Password" required>
                        <i class="fa fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                    </div>
                </div>
                <div class="input-group">
                    <label>Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm" required>
                        <i class="fa fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                </div>
                <button type="submit" class="btn-login">Register</button>
                <div class="register-link">Already have an account? <a href="login.php">Login</a></div>
            </form>
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