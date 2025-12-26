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

// --- DARK MODE LOGIC ---
if(!isset($_SESSION['pref_dark_mode'])) {
    if(isset($_SESSION['user_id'])) {
        $p_id = $_SESSION['user_id'];
        $res_pref = $conn->query("SELECT pref_dark_mode FROM patients WHERE patient_id = '$p_id'");
        if($res_pref && $row_pref = $res_pref->fetch_assoc()) {
            $_SESSION['pref_dark_mode'] = $row_pref['pref_dark_mode'];
        }
    }
}
$dark_class = (isset($_SESSION['pref_dark_mode']) && $_SESSION['pref_dark_mode'] == 1) ? 'dark-mode' : '';

// Get Patient Info
$res = $conn->query("SELECT * FROM patients WHERE patient_id = '$patient_id'");
$patient_data = $res->fetch_assoc();
$display_name = $patient_data['full_name'] ?? 'Patient';
$display_email = $patient_data['email'] ?? '';
$display_height = $patient_data['height'] ?? '';
$display_weight = $patient_data['weight'] ?? '';

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
    
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    
    <style>
        /* Premium Professional Design */
        :root {
            --primary-color: #667eea;
            --primary-dark: #5568d3;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --error-color: #ef4444;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f5f7fa;
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            --border-color: #e5e7eb;
            --white: #FFFFFF;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(102, 126, 234, 0.04);
            --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --sidebar-width: 260px;
        }

        /* --- DARK MODE OVERRIDES --- */
        body.dark-mode {
            --bg-light: #0F172A;
            --bg-gradient: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            --text-dark: #F8FAFC;
            --text-light: #94A3B8;
            --white: #1E293B;
            --border-color: #2D3A4F;
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-gradient);
            display: flex;
            margin: 0;
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* --- SIDEBAR STYLING --- */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #7B3F00 0%, #4a2600 100%);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            z-index: 1001;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            left: 0; top: 0;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }

        .main-content { 
            padding: 40px 50px;
            width: calc(100% - var(--sidebar-width));
            margin-left: var(--sidebar-width);
            flex: 1;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1), width 0.4s ease;
            animation: fadeIn 0.6s ease;
        }

        /* Laptop: Sidebar off state */
        body.sidebar-off .sidebar { transform: translateX(-100%); }
        body.sidebar-off .main-content { margin-left: 0; width: 100%; }

        /* Mobile: Sidebar overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            backdrop-filter: blur(2px);
        }

        /* Sidebar Close Button */
        .sidebar-close-btn {
            position: absolute;
            top: 20px; right: 20px; width: 32px; height: 32px;
            border-radius: 50%; background: rgba(255, 255, 255, 0.2);
            color: white; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; transition: all 0.3s ease; z-index: 1002;
        }
        .sidebar-close-btn:hover { background: rgba(255, 255, 255, 0.3); transform: rotate(90deg); }

        /* HAMBURGER BUTTON */
        #toggle-btn {
            font-size: 20px;
            cursor: pointer;
            color: var(--text-dark);
            background: var(--white);
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: none;
        }
        #toggle-btn:hover { transform: scale(1.05); }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 35px;
            position: relative;
        }
        
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 60px;
            width: 80px;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }
        
        .page-header h2 {
            font-size: 34px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
            letter-spacing: -0.5px;
        }
        
        .page-header p {
            margin-top: 5px;
            font-size: 15px;
        }
        
        .payment-info {
            background: var(--primary-gradient);
            padding: 24px 30px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-xl);
            animation: slideInDown 0.6s ease;
        }
        
        .payment-info h3 {
            color: white;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .payment-info h3 i {
            font-size: 28px;
        }
        
        .payment-info p {
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .payment-info p::before {
            content: '•';
            font-size: 20px;
        }
        
        .booking-container { 
            background: var(--white);
            padding: 45px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.6s ease;
            position: relative;
            overflow: hidden;
        }
        
        .booking-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--primary-gradient);
        }
        
        .form-grid { 
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 28px;
        }
        
        .full-width { grid-column: span 2; }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label { 
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.3px;
        }
        
        label i {
            color: var(--primary-color);
            font-size: 16px;
        }
        
        input, textarea, select { 
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--white);
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            color: var(--text-dark);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }
        
        input:hover, textarea:hover, select:hover {
            border-color: #cbd5e1;
        }
        
        input:focus, textarea:focus, select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.12);
            transform: translateY(-2px);
        }
        
        input:disabled {
            background: #f1f5f9;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
            line-height: 1.6;
        }
        
        #slotMessage {
            display: block;
            margin-top: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .gateway-select {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        
        .gateway-option {
            background: var(--white);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }
        
        .gateway-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .gateway-option:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow-card);
            transform: translateY(-3px);
        }
        
        .gateway-option.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .gateway-option.selected::before {
            transform: scaleY(1);
        }
        
        .gateway-option input[type="radio"] {
            width: 20px;
            height: 20px;
            margin: 0;
            cursor: pointer;
            accent-color: var(--primary-color);
        }
        
        .gateway-option i {
            font-size: 28px;
            color: var(--primary-color);
        }
        
        .gateway-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-dark);
        }
        
        #paymentButton {
            width: 100%;
            padding: 18px 32px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }
        
        #paymentButton::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        #paymentButton:hover::before {
            left: 100%;
        }
        
        #paymentButton:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        
        #paymentButton:active {
            transform: translateY(-1px);
        }
        
        #paymentButton:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        #paymentButton i {
            font-size: 18px;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideInDown 0.4s ease;
        }
        
        .alert.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left: 4px solid var(--success-color);
            color: #065f46;
        }
        
        .alert.error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid var(--error-color);
            color: #991b1b;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .mobile-sidebar-on .sidebar { transform: translateX(0); }
            .mobile-sidebar-on .sidebar-overlay { display: block; }
            .main-content { margin-left: 0; width: 100%; padding: 20px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .page-header::after { left: 40px; }
            .page-header h2 { font-size: 26px; }
            .booking-container { padding: 25px; }
            .form-grid { grid-template-columns: 1fr; gap: 20px; }
            .full-width { grid-column: span 1; }
            .gateway-select { grid-template-columns: 1fr; }
            .payment-info { flex-direction: column; align-items: flex-start; gap: 12px; padding: 20px; }
            .payment-info h3 { font-size: 20px; }
        }
    </style>
