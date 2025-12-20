<?php
session_start();
include '../config/db.php';
include 'payment_config.php';

// Handle NEW Cashfree Payment Gateway API callback (via URL parameter)
if (isset($_GET['appointment_id']) && !isset($_POST['razorpay_payment_id'])) {
    $appointment_id = $_GET['appointment_id'];
    
    // Get order details to verify payment
    $result = $conn->query("SELECT * FROM appointments WHERE appointment_id = $appointment_id");
    $appointment = $result->fetch_assoc();
    
    if ($appointment) {
        // Extract order_id from appointment
        $order_id = "ORDER_" . $appointment_id . "_*"; // Wildcard for any timestamp
        
        // Verify payment status with Cashfree API
        $api_url = (CASHFREE_ENV === "PROD") 
            ? "https://api.cashfree.com/pg/orders" 
            : "https://sandbox.cashfree.com/pg/orders";
        
        // Get actual order_id from database if stored, or search recent orders
        $order_search_url = $api_url . "?customer_id=PATIENT_" . $appointment_id;
        
        $ch = curl_init($order_search_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-version: 2022-09-01',
            'x-client-id: ' . CASHFREE_APP_ID,
            'x-client-secret: ' . CASHFREE_SECRET_KEY
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            $orders = json_decode($response, true);
            
            // Find the matching order and check status
            if (isset($orders[0]) && $orders[0]['order_status'] == 'PAID') {
                $order_details = $orders[0];
                
                $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($appointment_id, 6, '0', STR_PAD_LEFT);
                
                $stmt = $conn->prepare("UPDATE appointments SET 
                    payment_status = 'paid', 
                    payment_id = ?, 
                    payment_gateway = 'Cashfree',
                    payment_date = NOW(),
                    transaction_id = ?,
                    invoice_number = ?,
                    status = 'Confirmed'
                    WHERE appointment_id = ?");
                
                $order_id = $order_details['order_id'];
                $cf_order_id = $order_details['cf_order_id'] ?? $order_id;
                
                $stmt->bind_param("sssi", $order_id, $cf_order_id, $invoice_number, $appointment_id);
                $stmt->execute();
                
                // Get patient details
                $result = $conn->query("SELECT a.*, p.full_name, p.email 
                    FROM appointments a 
                    JOIN patients p ON a.patient_id = p.patient_id 
                    WHERE a.appointment_id = $appointment_id");
                $appointment = $result->fetch_assoc();
                
                $invoiceData = [
                    'invoice_number' => $invoice_number,
                    'patient_name' => $appointment['full_name'],
                    'patient_email' => $appointment['email'],
                    'appointment_date' => $appointment['appointment_date'],
                    'appointment_time' => $appointment['appointment_time'],
                    'health_issue' => $appointment['initial_health_issue'],
                    'transaction_id' => $cf_order_id,
                    'payment_gateway' => 'Cashfree',
                    'payment_date' => date('Y-m-d H:i:s'),
                    'amount' => $appointment['payment_amount']
                ];
                
                // Send emails
                sendInvoiceEmail($appointment['email'], $invoiceData, false);
                sendInvoiceEmail(ADMIN_EMAIL, $invoiceData, true);
                
                $_SESSION['payment_success'] = true;
                header("Location: booking_success.php?id=" . $appointment_id);
                exit();
            } else {
                $_SESSION['payment_error'] = 'Payment not completed or failed!';
                header("Location: book_appointment.php");
                exit();
            }
        }
    }
}

// Include mailer functions
function sendInvoiceEmail($email, $invoiceData, $isAdmin = false) {
    require_once '../mailer.php';
    $mail = getConfiguredMailer();
    if (!$mail) return false;

    try {
        $mail->setFrom('thecompassionatespace49@gmail.com', 'Medical App');
        $mail->addAddress($email);

        $recipient = $isAdmin ? 'Admin' : $invoiceData['patient_name'];
        $mail->Subject = 'Appointment Booking Confirmation - Invoice #' . $invoiceData['invoice_number'];
        
        $mail->isHTML(true);
        $mail->Body = generateInvoiceHTML($invoiceData, $isAdmin);
        $mail->AltBody = generateInvoicePlainText($invoiceData);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Invoice Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

function generateInvoiceHTML($data, $isAdmin = false) {
    $greeting = $isAdmin ? 'Dear Admin' : 'Dear ' . htmlspecialchars($data['patient_name']);
    
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #7B3F00; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .invoice-details { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; }
            .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
            .total-row { font-weight: bold; font-size: 18px; color: #7B3F00; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Payment Confirmation</h1>
                <p>Invoice #" . htmlspecialchars($data['invoice_number']) . "</p>
            </div>
            <div class='content'>
                <p>{$greeting},</p>
                <p>Your appointment has been successfully booked and payment confirmed!</p>
                
                <div class='invoice-details'>
                    <h3>Appointment Details</h3>
                    <div class='detail-row'>
                        <span>Patient Name:</span>
                        <strong>" . htmlspecialchars($data['patient_name']) . "</strong>
                    </div>
                    <div class='detail-row'>
                        <span>Email:</span>
                        <strong>" . htmlspecialchars($data['patient_email']) . "</strong>
                    </div>
                    <div class='detail-row'>
                        <span>Appointment Date:</span>
                        <strong>" . date('d M Y', strtotime($data['appointment_date'])) . "</strong>
                    </div>
                    <div class='detail-row'>
                        <span>Appointment Time:</span>
                        <strong>" . htmlspecialchars($data['appointment_time']) . "</strong>
                    </div>
                    <div class='detail-row'>
                        <span>Health Issue:</span>
                        <strong>" . htmlspecialchars($data['health_issue']) . "</strong>
                    </div>
                    
                    <h3 style='margin-top: 30px;'>Payment Details</h3>
                    <div class='detail-row'>
                        <span>Transaction ID:</span>
                        <strong>" . htmlspecialchars($data['transaction_id']) . "</strong>
                    </div>
                    <div class='detail-row'>
                        <span>Payment Gateway:</span>
                        <strong>" . htmlspecialchars($data['payment_gateway']) . "</strong>
                    </div>
                    <div class='detail-row'>
                        <span>Payment Date:</span>
                        <strong>" . date('d M Y H:i:s', strtotime($data['payment_date'])) . "</strong>
                    </div>
                    <div class='detail-row total-row'>
                        <span>Amount Paid:</span>
                        <strong>₹" . number_format($data['amount'], 2) . "</strong>
                    </div>
                </div>
                
                <p><strong>Status:</strong> <span style='color: green;'>Confirmed & Paid</span></p>
                <p>We look forward to seeing you at your appointment!</p>
            </div>
            <div class='footer'>
                <p>© 2025 Medical App. All rights reserved.</p>
                <p>If you have any questions, please contact us.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function generateInvoicePlainText($data) {
    return "
    Payment Confirmation - Invoice #{$data['invoice_number']}
    
    Dear {$data['patient_name']},
    
    Your appointment has been successfully booked and payment confirmed!
    
    Appointment Details:
    - Patient Name: {$data['patient_name']}
    - Email: {$data['patient_email']}
    - Date: " . date('d M Y', strtotime($data['appointment_date'])) . "
    - Time: {$data['appointment_time']}
    - Health Issue: {$data['health_issue']}
    
    Payment Details:
    - Transaction ID: {$data['transaction_id']}
    - Payment Gateway: {$data['payment_gateway']}
    - Payment Date: " . date('d M Y H:i:s', strtotime($data['payment_date'])) . "
    - Amount Paid: ₹" . number_format($data['amount'], 2) . "
    
    Status: Confirmed & Paid
    
    We look forward to seeing you at your appointment!
    
    © 2025 Medical App. All rights reserved.
    ";
}

// Handle Razorpay Callback
if (isset($_POST['razorpay_payment_id']) && isset($_POST['razorpay_order_id']) && isset($_POST['razorpay_signature'])) {
    $payment_id = $_POST['razorpay_payment_id'];
    $order_id = $_POST['razorpay_order_id'];
    $signature = $_POST['razorpay_signature'];
    $appointment_id = $_POST['appointment_id'];
    
    // Verify Razorpay signature
    $generated_signature = hash_hmac('sha256', $order_id . "|" . $payment_id, RAZORPAY_KEY_SECRET);
    
    if ($generated_signature === $signature) {
        // Payment verified - Update database
        $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($appointment_id, 6, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("UPDATE appointments SET 
            payment_status = 'paid', 
            payment_id = ?, 
            payment_gateway = 'Razorpay',
            payment_date = NOW(),
            transaction_id = ?,
            invoice_number = ?,
            status = 'Confirmed'
            WHERE appointment_id = ?");
        $stmt->bind_param("sssi", $order_id, $payment_id, $invoice_number, $appointment_id);
        $stmt->execute();
        
        // Get appointment and patient details for invoice
        $result = $conn->query("SELECT a.*, p.full_name, p.email 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.patient_id 
            WHERE a.appointment_id = $appointment_id");
        $appointment = $result->fetch_assoc();
        
        $invoiceData = [
            'invoice_number' => $invoice_number,
            'patient_name' => $appointment['full_name'],
            'patient_email' => $appointment['email'],
            'appointment_date' => $appointment['appointment_date'],
            'appointment_time' => $appointment['appointment_time'],
            'health_issue' => $appointment['initial_health_issue'],
            'transaction_id' => $payment_id,
            'payment_gateway' => 'Razorpay',
            'payment_date' => date('Y-m-d H:i:s'),
            'amount' => $appointment['payment_amount']
        ];
        
        // Send emails to patient and admin
        sendInvoiceEmail($appointment['email'], $invoiceData, false);
        sendInvoiceEmail(ADMIN_EMAIL, $invoiceData, true);
        
        $_SESSION['payment_success'] = true;
        header("Location: booking_success.php?id=" . $appointment_id);
        exit();
    } else {
        // Signature verification failed
        $_SESSION['payment_error'] = 'Payment verification failed! Signature mismatch.';
        header("Location: book_appointment.php");
        exit();
    }
}
// Handle old callback format (for backward compatibility)
elseif (isset($_POST['razorpay_payment_id']) && isset($_POST['appointment_id']) && !isset($_POST['razorpay_order_id'])) {
    $payment_id = $_POST['razorpay_payment_id'];
    $appointment_id = $_POST['appointment_id'];
    
    // Old format without signature verification (less secure)
    $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($appointment_id, 6, '0', STR_PAD_LEFT);
    
    $stmt = $conn->prepare("UPDATE appointments SET 
        payment_status = 'paid', 
        payment_id = ?, 
        payment_gateway = 'Razorpay',
        payment_date = NOW(),
        transaction_id = ?,
        invoice_number = ?,
        status = 'Confirmed'
        WHERE appointment_id = ?");
    $stmt->bind_param("sssi", $payment_id, $payment_id, $invoice_number, $appointment_id);
    $stmt->execute();
    
    // Get appointment and patient details for invoice
    $result = $conn->query("SELECT a.*, p.full_name, p.email 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        WHERE a.appointment_id = $appointment_id");
    $appointment = $result->fetch_assoc();
    
    $invoiceData = [
        'invoice_number' => $invoice_number,
        'patient_name' => $appointment['full_name'],
        'patient_email' => $appointment['email'],
        'appointment_date' => $appointment['appointment_date'],
        'appointment_time' => $appointment['appointment_time'],
        'health_issue' => $appointment['initial_health_issue'],
        'transaction_id' => $payment_id,
        'payment_gateway' => 'Razorpay',
        'payment_date' => date('Y-m-d H:i:s'),
        'amount' => $appointment['payment_amount']
    ];
    
    // Send emails to patient and admin
    sendInvoiceEmail($appointment['email'], $invoiceData, false);
    sendInvoiceEmail(ADMIN_EMAIL, $invoiceData, true);
    
    $_SESSION['payment_success'] = true;
    header("Location: booking_success.php?id=" . $appointment_id);
    exit();
}

// Handle Cashfree Callback
if (isset($_POST['orderId']) && isset($_POST['orderAmount'])) {
    $order_id = $_POST['orderId'];
    $order_amount = $_POST['orderAmount'];
    $referenceId = $_POST['referenceId'];
    $txStatus = $_POST['txStatus'];
    $paymentMode = $_POST['paymentMode'];
    $txMsg = $_POST['txMsg'];
    $txTime = $_POST['txTime'];
    $signature = $_POST['signature'];
    
    // Verify signature (order: orderId, orderAmount, referenceId, txStatus, paymentMode, txMsg, txTime)
    $data = $order_id . $order_amount . $referenceId . $txStatus . $paymentMode . $txMsg . $txTime;
    $generated_signature = base64_encode(hash_hmac('sha256', $data, CASHFREE_SECRET_KEY, true));
    
    if ($generated_signature === $signature && $txStatus === 'SUCCESS') {
        // Extract appointment_id from order_id format: ORDER_<appointment_id>_<timestamp>
        preg_match('/ORDER_(\d+)_/', $order_id, $matches);
        $appointment_id = isset($matches[1]) ? $matches[1] : null;
        
        if (!$appointment_id) {
            $_SESSION['payment_error'] = 'Invalid order ID format!';
            header("Location: book_appointment.php");
            exit();
        }
        
        $invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($appointment_id, 6, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("UPDATE appointments SET 
            payment_status = 'paid', 
            payment_id = ?, 
            payment_gateway = 'Cashfree',
            payment_date = NOW(),
            transaction_id = ?,
            invoice_number = ?,
            status = 'Confirmed'
            WHERE appointment_id = ?");
        $stmt->bind_param("sssi", $order_id, $referenceId, $invoice_number, $appointment_id);
        $stmt->execute();
        
        // Get appointment and patient details
        $result = $conn->query("SELECT a.*, p.full_name, p.email 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.patient_id 
            WHERE a.appointment_id = $appointment_id");
        $appointment = $result->fetch_assoc();
        
        $invoiceData = [
            'invoice_number' => $invoice_number,
            'patient_name' => $appointment['full_name'],
            'patient_email' => $appointment['email'],
            'appointment_date' => $appointment['appointment_date'],
            'appointment_time' => $appointment['appointment_time'],
            'health_issue' => $appointment['initial_health_issue'],
            'transaction_id' => $referenceId,
            'payment_gateway' => 'Cashfree',
            'payment_date' => date('Y-m-d H:i:s'),
            'amount' => $appointment['payment_amount']
        ];
        
        // Send emails
        sendInvoiceEmail($appointment['email'], $invoiceData, false);
        sendInvoiceEmail(ADMIN_EMAIL, $invoiceData, true);
        
        $_SESSION['payment_success'] = true;
        header("Location: booking_success.php?id=" . $appointment_id);
        exit();
    } else {
        $_SESSION['payment_error'] = 'Payment verification failed!';
        header("Location: book_appointment.php");
        exit();
    }
}

// If no valid callback data
header("Location: book_appointment.php");
exit();
?>
