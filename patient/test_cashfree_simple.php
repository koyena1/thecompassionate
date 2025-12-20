<!DOCTYPE html>
<html>
<head>
    <title>Cashfree Test Payment</title>
</head>
<body>
    <h2>Test Cashfree Payment</h2>
    
    <?php
    include 'payment_config.php';
    
    // Test order details
    $order_id = "TEST_ORDER_" . time();
    $order_amount = "1.00"; // Minimum test amount
    $order_currency = "INR";
    $order_note = "Test Order";
    
    // Customer details
    $customer_name = "Test User";
    $customer_email = "test@example.com";
    $customer_phone = "9999999999";
    
    // URLs
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = "http://" . $_SERVER['HTTP_HOST'] . $script_path;
    $return_url = $base_url . "/payment_callback.php";
    $notify_url = $base_url . "/payment_callback.php";
    
    // Generate signature
    $signature_data = $order_id . $order_amount . $order_currency . CASHFREE_APP_ID;
    $signature = base64_encode(hash_hmac('sha256', $signature_data, CASHFREE_SECRET_KEY, true));
    
    // Checkout URL
    $checkout_url = (CASHFREE_ENV === "PROD") 
        ? "https://www.cashfree.com/checkout/post/submit" 
        : "https://test.cashfree.com/billpay/checkout/post/submit";
    
    echo "<h3>Order Details:</h3>";
    echo "<p>Order ID: $order_id</p>";
    echo "<p>Amount: ₹$order_amount</p>";
    echo "<p>Signature Data: $signature_data</p>";
    echo "<p>Signature: $signature</p>";
    echo "<p>Checkout URL: $checkout_url</p>";
    echo "<hr>";
    ?>
    
    <form method="POST" action="<?php echo $checkout_url; ?>">
        <input type="hidden" name="appId" value="<?php echo CASHFREE_APP_ID; ?>">
        <input type="hidden" name="orderId" value="<?php echo $order_id; ?>">
        <input type="hidden" name="orderAmount" value="<?php echo $order_amount; ?>">
        <input type="hidden" name="orderCurrency" value="<?php echo $order_currency; ?>">
        <input type="hidden" name="orderNote" value="<?php echo $order_note; ?>">
        <input type="hidden" name="customerName" value="<?php echo $customer_name; ?>">
        <input type="hidden" name="customerEmail" value="<?php echo $customer_email; ?>">
        <input type="hidden" name="customerPhone" value="<?php echo $customer_phone; ?>">
        <input type="hidden" name="returnUrl" value="<?php echo $return_url; ?>">
        <input type="hidden" name="notifyUrl" value="<?php echo $notify_url; ?>">
        <input type="hidden" name="signature" value="<?php echo $signature; ?>">
        
        <button type="submit" style="padding: 15px 30px; background: #00B69B; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
            Test Payment - Pay ₹1.00
        </button>
    </form>
    
    <p style="margin-top: 20px; color: #666; font-size: 14px;">
        Click the button above to test Cashfree payment integration.<br>
        Use Cashfree test cards for testing in TEST mode.
    </p>
</body>
</html>