</head>
<body class="<?php echo $dark_class; ?>">

    <div class="sidebar-overlay" id="overlay"></div>

    <div class="sidebar" id="sidebar-container">
        <?php include 'sidebar.php'; ?>
        <button class="sidebar-close-btn" id="sidebar-close-btn">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>

    <main class="main-content">
        <div class="page-header">
            <button id="toggle-btn" title="Toggle Sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div>
                <h2>Book Appointment</h2>
                <p style="color: var(--text-light);">Schedule your consultation with our experienced psychiatrist</p>
            </div>
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
                        <label><i class="fas fa-ruler-vertical"></i> Height</label>
                        <input type="text" name="height" id="height" value="<?php echo htmlspecialchars($display_height); ?>" placeholder="Enter height">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-weight-hanging"></i> Weight (kg)</label>
                        <input type="text" name="weight" id="weight" value="<?php echo htmlspecialchars($display_weight); ?>" placeholder="Enter weight">
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
        const toggleBtn = document.getElementById('toggle-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const overlay = document.getElementById('overlay');
        const body = document.body;

        // Toggle Function
        toggleBtn.addEventListener('click', function() {
            if (window.innerWidth > 768) {
                body.classList.toggle('sidebar-off');
            } else {
                body.classList.toggle('mobile-sidebar-on');
            }
        });

        // Close sidebar
        function closeSidebar() {
            if (window.innerWidth > 768) {
                body.classList.add('sidebar-off');
            } else {
                body.classList.remove('mobile-sidebar-on');
            }
        }

        if(closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if(overlay) overlay.addEventListener('click', closeSidebar);

        // Define available time slots
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
            
            timeSelect.innerHTML = '<option value="">Loading available slots...</option>';
            timeSelect.disabled = true;
            slotMessage.textContent = 'Checking availability...';
            
            try {
                const response = await fetch(`get_available_slots.php?date=${selectedDate}`);
                const data = await response.json();
                
                if (data.success) {
                    const bookedSlots = data.booked_slots || [];
                    let options = '<option value="">-- Select Time Slot --</option>';
                    let availableCount = 0;
                    
                    availableTimeSlots.forEach(slot => {
                        const isBooked = bookedSlots.includes(slot);
                        if (!isBooked) {
                            options += `<option value="${slot}">${formatTime(slot)}</option>`;
                            availableCount++;
                        }
                    });
                    
                    timeSelect.innerHTML = options;
                    timeSelect.disabled = false;
                    
                    if (availableCount === 0) {
                        slotMessage.innerHTML = '<span style="color: #ef4444;">❌ No slots available for this date.</span>';
                        timeSelect.disabled = true;
                    } else {
                        slotMessage.innerHTML = `<span style="color: #10b981;">✓ ${availableCount} slot(s) available</span>`;
                    }
                } else {
                    timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                    slotMessage.textContent = 'Error: ' + (data.message || 'Unable to load slots');
                }
            } catch (error) {
                console.error('Error fetching slots:', error);
                timeSelect.innerHTML = '<option value="">Error loading slots</option>';
                slotMessage.innerHTML = '<span style="color: #ef4444;">Error loading available slots.</span>';
            }
        });
        
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

        // Handle payment button click
        function handlePayment() {
            const form = document.getElementById('appointmentForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData();
            formData.append('date', document.getElementById('appointmentDate').value);
            formData.append('time', document.getElementById('appointmentTime').value);
            formData.append('issue', document.getElementById('healthIssue').value);
            formData.append('payment_gateway', document.querySelector('input[name="payment_gateway"]:checked').value);

            const button = document.getElementById('paymentButton');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            fetch('create_appointment_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.gateway === 'razorpay') {
                        initiateRazorpayPayment(data);
                    } else if (data.gateway === 'cashfree') {
                        initiateCashfreePayment(data);
                    }
                } else {
                    alert('Error: ' + data.message);
                    button.disabled = false;
                    button.innerHTML = '<span>Proceed to Payment</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                button.disabled = false;
            });
        }

        function initiateRazorpayPayment(data) {
            const orderData = new FormData();
            orderData.append('appointment_id', data.appointment_id);
            orderData.append('amount', data.amount);

            fetch('create_razorpay_order.php', {
                method: 'POST',
                body: orderData
            })
            .then(response => response.json())
            .then(orderResponse => {
                const razorpayOptions = {
                    key: data.razorpay_key,
                    amount: orderResponse.amount,
                    currency: data.currency,
                    name: "Medical App",
                    order_id: orderResponse.order_id,
                    handler: function (response) {
                        // Submit to callback
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
                    prefill: { name: data.patient.name, email: data.patient.email, contact: data.patient.phone },
                    theme: { color: "#7B3F00" }
                };
                const razorpay = new Razorpay(razorpayOptions);
                razorpay.open();
            });
        }

        async function initiateCashfreePayment(data) {
            try {
                console.log('Initiating Cashfree payment...', data);
                
                const orderResponse = await fetch('create_cashfree_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        appointment_id: data.appointment_id,
                        amount: data.amount,
                        customer_name: data.patient.name,
                        customer_email: data.patient.email,
                        customer_phone: data.patient.phone
                    })
                });
                
                const orderData = await orderResponse.json();
                console.log('Cashfree order response:', orderData);
                
                if (!orderData.success) {
                    throw new Error(orderData.message || 'Failed to create order');
                }
                
                // Check if Cashfree SDK is loaded
                if (typeof Cashfree === 'undefined') {
                    throw new Error('Cashfree SDK not loaded');
                }
                
                console.log('Initializing Cashfree SDK with environment:', orderData.environment);
                
                // Initialize Cashfree with correct environment
                const cashfree = Cashfree({ 
                    mode: orderData.environment === 'PROD' ? 'production' : 'sandbox' 
                });
                
                // Get current path dynamically
                const returnUrl = window.location.origin + window.location.pathname.replace('book_appointment.php', 'payment_callback.php') + '?appointment_id=' + data.appointment_id;
                
                console.log('Opening Cashfree checkout with return URL:', returnUrl);
                
                // Open checkout
                cashfree.checkout({
                    paymentSessionId: orderData.payment_session_id,
                    returnUrl: returnUrl
                });
                
            } catch (error) {
                console.error('Cashfree Error:', error);
                alert('Payment initialization failed: ' + error.message);
                const button = document.getElementById('paymentButton');
                button.disabled = false;
                button.innerHTML = '<span>Proceed to Payment</span>';
            }
        }
    </script>
</body>
</html>