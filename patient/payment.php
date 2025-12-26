<?php 
include '../config/db.php'; 
session_start();

// --- CHECK LOGIN ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id']; 

// --- FETCH PATIENT DETAILS (Photo & Name) ---
$sql_patient = "SELECT * FROM patients WHERE patient_id = '$patient_id'";
$result_patient = $conn->query($sql_patient);
$patient_data = $result_patient->fetch_assoc();

// 1. Prepare Name
$display_name = !empty($patient_data['full_name']) ? $patient_data['full_name'] : 'Patient';

// 2. Prepare Image (Logic to use uploaded photo or fallback)
$db_image = !empty($patient_data['profile_image']) ? $patient_data['profile_image'] : 'https://i.pravatar.cc/150?img=33';
$display_img = $db_image . "?v=" . time(); // Add timestamp to force refresh

// --- FETCH PAYMENT HISTORY (Successful payments only) ---
$sql_payments = "SELECT * FROM appointments 
                 WHERE patient_id = '$patient_id' AND payment_status IN ('completed', 'paid') 
                 ORDER BY payment_date DESC";
$result_payments = $conn->query($sql_payments);

// Calculate total paid amount
$total_paid = 0;
$payment_count = 0;
if ($result_payments && $result_payments->num_rows > 0) {
    $result_payments->data_seek(0);
    while ($row = $result_payments->fetch_assoc()) {
        $total_paid += floatval($row['payment_amount']);
        $payment_count++;
    }
    $result_payments->data_seek(0); // Reset pointer
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- CONSISTENT DASHBOARD STYLES --- */
        :root {
            --bg-color: #F5F6FA;
            --sidebar-width: 240px;
            --primary-purple: #7B61FF;
            --primary-red: #FF5C60;
            --primary-orange: #FFB800;
            --primary-blue: #1FB6FF;
            --primary-green: #00B69B;
            --text-dark: #2D3436;
            --text-light: #A0A4A8;
            --white: #FFFFFF;
            --shadow: 0 4px 15px rgba(0,0,0,0.03);
            --radius: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            background: #7B3F00;
            padding: 30px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            left: 0;
            top: 0;
        }

        .menu-item {
            display: flex; align-items: center; gap: 15px; padding: 15px;
            color: var(--text-light); text-decoration: none; border-radius: 12px;
            margin-bottom: 5px; transition: 0.3s; font-size: 14px;
        }
        .menu-item:hover, .menu-item.active { background-color: var(--text-dark); color: var(--white); }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px 40px;
            width: 100%;
        }

        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .welcome-text h1 { font-size: 24px; font-weight: 600; }

        /* User Profile in Header */
        .user-profile { display: flex; align-items: center; gap: 20px; }
        .profile-info { display: flex; align-items: center; gap: 10px; }
        .profile-info img { width: 45px; height: 45px; border-radius: 12px; object-fit: cover; }

        /* --- STATS CARDS (Reused) --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            padding: 25px;
            border-radius: var(--radius);
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .card:hover { transform: translateY(-8px); }

        .card-icon {
            background: rgba(255,255,255,0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .card-info h2 { font-size: 28px; }
        .card-info span { font-size: 13px; opacity: 0.9; }
        .card.blue { background: var(--primary-blue); }
        .card.green { background: var(--primary-green); }

        /* --- PAYMENTS & BILLING STYLES --- */
        .payment-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--white);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            border: 1px solid #eee;
        }

        .bill-info h4 { font-size: 15px; color: var(--text-dark); }
        .bill-info p { font-size: 12px; color: var(--text-light); margin-top: 3px; }

        .bill-amount {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .pay-now-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            transition: 0.3s;
        }

        .pay-now-btn:hover { background: #008f7a; }

        .status-paid-text {
            color: var(--primary-green);
            font-weight: 500;
            font-size: 13px;
            background: rgba(0, 182, 155, 0.1);
            padding: 5px 10px;
            border-radius: 5px;
        }

        /* --- PAYMENT MODAL (POPUP) STYLES --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none; /* Hidden by default */
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }

        .modal-content {
            background: var(--white);
            width: 400px;
            padding: 30px;
            border-radius: 20px;
            position: relative;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            font-size: 20px;
            color: var(--text-light);
        }

        .cc-visual {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .cc-input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            outline: none;
        }
        
        .cc-row { display: flex; gap: 15px; }

        .pay-confirm-btn {
            width: 100%;
            background: var(--text-dark);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        .pay-confirm-btn:hover { background: black; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div class="welcome-text">
                <h1>Payment History</h1>
                <p>View all your successful payment transactions</p>
            </div>
            
            <div class="user-profile">
                <div class="profile-info">
                    <div style="text-align: right;">
                        <h4 style="font-size: 14px;"><?php echo htmlspecialchars($display_name); ?></h4>
                        <p style="font-size: 11px; color: var(--text-light);">Patient</p>
                    </div>
                    <img src="<?php echo htmlspecialchars($display_img); ?>" alt="Patient Profile">
                </div>
            </div>
        </header>

        <section class="stats-grid" style="margin-bottom: 30px;">
            <div class="card green" style="background: #00B69B;">
                <div class="card-icon"><i class="fa-solid fa-check-double"></i></div>
                <div class="card-info">
                    <h2>₹<?php echo number_format($total_paid, 2); ?></h2>
                    <span>Total Paid</span>
                </div>
            </div>
            <div class="card blue">
                <div class="card-icon"><i class="fa-solid fa-receipt"></i></div>
                <div class="card-info">
                    <h2><?php echo $payment_count; ?></h2>
                    <span>Successful Payments</span>
                </div>
            </div>
        </section>

        <h3 style="margin-bottom: 15px; font-size: 18px;">All Successful Payments</h3>
        <div class="history-list">
            <?php 
            if ($result_payments && $result_payments->num_rows > 0) {
                while ($payment = $result_payments->fetch_assoc()) {
                    $payment_date = date('d M Y', strtotime($payment['payment_date']));
                    $payment_time = date('h:i A', strtotime($payment['payment_date']));
                    $appointment_date = date('d M Y', strtotime($payment['appointment_date']));
                    $payment_gateway = !empty($payment['payment_gateway']) ? ucfirst($payment['payment_gateway']) : 'Online Payment';
                    $transaction_id = !empty($payment['payment_id']) ? $payment['payment_id'] : (!empty($payment['transaction_id']) ? $payment['transaction_id'] : 'N/A');
                    // Truncate transaction ID if too long
                    if (strlen($transaction_id) > 25) {
                        $transaction_id = substr($transaction_id, 0, 25) . '...';
                    }
            ?>
            <div class="payment-row">
                <div class="bill-info">
                    <h4>Appointment Consultation Fee</h4>
                    <p>Appointment ID: #APT-<?php echo $payment['appointment_id']; ?> • <?php echo $appointment_date; ?></p>
                    <p style="font-size: 11px; color: #999; margin-top: 3px;">Transaction ID: <?php echo htmlspecialchars($transaction_id); ?></p>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <span class="bill-amount">₹<?php echo number_format($payment['payment_amount'], 2); ?></span>
                    <span class="status-paid-text"><i class="fa-solid fa-check"></i> Paid</span>
                    <p style="font-size: 11px; color: var(--text-light); margin: 0;"><?php echo $payment_date; ?> • <?php echo $payment_time; ?></p>
                    <p style="font-size: 11px; color: var(--text-light); margin: 0;">via <?php echo $payment_gateway; ?></p>
                </div>
            </div>
            <?php 
                }
            } else {
            ?>
            <div class="payment-row" style="text-align: center; padding: 40px;">
                <div style="margin: 0 auto;">
                    <i class="fa-solid fa-receipt" style="font-size: 48px; color: var(--text-light); margin-bottom: 15px;"></i>
                    <h4 style="color: var(--text-light);">No payment history found</h4>
                    <p style="color: var(--text-light); font-size: 13px;">Your successful payments will appear here</p>
                </div>
            </div>
            <?php 
            }
            ?>
        </div>

    </main>


</body>
</html>