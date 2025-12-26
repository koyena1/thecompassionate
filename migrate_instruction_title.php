<?php
/**
 * Migration Script: Add instruction_title to patient_uploads table
 * Run this file once to update your database schema
 * Access: http://localhost/psychiatrist/migrate_instruction_title.php
 */

include 'config/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #589167; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid green; margin: 10px 0; border-radius: 5px; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid red; margin: 10px 0; border-radius: 5px; }
        .info { background: #d1ecf1; padding: 10px; border-left: 4px solid #0c5460; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔧 Database Migration: Instruction Title for Uploads</h1>";

// Check database connection
if (!$conn) {
    echo "<div class='error'><strong>❌ Database Connection Failed!</strong><br>";
    echo "Error: " . mysqli_connect_error() . "</div>";
    exit();
}

echo "<div class='success'><strong>✅ Database Connected Successfully!</strong></div>";

// Check if patient_uploads table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'patient_uploads'");
if ($tableCheck->num_rows == 0) {
    echo "<div class='error'><strong>❌ Table 'patient_uploads' does not exist!</strong><br>";
    echo "Please create the patient_uploads table first.</div>";
    exit();
}

echo "<div class='success'><strong>✅ Table 'patient_uploads' exists</strong></div>";

// Check if instruction_title column already exists
$columnCheck = $conn->query("SHOW COLUMNS FROM patient_uploads LIKE 'instruction_title'");
if ($columnCheck->num_rows > 0) {
    echo "<div class='info'><strong>ℹ️ Column 'instruction_title' already exists!</strong><br>";
    echo "No migration needed. Your database is up to date.</div>";
} else {
    // Add instruction_title column
    echo "<div class='info'><strong>🔄 Adding 'instruction_title' column...</strong></div>";
    
    $sql = "ALTER TABLE patient_uploads ADD COLUMN instruction_title VARCHAR(255) NULL AFTER patient_id";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='success'><strong>✅ Column 'instruction_title' added successfully!</strong></div>";
        
        // Update existing records
        echo "<div class='info'><strong>🔄 Updating existing records...</strong></div>";
        $updateSql = "UPDATE patient_uploads SET instruction_title = 'General Document' WHERE instruction_title IS NULL OR instruction_title = ''";
        
        if ($conn->query($updateSql) === TRUE) {
            $affectedRows = $conn->affected_rows;
            echo "<div class='success'><strong>✅ Updated $affectedRows existing records with default instruction title!</strong></div>";
        } else {
            echo "<div class='error'><strong>⚠️ Warning: Could not update existing records.</strong><br>";
            echo "Error: " . $conn->error . "</div>";
        }
        
        echo "<div class='success'><strong>🎉 Migration completed successfully!</strong><br>";
        echo "You can now upload files and they will be named according to the instruction title.</div>";
        
    } else {
        echo "<div class='error'><strong>❌ Error adding column!</strong><br>";
        echo "Error: " . $conn->error . "</div>";
    }
}

echo "<br><div class='info'><strong>📝 Next Steps:</strong><br>";
echo "1. Delete this migration file for security: <code>migrate_instruction_title.php</code><br>";
echo "2. Test the upload functionality in the patient documentation page<br>";
echo "3. Files will now be named based on instruction titles (e.g., Sugar_test_123_timestamp.jpg)</div>";

echo "</body></html>";

$conn->close();
?>
