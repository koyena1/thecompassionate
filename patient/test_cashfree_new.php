<!DOCTYPE html>
<html>
<head>
    <title>Cashfree NEW API Test</title>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
</head>
<body>
    <h2>Test Cashfree Payment (NEW API)</h2>
    <button onclick="testPayment()" style="padding: 15px 30px; background: #00B69B; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
        Test Payment - Pay ₹1.00
    </button>
    
    <div id="status" style="margin-top: 20px;"></div>
    
    <script>
        async function testPayment() {
            const statusDiv = document.getElementById('status');
            statusDiv.innerHTML = 'Creating order...';
            
            try {
                // Create order
                const response = await fetch('create_cashfree_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        appointment_id: '999',
                        amount: '1.00',
                        customer_name: 'Test Patient',
                        customer_email: 'test@example.com',
                        customer_phone: '9999999999'
                    })
                });
                
                const orderData = await response.json();
                console.log('Order Data:', orderData);
                
                if (!orderData.success) {
                    statusDiv.innerHTML = '<span style="color:red;">Error: ' + orderData.message + '</span>';
                    if (orderData.details) {
                        console.error('Error details:', orderData.details);
                    }
                    return;
                }
                
                statusDiv.innerHTML = 'Order created! Opening checkout...';
                
                // Initialize Cashfree
                const cashfree = Cashfree({
                    mode: orderData.environment === 'PROD' ? 'production' : 'sandbox'
                });
                
                // Checkout options
                const checkoutOptions = {
                    paymentSessionId: orderData.payment_session_id,
                    returnUrl: window.location.origin + '/psychiatrist_doctor/patient/payment_callback.php?appointment_id=999'
                };
                
                statusDiv.innerHTML = 'Opening Cashfree checkout...';
                
                // Open checkout
                cashfree.checkout(checkoutOptions).then((result) => {
                    console.log('Checkout result:', result);
                    if (result.error) {
                        statusDiv.innerHTML = '<span style="color:red;">Payment error: ' + result.error.message + '</span>';
                    }
                    if (result.paymentDetails) {
                        statusDiv.innerHTML = '<span style="color:green;">Payment completed!</span>';
                    }
                });
                
            } catch (error) {
                console.error('Error:', error);
                statusDiv.innerHTML = '<span style="color:red;">Error: ' + error.message + '</span>';
            }
        }
    </script>
    
    <p style="margin-top: 30px; color: #666;">
        <strong>This uses the NEW Cashfree Payment Gateway API</strong><br>
        - No signature mismatch errors<br>
        - Modern SDK-based checkout<br>
        - Works with new credentials (cfsk_ma_test_...)<br>
    </p>
</body>
</html>
