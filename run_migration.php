<?php
// Database Migration Script for Patient-Specific Documentation Feature
// Direct database connection to avoid including other files

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "safe_space_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<h2 style='color: red;'>Connection failed: " . $conn->connect_error . "</h2>");
}

echo "<h2>Running Database Migration...</h2><br>";

// Check and add patient_id column
echo "Checking patient_id column... ";
$check_col1 = $conn->query("SHOW COLUMNS FROM documentation LIKE 'patient_id'");
if ($check_col1->num_rows == 0) {
    $sql1 = "ALTER TABLE documentation ADD COLUMN patient_id INT NULL AFTER id";
    if ($conn->query($sql1) === TRUE) {
        echo "<span style='color: green;'>✓ Added</span><br>";
    } else {
        echo "<span style='color: red;'>✗ Error: " . $conn->error . "</span><br>";
    }
} else {
    echo "<span style='color: orange;'>⚠ Already exists</span><br>";
}

// Check and add created_at column
echo "Checking created_at column... ";
$check_col2 = $conn->query("SHOW COLUMNS FROM documentation LIKE 'created_at'");
if ($check_col2->num_rows == 0) {
    $sql2 = "ALTER TABLE documentation ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    if ($conn->query($sql2) === TRUE) {
        echo "<span style='color: green;'>✓ Added</span><br>";
    } else {
        echo "<span style='color: red;'>✗ Error: " . $conn->error . "</span><br>";
    }
} else {
    echo "<span style='color: orange;'>⚠ Already exists</span><br>";
}

// Check and add updated_at column
echo "Checking updated_at column... ";
$check_col3 = $conn->query("SHOW COLUMNS FROM documentation LIKE 'updated_at'");
if ($check_col3->num_rows == 0) {
    $sql3 = "ALTER TABLE documentation ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    if ($conn->query($sql3) === TRUE) {
        echo "<span style='color: green;'>✓ Added</span><br>";
    } else {
        echo "<span style='color: red;'>✗ Error: " . $conn->error . "</span><br>";
    }
} else {
    echo "<span style='color: orange;'>⚠ Already exists</span><br>";
}

// Check if foreign key already exists before adding
$check_fk = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                          WHERE TABLE_SCHEMA = 'safe_space_db' 
                          AND TABLE_NAME = 'documentation' 
                          AND CONSTRAINT_NAME = 'fk_documentation_patient'");

if ($check_fk->num_rows == 0) {
    // Add foreign key constraint
    $sql4 = "ALTER TABLE documentation 
             ADD CONSTRAINT fk_documentation_patient 
             FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE";

    echo "Adding foreign key constraint... ";
    if ($conn->query($sql4) === TRUE) {
        echo "<span style='color: green;'>✓ Success</span><br>";
    } else {
        echo "<span style='color: red;'>✗ Error: " . $conn->error . "</span><br>";
    }
} else {
    echo "Foreign key constraint... <span style='color: orange;'>⚠ Already exists</span><br>";
}

// Update existing records to have NULL patient_id (global)
$sql5 = "UPDATE documentation SET patient_id = NULL WHERE patient_id IS NULL OR patient_id = 0";

echo "Setting existing records to global (NULL patient_id)... ";
if ($conn->query($sql5) === TRUE) {
    echo "<span style='color: green;'>✓ Success</span><br>";
} else {
    echo "<span style='color: red;'>✗ Error: " . $conn->error . "</span><br>";
}

// Check if index already exists before adding
$check_index = $conn->query("SHOW INDEX FROM documentation WHERE Key_name = 'idx_patient_id'");

if ($check_index->num_rows == 0) {
    // Create index for faster queries
    $sql6 = "CREATE INDEX idx_patient_id ON documentation(patient_id)";

    echo "Creating index on patient_id... ";
    if ($conn->query($sql6) === TRUE) {
        echo "<span style='color: green;'>✓ Success</span><br>";
    } else {
        echo "<span style='color: red;'>✗ Error: " . $conn->error . "</span><br>";
    }
} else {
    echo "Index on patient_id... <span style='color: orange;'>⚠ Already exists</span><br>";
}

echo "<br><h3 style='color: green;'>Migration Complete!</h3>";
echo "<p>You can now use the patient-specific documentation feature.</p>";
echo "<p><a href='admin/clinical_updates_documentation.php'>Go to Clinical Updates & Documentation</a></p>";
echo "<p><strong>Note:</strong> You can delete this file (run_migration.php) after successful migration.</p>";

$conn->close();
?>
