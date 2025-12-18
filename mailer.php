<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

// Common PHPMailer configuration function
function getConfiguredMailer() {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings for Gmail SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'thecompassionatespace49@gmail.com';
        $mail->Password   = 'uogentsnujddlnuy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Disable debug output (set to 2 for troubleshooting)
        $mail->SMTPDebug = 0;
        
        return $mail;
    } catch (Exception $e) {
        error_log("PHPMailer Configuration Error: {$e->getMessage()}");
        return null;
    }
}

function sendVerificationEmail($email, $token, $userName = '') {
    $mail = getConfiguredMailer();
    if (!$mail) return false;

    try {
        // Recipients
        $mail->setFrom('thecompassionatespace49@gmail.com', 'Medical App');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email Address';
        $verificationLink = "http://localhost/psychiatrist/verify.php?token=" . urlencode($token);
        
        // Plain text version
        $mail->AltBody = "Hi $userName,\n\nPlease click the link below to verify your account:\n$verificationLink\n\nIf you did not create this account, please ignore this email.";
        
        // HTML version
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                <div style='max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px;'>
                    <h2 style='color: #589167;'>Email Verification</h2>
                    <p>Hi " . htmlspecialchars($userName) . ",</p>
                    <p>Please click the button below to verify your account:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$verificationLink' style='background: #589167; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email</a>
                    </p>
                    <p style='color: #666; font-size: 12px;'>Or copy and paste this link: <br>$verificationLink</p>
                    <p style='color: #666; font-size: 12px;'>If you did not create this account, please ignore this email.</p>
                </div>
            </body>
            </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Verification Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendPasswordResetEmail($email, $token, $userName = '') {
    $mail = getConfiguredMailer();
    if (!$mail) return false;

    try {
        // Recipients
        $mail->setFrom('thecompassionatespace49@gmail.com', 'Medical App');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $resetLink = "http://localhost/psychiatrist/reset_password.php?token=" . urlencode($token);
        
        // Plain text version
        $mail->AltBody = "Hi $userName,\n\nYou requested to reset your password. Click the link below:\n$resetLink\n\nThis link is valid for 1 hour.\n\nIf you did not request this, please ignore this email.";
        
        // HTML version
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                <div style='max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px;'>
                    <h2 style='color: #589167;'>Password Reset Request</h2>
                    <p>Hi " . htmlspecialchars($userName) . ",</p>
                    <p>You requested to reset your password. Click the button below to proceed:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$resetLink' style='background: #589167; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                    </p>
                    <p style='color: #666; font-size: 12px;'>Or copy and paste this link: <br>$resetLink</p>
                    <p style='color: #ff6b6b; font-size: 12px;'><strong>This link is valid for 1 hour.</strong></p>
                    <p style='color: #666; font-size: 12px;'>If you did not request this password reset, please ignore this email and your password will remain unchanged.</p>
                </div>
            </body>
            </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Password Reset Email Error: {$mail->ErrorInfo}");
        return false;
    }
}