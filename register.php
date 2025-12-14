<?php
// register.php
session_start(); // Start session at the very top
include 'config/db.php'; 

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve inputs
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $whatsapp = $conn->real_escape_string($_POST['whatsapp']); 
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if email already exists
        $checkQuery = "SELECT * FROM patients WHERE email = '$email'";
        $result = $conn->query($checkQuery);

        if ($result->num_rows > 0) {
            $error = "Email address is already registered!";
        } else {
            // Hash the password for security
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert into 'patients' table
            $sql = "INSERT INTO patients (full_name, email, phone_number, password_hash) 
                    VALUES ('$name', '$email', '$whatsapp', '$hashed_password')";

            if ($conn->query($sql) === TRUE) {
                // --- AUTO LOGIN LOGIC START ---
                $new_user_id = $conn->insert_id; // Get the ID of the new user
                
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'patient';
                $_SESSION['full_name'] = $name;

                // Redirect directly to dashboard
                header("Location: patient/dashboard.php");
                exit();
                // --- AUTO LOGIN LOGIC END ---
            } else {
                $error = "Error: " . $conn->error;
            }
        }
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
        /* Reusing the exact same CSS for consistency */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            overflow: hidden; 
        }

        /* LEFT PANEL */
        .left-panel {
            flex: 1;
            background-color: #ffffff;
            position: relative;
            display: flex;
            align-items: flex-end; 
            justify-content: center;
        }

        .decoration-star {
            position: absolute;
            color: #8E3E8C;
            font-size: 24px;
            animation: twinkle 2s infinite ease-in-out;
        }
        
        .star-1 { top: 10%; left: 10%; font-size: 30px; }
        .star-2 { top: 20%; right: 20%; }
        
        .heartbeat-line {
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            opacity: 0.1;
            z-index: 1;
        }

        .doctor-img {
            max-width: 80%;
            height: auto;
            z-index: 2;
            filter: drop-shadow(0px 10px 15px rgba(0,0,0,0.1));
        }

        /* RIGHT PANEL */
        .right-panel {
            flex: 1;
            background-color: #8E3E8C; 
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 100px;
            color: white;
            position: relative;
            clip-path: polygon(15% 0, 100% 0, 100% 100%, 0% 100%);
        }

        .white-star {
            color: white;
            opacity: 0.8;
            position: absolute;
        }
        .ws-1 { top: 15%; right: 10%; font-size: 40px; }
        .ws-2 { bottom: 10%; left: 15%; font-size: 30px; }

        .login-content {
            width: 100%;
            max-width: 400px;
            margin-left: auto; 
            margin-right: 50px;
        }

        h2 { font-size: 2rem; margin-bottom: 5px; font-weight: 600; }
        .subtitle { margin-bottom: 30px; font-size: 0.9rem; opacity: 0.8; }

        .input-group { margin-bottom: 15px; } 
        
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.85rem;
            margin-left: 15px;
        }

        .input-wrapper { position: relative; }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 20px; 
            border-radius: 30px;
            border: none;
            outline: none;
            font-size: 0.95rem;
            color: #333;
        }

        /* Added padding to right so text doesn't hit the eye icon */
        input[type="password"] {
            padding-right: 45px; 
        }

        /* NEW CSS FOR EYE ICON */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #8E3E8C; /* Using theme color for the icon */
            font-size: 1rem;
            z-index: 10;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            border-radius: 30px;
            background: transparent;
            border: 1px solid white;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover { background: white; color: #8E3E8C; }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
        }

        .register-link a {
            color: white;
            font-weight: bold;
            text-decoration: underline;
        }

        .alert-msg {
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            text-align: center;
        }
        .error { background: rgba(255,0,0,0.2); }
        .success { background: rgba(0, 255, 0, 0.2); }

        @keyframes twinkle {
            0% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
            100% { opacity: 0.5; transform: scale(1); }
        }

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
        <i class="fas fa-star white-star ws-1"></i>
        <i class="fas fa-star white-star ws-2"></i>

        <div class="login-content">
            <h2>New Account</h2>
            <p class="subtitle">Join our Medical Community</p>

            <?php if(!empty($error)): ?>
                <div class="alert-msg error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if(!empty($success)): ?>
                <div class="alert-msg success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                
                <div class="input-group">
                    <label>Full Name</label>
                    <div class="input-wrapper">
                        <input type="text" name="name" placeholder="Dr. John Doe" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" placeholder="dr@gmail.com" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>WhatsApp Number</label>
                    <div class="input-wrapper">
                        <input type="text" name="whatsapp" placeholder="+91 1234567890" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" placeholder="Create Password" required>
                        <i class="fa fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                    </div>
                </div>

                <div class="input-group">
                    <label>Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                        <i class="fa fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn-login">Register</button>

                <div class="register-link">
                    Already have an account? <a href="login.php">Login</a>
                </div>

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