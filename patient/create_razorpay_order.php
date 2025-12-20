<?php
// Create Razorpay Order
session_start();

// Clear any output buffers
if (ob_get_level()) ob_end_clean();

include '../config/db.php';
include 'payment_config.php';

header('Content-Type: application/json');

// Log errors to file instead of displaying
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../error.log');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_id = $_POST['appointment_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    
    if (empty($appointment_id) || empty($amount)) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters', 'debug' => ['appointment_id' => $appointment_id, 'amount' => $amount]]);
        exit;
    }
    
    // Create Razorpay Order using API
    $api_key = RAZORPAY_KEY_ID;
    $api_secret = RAZORPAY_KEY_SECRET;
    
    $order_data = [
        'amount' => intval($amount * 100), // Amount in paise
        'currency' => CURRENCY,
        'receipt' => 'rcpt_' . $appointment_id,
        'payment_capture' => 1, // Auto capture payment
        'notes' => [
            'appointment_id' => strval($appointment_id),
            'booking_type' => 'psychiatrist_consultation'
        ]
    ];
    
    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($api_key . ':' . $api_secret)
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code == 200) {
        $order = json_decode($response, true);
        echo json_encode([
            'success' => true,
            'order_id' => $order['id'],
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'receipt' => $order['receipt']
        ]);
    } else {
        $error_response = json_decode($response, true);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create Razorpay order',
            'http_code' => $http_code,
            'error' => $error_response ?? $response,
            'curl_error' => $curl_error
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
exit;
