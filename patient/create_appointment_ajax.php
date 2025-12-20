<?php
// API endpoint to create appointment
session_start();
include '../config/db.php';
include 'payment_config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $issue = $_POST['issue'] ?? '';
    $payment_gateway = $_POST['payment_gateway'] ?? 'razorpay';
    
    if (empty($date) || empty($time) || empty($issue)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    $status = 'Pending Payment';
    $payment_amount = APPOINTMENT_FEE;

    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time, initial_health_issue, status, payment_status, payment_amount) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
    
    if ($stmt) {
        $stmt->bind_param("issssd", $patient_id, $date, $time, $issue, $status, $payment_amount);
        
        if ($stmt->execute()) {
            $appointment_id = $conn->insert_id;
            
            // Get patient details
            $res = $conn->query("SELECT * FROM patients WHERE patient_id = '$patient_id'");
            $patient_data = $res->fetch_assoc();
            
            $response = [
                'success' => true,
                'appointment_id' => $appointment_id,
                'amount' => $payment_amount,
                'gateway' => $payment_gateway,
                'patient' => [
                    'name' => $patient_data['full_name'] ?? 'Patient',
                    'email' => $patient_data['email'] ?? '',
                    'phone' => $patient_data['phone'] ?? '9999999999'
                ],
                'razorpay_key' => RAZORPAY_KEY_ID,
                'cashfree_app_id' => CASHFREE_APP_ID,
                'currency' => CURRENCY
            ];
            
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
