<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$patient_id = $_SESSION['user_id'];

echo "<h2>Debug Payment Data for Patient ID: $patient_id</h2>";

// Check all appointments for this patient
$sql = "SELECT appointment_id, appointment_date, appointment_time, status, payment_status, payment_amount, payment_gateway, payment_date, payment_id, transaction_id 
        FROM appointments 
        WHERE patient_id = '$patient_id' 
        ORDER BY appointment_date DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr>
            <th>ID</th>
            <th>Date</th>
            <th>Status</th>
            <th>Payment Status</th>
            <th>Amount</th>
            <th>Gateway</th>
            <th>Payment Date</th>
            <th>Payment ID</th>
            <th>Transaction ID</th>
          </tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['appointment_id'] . "</td>";
        echo "<td>" . $row['appointment_date'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td><strong>" . $row['payment_status'] . "</strong></td>";
        echo "<td>" . $row['payment_amount'] . "</td>";
        echo "<td>" . $row['payment_gateway'] . "</td>";
        echo "<td>" . $row['payment_date'] . "</td>";
        echo "<td>" . $row['payment_id'] . "</td>";
        echo "<td>" . $row['transaction_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No appointments found for patient ID: $patient_id</p>";
}

// Show the exact query being used
echo "<hr>";
echo "<h3>Query being used in payment.php:</h3>";
echo "<pre>";
echo "SELECT * FROM appointments 
WHERE patient_id = '$patient_id' AND payment_status IN ('completed', 'paid') 
ORDER BY payment_date DESC";
echo "</pre>";

$conn->close();
?>
