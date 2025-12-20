<?php
// Check if payment columns exist in database
include '../config/db.php';

echo "<h2>Database Column Check</h2>";
echo "<p>Checking if payment columns exist in appointments table...</p>";

$result = $conn->query("DESCRIBE appointments");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

$required_columns = ['payment_status', 'payment_amount', 'payment_id', 'payment_gateway', 'payment_date', 'transaction_id', 'invoice_number'];
$found_columns = [];

while($row = $result->fetch_assoc()) {
    $is_payment_column = in_array($row['Field'], $required_columns);
    $style = $is_payment_column ? "background-color: #d1fae5;" : "";
    echo "<tr style='$style'>";
    echo "<td><strong>" . $row['Field'] . "</strong></td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "</tr>";
    
    if($is_payment_column) {
        $found_columns[] = $row['Field'];
    }
}

echo "</table>";

echo "<h3>Payment Columns Status:</h3>";
echo "<ul>";
foreach($required_columns as $col) {
    $found = in_array($col, $found_columns);
    $icon = $found ? "✅" : "❌";
    $status = $found ? "<span style='color: green;'>Found</span>" : "<span style='color: red;'>Missing</span>";
    echo "<li>$icon <strong>$col</strong>: $status</li>";
}
echo "</ul>";

$missing_count = count($required_columns) - count($found_columns);

if($missing_count > 0) {
    echo "<div style='background: #fee2e2; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: #dc2626;'>⚠️ Action Required!</h3>";
    echo "<p><strong>You need to run the SQL update script:</strong></p>";
    echo "<ol>";
    echo "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
    echo "<li>Select database: <code>safe_space_db</code></li>";
    echo "<li>Click 'SQL' tab</li>";
    echo "<li>Open file: <code>payment_schema.sql</code> from your project folder</li>";
    echo "<li>Copy all content and paste it</li>";
    echo "<li>Click 'Go' button</li>";
    echo "<li>Refresh this page to verify</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #d1fae5; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: #059669;'>✅ Database is Ready!</h3>";
    echo "<p>All required payment columns are present in the appointments table.</p>";
    echo "<p><a href='book_appointment.php' style='background: #7B3F00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Test Booking System</a></p>";
    echo "</div>";
}

// Check Razorpay configuration
echo "<h3>Payment Gateway Configuration:</h3>";
include 'payment_config.php';
echo "<ul>";
echo "<li>Razorpay Key ID: " . (RAZORPAY_KEY_ID !== 'your_razorpay_key_id' ? "✅ Configured" : "❌ Not configured") . "</li>";
echo "<li>Cashfree App ID: " . (CASHFREE_APP_ID !== 'your_cashfree_app_id' ? "✅ Configured" : "❌ Not configured") . "</li>";
echo "<li>Appointment Fee: ₹" . number_format(APPOINTMENT_FEE, 2) . "</li>";
echo "<li>Admin Email: " . ADMIN_EMAIL . "</li>";
echo "</ul>";

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment System Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; }
        table { width: 100%; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
</body>
</html>
