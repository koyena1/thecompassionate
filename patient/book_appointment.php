<?php 
// FILE: psychiatrist/patient/book_appointment.php
include '../config/db.php'; 
include 'payment_config.php';
session_start();

$patient_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$message = "";
$messageType = "";
$appointment_id = null;
$payment_gateway = 'razorpay'; // Default gateway

// Get Patient Info
$res = $conn->query("SELECT * FROM patients WHERE patient_id = '$patient_id'");
$patient_data = $res->fetch_assoc();
$display_name = $patient_data['full_name'] ?? 'Patient';
$display_email = $patient_data['email'] ?? '';

// Display payment success/error messages
if (isset($_SESSION['payment_error'])) {
    $message = $_SESSION['payment_error'];
    $messageType = "error";
    unset($_SESSION['payment_error']);
}

// Handle Post - Create appointment pending payment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_appointment'])) {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $issue = $_POST['issue'];
    $payment_gateway = $_POST['payment_gateway'] ?? 'razorpay';
    $status = 'Pending Payment';
    $payment_amount = APPOINTMENT_FEE;

    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time, initial_health_issue, status, payment_status, payment_amount) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
    if ($stmt) {
        $stmt->bind_param("issssd", $patient_id, $date, $time, $issue, $status, $payment_amount);
        if ($stmt->execute()) {
            $appointment_id = $conn->insert_id;
            // Store appointment details in session for payment
            $_SESSION['pending_appointment'] = [
                'id' => $appointment_id,
                'date' => $date,
                'time' => $time,
                'issue' => $issue,
                'amount' => $payment_amount,
                'gateway' => $payment_gateway
            ];
        } else {
            $message = "Error: " . $conn->error;
            $messageType = "error";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Razorpay Script -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    
    <style>
        /* Modern Professional Design */
        :root {
            --primary-color: #667eea;
            --primary-dark: #5568d3;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --error-color: #ef4444;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f9fafb;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-light);
            display: flex;
            margin: 0;
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .main-content { 
            padding: 30px 40px;
            width: 100%;
            margin-left: 240px;
            max-width: 1200px;
            margin-right: auto;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: var(--text-light);
            font-size: 16px;
        }
        
        .booking-container { 
            background: white;
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .booking-container:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        .form-grid { 
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-top: 24px;
        }
        
        .full-width { 
            grid-column: span 2;
        }
        
        .form-group {
            position: relative;
        }
        
        label { 
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        label i {
            color: var(--primary-color);
            font-size: 16px;
        }
        
        input, textarea, select { 
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            background: white;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            color: var(--text-dark);
            transition: all 0.3s ease;
            outline: none;
        }
        
        input:focus, textarea:focus, select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }
        
        input:disabled, textarea:disabled, select:disabled {
            background: var(--bg-light);
            color: var(--text-light);
            cursor: not-allowed;
            border-color: var(--border-color);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
        }
        
        select option:disabled { 
            color: #ccc;
        }
        
        #appointmentTime:disabled {
            background: var(--bg-light);
            cursor: not-allowed;
        }
        
        #slotMessage {
            display: block;
            margin-top: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        
        button { 
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 16px 24px;
            border-radius: var(--radius-md);
            width: 100%;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        button:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .alert { 
            padding: 16px 20px;
            margin-bottom: 24px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert.success { 
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success-color);
        }
        
        .alert.error { 
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--error-color);
        }
        
        .alert i {
            font-size: 20px;
        }
        
        .payment-info {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }
        
        .payment-info::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .payment-info h3 { 
            margin: 0 0 12px 0;
            font-size: 20px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .payment-info .amount { 
            font-size: 48px;
            font-weight: 700;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .payment-info p {
            margin: 12px 0 0 0;
            font-size: 14px;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }
        
        .gateway-select {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .gateway-option {
            padding: 20px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            background: white;
            position: relative;
        }
        
        .gateway-option:hover {
            border-color: var(--primary-color);
            background: #f0f9ff;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .gateway-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .gateway-option.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            box-shadow: var(--shadow-md);
        }
        
        .gateway-option.selected::after {
            content: '✓';
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--primary-color);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }
        
        .gateway-option i {
            font-size: 28px;
            color: var(--primary-color);
            margin-bottom: 8px;
            display: block;
        }
        
        .gateway-option .gateway-name {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-dark);
        }
        
        /* Hide all modals that might interfere */
        .success-modal, 
        .booking-modal,
        [id*="Modal"],
        [id*="modal"],
        [class*="success-popup"] {
            display: none !important;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 240px;
                padding: 20px 30px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .page-header h2 {
                font-size: 24px;
            }
            
            .booking-container {
                padding: 24px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .full-width {
                grid-column: span 1;
            }
            
            .payment-info {
                padding: 24px;
            }
            
            .payment-info .amount {
                font-size: 40px;
            }
            
            .gateway-select {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 16px;
            }
            
            .page-header h2 {
                font-size: 22px;
            }
            
            .booking-container {
                padding: 20px;
            }
            
            .payment-info .amount {
                font-size: 36px;
            }
            
            button {
                padding: 14px 20px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h2><i class="fas fa-calendar-plus"></i> Book Appointment</h2>
            <p>Schedule your consultation with our experienced psychiatrist</p>
        </div>
        
        <?php if($message): ?>
            <div class="alert <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <div class="payment-info">
            <h3><i class="fas fa-rupee-sign"></i> Consultation Fee: ₹<?php echo number_format(APPOINTMENT_FEE, 2); ?></h3>
            <p>Secure online payment • Instant confirmation</p>
        </div>

        <div class="booking-container">
            <form id="appointmentForm" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Select Date</label>
                        <input type="date" name="date" id="appointmentDate" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Preferred Time</label>
                        <select name="time" id="appointmentTime" required>
                            <option value="">Select a date first</option>
                        </select>
                        <span id="slotMessage"></span>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($display_name); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($display_email); ?>" disabled>
                    </div>
                    <div class="full-width form-group">
                        <label><i class="fas fa-notes-medical"></i> Describe Your Health Issue</label>
                        <textarea name="issue" id="healthIssue" rows="4" required placeholder="Please describe your symptoms, concerns, or reason for consultation..."></textarea>
                    </div>
                    
                    <div class="full-width form-group">
                        <label><i class="fas fa-credit-card"></i> Select Payment Gateway</label>
                        <div class="gateway-select">
                            <label class="gateway-option selected">
                                <input type="radio" name="payment_gateway" value="razorpay" checked>
                                <i class="fab fa-cc-visa"></i>
                                <div class="gateway-name">Razorpay</div>
                            </label>
                            <label class="gateway-option">
                                <input type="radio" name="payment_gateway" value="cashfree">
                                <i class="fas fa-wallet"></i>
                                <div class="gateway-name">Cashfree</div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="full-width">
                        <button type="button" onclick="handlePayment()" id="paymentButton">
                            <i class="fas fa-lock"></i>
                            <span>Proceed to Payment • ₹<?php echo number_format(APPOINTMENT_FEE, 2); ?></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Define available time slots (9 AM to 5 PM, 30-minute intervals)
        const availableTimeSlots = [
            '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
            '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
            '15:00', '15:30', '16:00', '16:30', '17:00'
        ];

        // Fetch and update available slots when date changes
        document.getElementById('appointmentDate').addEventListener('change', async function() {
            const selectedDate = this.value;
            const timeSelect = document.getElementById('appointmentTime');
            const slotMessage = document.getElementById('slotMessage');
            
            if (!selectedDate) {
                timeSelect.innerHTML = '<option value="">Select a date first</option>';
                slotMessage.textContent = '';
                return;
            }
            
            // Show loading
            timeSelect.innerHTML = '<option value="">Loading available slots...</option>';
            timeSelect.disabled = true;
            slotMessage.textContent = 'Checking availability...';
            
            try {
                // Fetch booked slots for the selected date
                const response = await fetch(`get_available_slots.php?date=${selectedDate}`);
                const data = await response.json();
                
                console.log('Available slots response:', data);
                console.log('Booked slots:', data.booked_slots);
                
                if (data.success) {
                    const bookedSlots = data.booked_slots || [];
                    
                    // Build options with available slots only
                    let options = '<option value="">-- Select Time Slot --</option>';
                    let availableCount = 0;
                    
                    availableTimeSlots.forEach(slot => {
                        const isBooked = bookedSlots.includes(slot);
                        console.log(`Slot ${slot}: ${isBooked ? 'BOOKED' : 'AVAILABLE'}`);
                        if (!isBooked) {
                            options += `<option value="${slot}">${formatTime(slot)}</option>`;
                            availableCount++;
                        }
                    });
                    
                    timeSelect.innerHTML = options;
                    timeSelect.disabled = false;
                    
                    // Update message
                    if (availableCount === 0) {
                        slotMessage.innerHTML = '<span style="color: #dc2626;">❌ No slots available for this date. Please select another date.</span>';
                        timeSelect.disabled = true;
                    } else {
                        slotMessage.innerHTML = `<span style="color: #059669;">✓ ${availableCount} slot(s) available</span>`;
                    }
                } else {
                    timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                    slotMessage.textContent = 'Error: ' + (data.message || 'Unable to load slots');
                }
            } catch (error) {
                console.error('Error fetching slots:', error);
                timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                slotMessage.innerHTML = '<span style="color: #dc2626;">Error loading available slots. Please try again.</span>';
            }
        });
        
        // Format time to 12-hour format with AM/PM
        function formatTime(time24) {
            const [hours, minutes] = time24.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }

        // Gateway selection
        document.querySelectorAll('.gateway-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.gateway-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Prevent any success modals from other pages
        document.addEventListener('DOMContentLoaded', function() {
            // Close any existing modals
            const existingModals = document.querySelectorAll('.modal, .success-modal, [class*="modal"]');
            existingModals.forEach(modal => {
                if(modal.style.display === 'flex' || modal.style.display === 'block') {
                    modal.style.display = 'none';
                }
            });
        });

        // Handle payment button click
        function handlePayment() {
            const form = document.getElementById('appointmentForm');
            
            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Get form data
            const formData = new FormData();
            formData.append('date', document.getElementById('appointmentDate').value);
            formData.append('time', document.getElementById('appointmentTime').value);
            formData.append('issue', document.getElementById('healthIssue').value);
            formData.append('payment_gateway', document.querySelector('input[name="payment_gateway"]:checked').value);

            // Disable button
            const button = document.getElementById('paymentButton');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating appointment...';

            // Submit via AJAX to create appointment
            fetch('create_appointment_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Appointment response:', data);
                
                if (data.success) {
                    // Appointment created successfully, now initiate payment
                    if (data.gateway === 'razorpay') {
                        initiateRazorpayPayment(data);
                    } else if (data.gateway === 'cashfree') {
                        initiateCashfreePayment(data);
                    }
                } else {
                    alert('Error: ' + data.message);
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-lock"></i> <span>Proceed to Payment • ₹<?php echo number_format(APPOINTMENT_FEE, 2); ?></span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-lock"></i> <span>Proceed to Payment • ₹<?php echo number_format(APPOINTMENT_FEE, 2); ?></span>';
            });
        }

        // Initiate Razorpay Payment
        function initiateRazorpayPayment(data) {
            if (typeof Razorpay === 'undefined') {
                alert('Payment gateway failed to load. Please refresh the page and try again.');
                return;
            }

            // First, create a Razorpay order on the server
            const orderData = new FormData();
            orderData.append('appointment_id', data.appointment_id);
            orderData.append('amount', data.amount);

            fetch('create_razorpay_order.php', {
                method: 'POST',
                body: orderData
            })
            .then(response => response.json())
            .then(orderResponse => {
                console.log('Razorpay order created:', orderResponse);
                
                if (!orderResponse.success) {
                    alert('Failed to create payment order: ' + orderResponse.message);
                    window.location.href = 'book_appointment.php';
                    return;
                }

                // Now open Razorpay modal with the order_id
                const razorpayOptions = {
                    key: data.razorpay_key,
                    amount: orderResponse.amount, // Amount in paise
                    currency: data.currency,
                    name: "Medical App - Psychiatrist",
                    description: "Appointment Booking Fee",
                    image: "https://cdn-icons-png.flaticon.com/512/2913/2913133.png",
                    order_id: orderResponse.order_id, // Proper Razorpay order ID
                    handler: function (response) {
                        console.log('Payment successful:', response);
                        // Send payment details to server
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'payment_callback.php';
                        
                        const fields = {
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_signature: response.razorpay_signature,
                            appointment_id: data.appointment_id
                        };
                        
                        for (const key in fields) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = key;
                            input.value = fields[key];
                            form.appendChild(input);
                        }
                        
                        document.body.appendChild(form);
                        form.submit();
                    },
                    prefill: {
                        name: data.patient.name,
                        email: data.patient.email,
                        contact: data.patient.phone
                    },
                    notes: {
                        appointment_id: data.appointment_id,
                        booking_type: "psychiatrist_consultation"
                    },
                    theme: {
                        color: "#7B3F00"
                    },
                    modal: {
                        ondismiss: function() {
                            console.log('Payment cancelled by user');
                            alert('Payment cancelled. Your appointment is not confirmed. Please complete the payment to confirm your booking.');
                            window.location.href = 'book_appointment.php';
                        }
                    }
                };
                
                console.log('Opening Razorpay modal with order:', razorpayOptions);
                const razorpay = new Razorpay(razorpayOptions);
                razorpay.on('payment.failed', function (response){
                    console.error('Payment failed:', response.error);
                    alert('Payment failed: ' + response.error.description);
                    window.location.href = 'book_appointment.php';
                });
                razorpay.open();
            })
            .catch(error => {
                console.error('Error creating Razorpay order:', error);
                alert('Failed to initialize payment. Please try again.');
                window.location.href = 'book_appointment.php';
            });
        }

        // Initiate Cashfree Payment (NEW Payment Gateway API)
        async function initiateCashfreePayment(data) {
            try {
                // Create Cashfree order using new API
                const orderResponse = await fetch('create_cashfree_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        appointment_id: data.appointment_id,
                        amount: data.amount,
                        customer_name: data.patient.name,
                        customer_email: data.patient.email,
                        customer_phone: data.patient.phone
                    })
                });

                const orderData = await orderResponse.json();
                console.log('Cashfree Order Response:', orderData);

                if (!orderData.success) {
                    showError(orderData.message || 'Failed to create Cashfree order');
                    return;
                }

                // Load Cashfree Checkout SDK
                const script = document.createElement('script');
                script.src = 'https://sdk.cashfree.com/js/v3/cashfree.js';
                script.onload = () => {
                    // Initialize Cashfree SDK
                    const cashfree = Cashfree({
                        mode: orderData.environment === 'PROD' ? 'production' : 'sandbox'
                    });

                    // Checkout options
                    const checkoutOptions = {
                        paymentSessionId: orderData.payment_session_id,
                        returnUrl: `http://${window.location.host}/psychiatrist_doctor/patient/payment_callback.php?appointment_id=${data.appointment_id}`
                    };

                    // Open checkout
                    cashfree.checkout(checkoutOptions).then((result) => {
                        if (result.error) {
                            console.error('Payment error:', result.error);
                            showError('Payment failed: ' + result.error.message);
                        }
                        if (result.redirect) {
                            console.log('Payment will be redirected');
                        }
                        if (result.paymentDetails) {
                            console.log('Payment completed:', result.paymentDetails);
                        }
                    });
                };
                document.head.appendChild(script);
            } catch (error) {
                console.error('Cashfree Error:', error);
                showError('Failed to initiate Cashfree payment. Please try again.');
            }
        }
    </script>
</body>
</html>