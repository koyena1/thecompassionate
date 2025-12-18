<?php
/**
 * Database Schema Check Script
 * Run this file to verify your database is properly configured
 * Access: http://localhost/psychiatrist/check_database.php
 */

include 'config/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Schema Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #589167; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid green; margin: 10px 0; border-radius: 5px; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid red; margin: 10px 0; border-radius: 5px; }
        .warning { color: orange; padding: 10px; background: #fff3cd; border: 1px solid orange; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #589167; color: white; }
        .code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .info { background: #d1ecf1; padding: 10px; border-left: 4px solid #0c5460; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔍 Database Schema Check</h1>";

// Check database connection
if (!$conn) {
    echo "<div class='error'><strong>❌ Database Connection Failed!</strong><br>";
    echo "Error: " . mysqli_connect_error() . "</div>";
    exit();
}

echo "<div class='success'><strong>✅ Database Connected Successfully!</strong><br>";
echo "Database: <span class='code'>safe_space_db</span></div>";

// Check if patients table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'patients'");
if ($tableCheck->num_rows == 0) {
    echo "<div class='error'><strong>❌ Table 'patients' does not exist!</strong><br>";
    echo "Please create the patients table first.</div>";
    exit();
}

echo "<div class='success'><strong>✅ Table 'patients' exists</strong></div>";

// Check required columns
echo "<h2>📋 Column Verification</h2>";

$requiredColumns = [
    'patient_id' => ['type' => 'INT', 'required' => true],
    'full_name' => ['type' => 'VARCHAR', 'required' => true],
    'email' => ['type' => 'VARCHAR', 'required' => true],
    'phone_number' => ['type' => 'VARCHAR', 'required' => true],
    'password_hash' => ['type' => 'VARCHAR', 'required' => true],
    'token' => ['type' => 'VARCHAR', 'required' => true, 'nullable' => true],
    'is_verified' => ['type' => 'TINYINT', 'required' => true],
    'token_created_at' => ['type' => 'DATETIME', 'required' => true, 'nullable' => true]
];

$columnsQuery = $conn->query("DESCRIBE patients");
$existingColumns = [];
$issues = [];

echo "<table>";
echo "<tr><th>Column Name</th><th>Data Type</th><th>Null</th><th>Default</th><th>Status</th></tr>";

while ($col = $columnsQuery->fetch_assoc()) {
    $existingColumns[$col['Field']] = $col;
    $status = "✅";
    
    // Check if it's a required column for our features
    if (isset($requiredColumns[$col['Field']])) {
        if ($col['Field'] == 'token' && ($col['Null'] != 'YES' || $col['Type'] != 'varchar(255)')) {
            $status = "⚠️ Should be VARCHAR(255) NULL";
            $issues[] = "Column 'token' should be VARCHAR(255) and allow NULL";
        } elseif ($col['Field'] == 'is_verified' && $col['Type'] != 'tinyint(1)') {
            $status = "⚠️ Should be TINYINT(1)";
            $issues[] = "Column 'is_verified' should be TINYINT(1)";
        } elseif ($col['Field'] == 'token_created_at' && ($col['Null'] != 'YES' || !strpos($col['Type'], 'datetime'))) {
            $status = "⚠️ Should be DATETIME NULL";
            $issues[] = "Column 'token_created_at' should be DATETIME and allow NULL";
        }
    }
    
    echo "<tr>";
    echo "<td><strong>" . $col['Field'] . "</strong></td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";

// Check for missing columns
$missingColumns = [];
foreach ($requiredColumns as $colName => $colInfo) {
    if (!isset($existingColumns[$colName])) {
        $missingColumns[] = $colName;
    }
}

if (count($missingColumns) > 0) {
    echo "<div class='error'><strong>❌ Missing Required Columns:</strong><br>";
    foreach ($missingColumns as $col) {
        echo "• <span class='code'>$col</span><br>";
    }
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<strong>🔧 To fix this, run the following SQL:</strong><br>";
    echo "<pre>USE safe_space_db;\n\n";
    
    foreach ($missingColumns as $col) {
        if ($col == 'token') {
            echo "ALTER TABLE patients ADD COLUMN token VARCHAR(255) NULL DEFAULT NULL AFTER password_hash;\n";
        } elseif ($col == 'is_verified') {
            echo "ALTER TABLE patients ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER token;\n";
        } elseif ($col == 'token_created_at') {
            echo "ALTER TABLE patients ADD COLUMN token_created_at DATETIME NULL DEFAULT NULL AFTER is_verified;\n";
        }
    }
    echo "</pre></div>";
} else {
    echo "<div class='success'><strong>✅ All required columns exist!</strong></div>";
}

// Check for indexes
echo "<h2>🔑 Index Check</h2>";
$indexQuery = $conn->query("SHOW INDEX FROM patients");
$indexes = [];
while ($index = $indexQuery->fetch_assoc()) {
    $indexes[] = $index['Column_name'];
}

echo "<table>";
echo "<tr><th>Index</th><th>Status</th></tr>";

$recommendedIndexes = ['email', 'token'];
foreach ($recommendedIndexes as $idxCol) {
    $status = in_array($idxCol, $indexes) ? "✅ Indexed" : "⚠️ Not indexed (recommended)";
    echo "<tr><td><span class='code'>$idxCol</span></td><td>$status</td></tr>";
}
echo "</table>";

// Check PHPMailer
echo "<h2>📧 PHPMailer Check</h2>";
if (file_exists('vendor/autoload.php')) {
    echo "<div class='success'><strong>✅ PHPMailer vendor folder exists</strong></div>";
    
    require 'vendor/autoload.php';
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "<div class='success'><strong>✅ PHPMailer class loaded successfully</strong></div>";
    } else {
        echo "<div class='error'><strong>❌ PHPMailer class not found</strong><br>";
        echo "Run: <span class='code'>composer require phpmailer/phpmailer</span></div>";
    }
} else {
    echo "<div class='error'><strong>❌ Composer autoload not found</strong><br>";
    echo "Run: <span class='code'>composer install</span> in your project directory</div>";
}

// Check mailer.php configuration
echo "<h2>⚙️ Email Configuration</h2>";
if (file_exists('mailer.php')) {
    echo "<div class='success'><strong>✅ mailer.php exists</strong></div>";
    
    $mailerContent = file_get_contents('mailer.php');
    
    if (strpos($mailerContent, 'thecompassionatespace49@gmail.com') !== false) {
        echo "<div class='success'><strong>✅ Gmail credentials configured</strong><br>";
        echo "Email: <span class='code'>thecompassionatespace49@gmail.com</span></div>";
    } else {
        echo "<div class='warning'><strong>⚠️ Gmail credentials may need review</strong></div>";
    }
} else {
    echo "<div class='error'><strong>❌ mailer.php not found</strong></div>";
}

// Final summary
echo "<h2>📊 Summary</h2>";
if (count($missingColumns) == 0 && count($issues) == 0) {
    echo "<div class='success'><strong>🎉 All checks passed! Your system is ready to use.</strong><br><br>";
    echo "<strong>Next steps:</strong><br>";
    echo "1. Test registration at: <a href='register.php'>register.php</a><br>";
    echo "2. Check email for verification link<br>";
    echo "3. Test login at: <a href='login.php'>login.php</a><br>";
    echo "4. Test forgot password at: <a href='forgot_password.php'>forgot_password.php</a><br>";
    echo "</div>";
} else {
    echo "<div class='warning'><strong>⚠️ Some issues found. Please fix them using the SQL commands above.</strong></div>";
}

// Sample user check
$userCount = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'];
$verifiedCount = $conn->query("SELECT COUNT(*) as count FROM patients WHERE is_verified = 1")->fetch_assoc()['count'];

echo "<div class='info'>";
echo "<strong>👥 User Statistics:</strong><br>";
echo "Total users: <strong>$userCount</strong><br>";
echo "Verified users: <strong>$verifiedCount</strong><br>";
echo "Unverified users: <strong>" . ($userCount - $verifiedCount) . "</strong>";
echo "</div>";

$conn->close();

echo "<hr>";
echo "<p><em>To re-run this check, refresh this page. For detailed setup instructions, see EMAIL_SETUP_README.md</em></p>";
echo "</body></html>";
?>
