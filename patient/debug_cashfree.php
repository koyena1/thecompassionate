<?php
// Debug Cashfree Signature Generation
include 'payment_config.php';

// Test values
$order_id = "ORDER_123_1234567890";
$order_amount = "700.00";
$order_currency = "INR";

echo "<h2>Cashfree Debug Information</h2>";
echo "<p><strong>App ID:</strong> " . CASHFREE_APP_ID . "</p>";
echo "<p><strong>Secret Key Length:</strong> " . strlen(CASHFREE_SECRET_KEY) . " characters</p>";
echo "<p><strong>Secret Key Starts With:</strong> " . substr(CASHFREE_SECRET_KEY, 0, 15) . "...</p>";
echo "<p><strong>Environment:</strong> " . CASHFREE_ENV . "</p>";

echo "<hr>";
echo "<h3>Signature Generation Test</h3>";
echo "<p><strong>Order ID:</strong> $order_id</p>";
echo "<p><strong>Order Amount:</strong> $order_amount</p>";
echo "<p><strong>Order Currency:</strong> $order_currency</p>";

// Test signature generation
$signature_data = $order_id . $order_amount . $order_currency . CASHFREE_APP_ID;
echo "<p><strong>Signature Data String:</strong> $signature_data</p>";
echo "<p><strong>Signature Data Length:</strong> " . strlen($signature_data) . "</p>";

$signature = base64_encode(hash_hmac('sha256', $signature_data, CASHFREE_SECRET_KEY, true));
echo "<p><strong>Generated Signature (Base64):</strong> $signature</p>";

// Also show hex version
$signature_hex = hash_hmac('sha256', $signature_data, CASHFREE_SECRET_KEY);
echo "<p><strong>Hex Signature:</strong> $signature_hex</p>";

echo "<hr>";
echo "<h3>API Endpoint</h3>";
$checkout_url = (CASHFREE_ENV === "PROD") 
    ? "https://www.cashfree.com/checkout/post/submit" 
    : "https://test.cashfree.com/billpay/checkout/post/submit";
echo "<p><strong>Checkout URL:</strong> $checkout_url</p>";

echo "<hr>";
echo "<h3>Base URL Detection</h3>";
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$base_url = "http://" . $_SERVER['HTTP_HOST'] . $script_path;
echo "<p><strong>Detected Base URL:</strong> $base_url</p>";
echo "<p><strong>Return URL:</strong> $base_url/payment_callback.php</p>";

echo "<hr>";
echo "<h3>Test Form Data</h3>";
echo "<pre>";
$formData = [
    'appId' => CASHFREE_APP_ID,
    'orderId' => $order_id,
    'orderAmount' => $order_amount,
    'orderCurrency' => $order_currency,
    'orderNote' => 'Test Order',
    'customerName' => 'Test Patient',
    'customerEmail' => 'test@example.com',
    'customerPhone' => '9999999999',
    'returnUrl' => $base_url . '/payment_callback.php',
    'notifyUrl' => $base_url . '/payment_callback.php',
    'signature' => $signature
];
print_r($formData);
echo "</pre>";
?>
