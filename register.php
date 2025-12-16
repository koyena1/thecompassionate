<?php
// register.php
session_start();

// --- CONFIGURATION ---
// 1. Enter your Gmail Address here:
$my_gmail_username = 'singhasoma644@gmail.com';
// 2. Enter your 16-digit App Password here (keep the quotes):
// If you leave this blank or wrong, the system will show the OTP in a popup instead.
$my_gmail_password = 'xxxx xxxx xxxx xxxx'; 

// --- ERROR REPORTING ---
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

// --- DB CONNECTION ---
if (!file_exists('config/db.php')) { die("Error: config/db.php missing."); }
include 'config/db.php'; 

// --- PHPMAILER ---
if (!file_exists('PHPMailer/src/Exception.php')) { die("Error: PHPMailer folder is missing."); }
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";
$name_val = "";
$email_val = "";
$whatsapp_val = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_val = trim($_POST['name']);
    $email_val = trim($_POST['email']);
    $whatsapp_val = $_POST['whatsapp'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    try {
        // 1. VALIDATION
        $whatsapp_clean = preg_replace('/[^0-9]/', '', $whatsapp_val);
        if (strlen($whatsapp_clean) > 10 && substr($whatsapp_clean, 0, 2) == '91') $whatsapp_clean = substr($whatsapp_clean, 2);
        if (strlen($whatsapp_clean) !== 10) throw new Exception("Invalid 10-digit mobile number.");
        if (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) throw new Exception("Invalid email format.");
        if ($password !== $confirm_password) throw new Exception("Passwords do not match!");

        // 2. DUPLICATE CHECK
        $checkStmt = $conn->prepare("SELECT patient_id FROM patients WHERE email = ?");
        $checkStmt->bind_param("s", $email_val);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) throw new Exception("Email address is already registered!");
        $checkStmt->close();

        // 3. GENERATE OTP & INSERT
        $otp = rand(100000, 999999);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO patients (full_name, email, phone_number, password_hash, token, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sssss", $name_val, $email_val, $whatsapp_clean, $hashed_password, $otp);

        if ($stmt->execute()) {
            
            // --- 4. SEND EMAIL (Smart Mode) ---
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; 
                $mail->SMTPAuth   = true;
                $mail->Username   = $my_gmail_username;
                $mail->Password   = $my_gmail_password; 
                $mail->SMTPSecure = 'ssl'; 
                $mail->Port       = 465;

                // Sender Info
                $mail->setFrom($my_gmail_username, 'Safe Space Admin');
                $mail->addAddress($email_val, $name_val);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Verify Your Account - SafeSpace';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                        <div style='max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; text-align: center;'>
                            <h2 style='color: #589167;'>Verify Your Email</h2>
                            <p>Welcome, $name_val!</p>
                            <p>Your verification code is:</p>
                            <h1 style='color: #333; letter-spacing: 5px; font-size: 32px; margin: 20px 0;'>$otp</h1>
                            <p style='font-size: 12px; color: #888;'>If you did not request this, please ignore this email.</p>
                        </div>
                    </div>
                ";

                $mail->send();

                // SUCCESS: Email Sent
                echo "<script>
                    alert('Registration Successful! An OTP has been sent to your email.');
                    window.location.href = 'verify_otp.php?email=" . urlencode($email_val) . "';
                </script>";

            } catch (Exception $e) {
                // FAILURE: Email Crashed (Password wrong/missing)
                // Fallback to showing OTP on screen so user isn't stuck
                echo "<script>
                    alert('Registration Saved! \\n\\n(Note: Email could not be sent. Check your App Password.)\\n\\nYour OTP is: " . $otp . "');
                    window.location.href = 'verify_otp.php?email=" . urlencode($email_val) . "';
                </script>";
            }
            exit();

        } else {
            throw new Exception("Database Error: " . $stmt->error);
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SafeSpace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { height: 100vh; display: flex; overflow: hidden; }
        .left-panel { flex: 1; background-color: #ffffff; display: flex; align-items: flex-end; justify-content: center; }
        .right-panel { flex: 1; background-color: #589167; display: flex; flex-direction: column; justify-content: center; padding: 0 100px; color: white; }
        .login-content { width: 100%; max-width: 450px; margin: 0 auto; }
        h2 { font-size: 2rem; margin-bottom: 5px; font-weight: 600; }
        .input-group { margin-bottom: 15px; } 
        .input-group label { display: block; margin-bottom: 5px; font-size: 0.85rem; margin-left: 15px; }
        input { width: 100%; padding: 12px 20px; border-radius: 30px; border: none; outline: none; color: #333; }
        .btn-login { width: 100%; padding: 15px; border-radius: 30px; background: transparent; border: 1px solid white; color: white; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-login:hover { background: white; color: #589167; }
        .alert-msg { padding: 15px; border-radius: 10px; margin-bottom: 20px; background: #ffebee; color: #c62828; }
        .register-link { text-align: center; margin-top: 20px; font-size: 0.8rem; }
        .register-link a { color: white; font-weight: bold; }
        @media (max-width: 768px) { .left-panel { display: none; } .right-panel { padding: 20px; } }
    </style>
</head>
<body>
    <div class="left-panel">
        <img src="https://png.pngtree.com/png-vector/20230928/ourmid/pngtree-young-afro-professional-doctor-png-image_10148632.png" style="max-width:80%;">
    </div>
    <div class="right-panel">
        <div class="login-content">
            <h2>Create Account</h2>
            <p style="margin-bottom: 20px; opacity: 0.8;">Join our Medical Community</p>

            <?php if(!empty($error)): ?>
                <div class="alert-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-group"><label>Full Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($name_val); ?>" required></div>
                <div class="input-group"><label>Email Address</label><input type="email" name="email" value="<?php echo htmlspecialchars($email_val); ?>" required></div>
                <div class="input-group"><label>WhatsApp Number</label><input type="tel" name="whatsapp" value="<?php echo htmlspecialchars($whatsapp_val); ?>" required></div>
                <div class="input-group"><label>Password</label><input type="password" name="password" required></div>
                <div class="input-group"><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
                <button type="submit" class="btn-login">Register</button>
                <div class="register-link">Already have an account? <a href="login.php">Login</a></div>
            </form>
        </div>
    </div>
</body>
</html>