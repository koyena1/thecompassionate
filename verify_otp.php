<?php
// verify_otp.php
session_start();
require_once 'config/db.php';

$error = "";
$success = "";
$email = "";

// 1. Get Email from URL (Auto-filled from Register page)
if (isset($_GET['email'])) {
    $email = urldecode($_GET['email']);
}

// 2. Process OTP Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $entered_otp = trim($_POST['otp']);

    // Check DB for this Email and Token (OTP)
    $stmt = $conn->prepare("SELECT patient_id, full_name, email, token FROM patients WHERE email = ? AND token = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $entered_otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 3. Mark Verified & Remove OTP
        $update = $conn->prepare("UPDATE patients SET is_verified = 1, token = NULL WHERE patient_id = ?");
        $update->bind_param("i", $user['patient_id']);
        
        if ($update->execute()) {
            // 4. Auto Login
            $_SESSION['user_id'] = $user['patient_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'patient';
            $_SESSION['full_name'] = $user['full_name'];

            // 5. Redirect to Dashboard
            echo "<script>
                alert('Account verified successfully!');
                window.location.href = 'patient/dashboard.php';
            </script>";
            exit();
        } else {
            $error = "System Error: Could not verify account.";
        }
    } else {
        $error = "Invalid OTP. Please check your email and try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { 
            height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            background-color: #589167; 
        }
        
        .verify-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        h2 { color: #589167; margin-bottom: 10px; }
        p { color: #666; margin-bottom: 25px; line-height: 1.5; }
        
        .otp-input {
            width: 100%;
            padding: 15px;
            font-size: 1.5rem;
            letter-spacing: 12px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 10px;
            margin-bottom: 20px;
            outline: none;
        }
        .otp-input:focus { border-color: #589167; }
        
        .btn-verify {
            width: 100%;
            padding: 15px;
            border-radius: 30px;
            background: #589167;
            border: none;
            color: white;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-verify:hover { background: #467553; }
        
        .error-msg { 
            background: #ffebee; 
            color: #c62828; 
            padding: 10px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-size: 0.9rem; 
        }
    </style>
</head>
<body>

    <div class="verify-card">
        <h2>Verify Account</h2>
        <p>We've sent a 6-digit code to<br><strong><?php echo htmlspecialchars($email); ?></strong></p>

        <?php if(!empty($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            
            <input type="text" name="otp" class="otp-input" placeholder="000000" maxlength="6" required autofocus autocomplete="off">
            
            <button type="submit" class="btn-verify">Verify & Login</button>
        </form>
        
        <p style="margin-top: 20px; font-size: 0.85rem;">
            <a href="register.php" style="color: #666; text-decoration: none;">Incorrect Email? Register Again</a>
        </p>
    </div>

</body>
</html>