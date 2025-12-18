# Email Verification & Password Reset Setup Guide

## Overview
Your psychiatrist application now has complete email verification and password reset functionality using PHPMailer with Gmail SMTP.

## ✅ What Has Been Fixed

### 1. **Centralized Email Configuration (mailer.php)**
- Created a reusable `getConfiguredMailer()` function
- All email functions now use the same SMTP settings
- Your Gmail credentials: `thecompassionatespace49@gmail.com`
- App Password: `uogentsnujddlnuy`

### 2. **Email Functions**
- `sendVerificationEmail($email, $token, $userName)` - Sends verification email on registration
- `sendPasswordResetEmail($email, $token, $userName)` - Sends password reset email

### 3. **New Files Created**
- **forgot_password.php** - Users can request password reset link
- **reset_password.php** - Users can set new password with valid token
- **database_update.sql** - SQL script to ensure database schema is correct

### 4. **Updated Files**
- **register.php** - Now uses centralized mailer function
- **login.php** - "Forgot Password?" link now points to forgot_password.php
- **verify.php** - Already working correctly
- **mailer.php** - Refactored with better error handling and styling

## 🔧 Setup Instructions

### Step 1: Update Database Schema
Run the SQL script to ensure your database has all required columns:

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select `safe_space_db` database
3. Click "SQL" tab
4. Copy and paste contents from `database_update.sql`
5. Click "Go" to execute

**Required columns in `patients` table:**
- `token` (VARCHAR 255, NULL) - Stores verification/reset tokens
- `is_verified` (TINYINT, DEFAULT 0) - Email verification status
- `token_created_at` (DATETIME, NULL) - Token creation timestamp for expiry

### Step 2: Verify Gmail Settings
Your Gmail App Password is already configured, but ensure:

1. ✅ 2-Factor Authentication is enabled on your Gmail account
2. ✅ App Password `uogentsnujddlnuy` is active
3. ✅ Gmail account `thecompassionatespace49@gmail.com` can send emails

**To test/regenerate App Password:**
- Visit: https://myaccount.google.com/apppasswords
- Generate new App Password if current one doesn't work
- Update password in [mailer.php](mailer.php#L11)

### Step 3: Test the Complete Flow

#### A. Registration & Email Verification
1. Go to: `http://localhost/psychiatrist/register.php`
2. Fill in registration form
3. Submit form
4. Check email inbox for verification link
5. Click verification link
6. Verify you're redirected to login page with success message

#### B. Password Reset
1. Go to: `http://localhost/psychiatrist/login.php`
2. Click "Forgot Password?" link
3. Enter your email address
4. Check email for password reset link
5. Click reset link (valid for 1 hour)
6. Enter new password
7. Login with new password

### Step 4: Debugging (if emails not sending)

**Enable Debug Mode:**
Edit [mailer.php](mailer.php#L17) and change:
```php
$mail->SMTPDebug = 0;  // Change to 2 for debugging
```

**Check SMTP Debug Log:**
- Debug output will be displayed on screen when SMTPDebug = 2
- Common issues:
  - Incorrect App Password
  - Gmail blocking access (check Gmail security settings)
  - Firewall blocking port 587

**Test PHPMailer Installation:**
```php
<?php
require 'vendor/autoload.php';
echo "PHPMailer loaded successfully!";
?>
```

## 📧 Email Templates

Both emails now have professional HTML styling:
- Branded with your green color (#589167)
- Responsive design
- Clear call-to-action buttons
- Fallback plain text versions

## 🔒 Security Features

1. **Token-based verification** - 100-character random tokens
2. **Password reset expiry** - Links valid for 1 hour only
3. **Email verification required** - Users must verify before login
4. **Password hashing** - Using PHP's password_hash()
5. **Secure token cleanup** - Tokens removed after use

## 📁 File Structure

```
psychiatrist/
├── mailer.php              # Email configuration & functions
├── register.php            # User registration with verification
├── verify.php              # Email verification handler
├── login.php               # Login with forgot password link
├── forgot_password.php     # Request password reset
├── reset_password.php      # Reset password with token
├── database_update.sql     # Database schema update script
└── config/
    └── db.php              # Database connection
```

## 🎨 User Flow Diagram

```
Registration Flow:
register.php → (Email Sent) → verify.php → login.php

Password Reset Flow:
login.php → forgot_password.php → (Email Sent) → reset_password.php → login.php
```

## ⚙️ Configuration Options

### Change Email Sender Name
Edit [mailer.php](mailer.php#L27):
```php
$mail->setFrom('thecompassionatespace49@gmail.com', 'Your App Name');
```

### Change Token Expiry Time
Edit [reset_password.php](reset_password.php#L29) (currently 1 hour = 3600 seconds):
```php
if ($time_diff <= 3600) { // Change this value
```

### Disable Email Verification Requirement
Edit [login.php](login.php#L64) (not recommended):
```php
// Remove this check to allow login without verification
if ($patient['is_verified'] == 1) {
```

## 🐛 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Emails not sending | Enable SMTPDebug = 2 in mailer.php |
| "Invalid credentials" | Regenerate Gmail App Password |
| Token expired | Request new reset link |
| Database error | Run database_update.sql |
| Already verified error | User clicked verification link twice (safe to ignore) |

## 📝 Testing Checklist

- [ ] Database columns exist (run database_update.sql)
- [ ] Can register new account
- [ ] Verification email arrives in inbox
- [ ] Verification link works
- [ ] Cannot login before verification
- [ ] Can login after verification
- [ ] Can request password reset
- [ ] Reset email arrives
- [ ] Reset link works (within 1 hour)
- [ ] Expired reset link shows error
- [ ] Can login with new password

## 🔄 Next Steps (Optional Enhancements)

1. **Rate limiting** - Prevent email spam
2. **Resend verification email** - If user didn't receive it
3. **Email templates** - Move HTML to separate files
4. **AJAX forms** - Better user experience
5. **SMS verification** - Alternative to email
6. **Remember me functionality** - Currently not implemented

## 📞 Support

If you encounter any issues:
1. Check SMTP debug output
2. Verify database schema
3. Test Gmail credentials
4. Check PHP error logs in XAMPP

---

**All systems configured and ready to use!** 🚀
