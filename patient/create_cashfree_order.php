<?php
// Create Cashfree payment order using NEW Payment Gateway API
session_start();
include '../config/db.php';
include 'payment_config.php';

// Clear any previous output
ob_clean();
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_id = $_POST['appointment_id'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $customer_name = $_POST['customer_name'] ?? 'Patient';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    
    if (empty($appointment_id) || empty($amount)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required parameters'
        ]);
        exit;
    }
    
    // Generate order ID
    $order_id = "ORDER_" . $appointment_id . "_" . time();
    
    // Generate return URL
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = "http://" . $_SERVER['HTTP_HOST'] . $script_path;
    $return_url = $base_url . "/payment_callback.php?appointment_id=" . $appointment_id;
    
    // Prepare order data for NEW Cashfree API
    $orderData = [
        'order_id' => $order_id,
        'order_amount' => (float)$amount,
        'order_currency' => CURRENCY,
        'customer_details' => [
            'customer_id' => 'PATIENT_' . $appointment_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone
        ],
        'order_meta' => [
            'return_url' => $return_url,
            'notify_url' => $return_url
        ],
        'order_note' => 'Appointment Booking - ID: ' . $appointment_id
    ];
    
    // Determine API endpoint
    $api_url = (CASHFREE_ENV === "PROD") 
        ? "https://api.cashfree.com/pg/orders" 
        : "https://sandbox.cashfree.com/pg/orders";
    
    // Initialize cURL
    $ch = curl_init($api_url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-version: 2022-09-01',
        'x-client-id: ' . CASHFREE_APP_ID,
        'x-client-secret: ' . CASHFREE_SECRET_KEY
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 || $http_code == 201) {
        $result = json_decode($response, true);
        
        if (isset($result['payment_session_id'])) {
            echo json_encode([
                'success' => true,
                'order_id' => $order_id,
                'payment_session_id' => $result['payment_session_id'],
                'order_token' => $result['payment_session_id'], // For backward compatibility
                'environment' => CASHFREE_ENV
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create order: ' . ($result['message'] ?? 'Unknown error')
            ]);
        }
    } else {
        $error = json_decode($response, true);
        echo json_encode([
            'success' => false,
            'message' => 'API Error: ' . ($error['message'] ?? 'Failed to create order'),
            'details' => $error
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
}
