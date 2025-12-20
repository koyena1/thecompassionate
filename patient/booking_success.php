<?php 
session_start();
include '../config/db.php';

if (!isset($_SESSION['payment_success']) || !isset($_GET['id'])) {
    header("Location: book_appointment.php");
    exit();
}

$appointment_id = $_GET['id'];

// Get appointment details
$result = $conn->query("SELECT a.*, p.full_name, p.email 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.patient_id 
    WHERE a.appointment_id = $appointment_id");
$appointment = $result->fetch_assoc();

unset($_SESSION['payment_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .success-container {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out;
        }
        .success-icon i {
            color: white;
            font-size: 40px;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        .detail-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .btn-primary {
            background: #7B3F00;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: #5d2f00;
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h1>Payment Successful!</h1>
        <p>Your appointment has been confirmed and invoice has been sent to your email.</p>
        
        <div class="detail-box">
            <div class="detail-row">
                <span><strong>Invoice Number:</strong></span>
                <span><?php echo htmlspecialchars($appointment['invoice_number']); ?></span>
            </div>
            <div class="detail-row">
                <span><strong>Appointment Date:</strong></span>
                <span><?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span><strong>Appointment Time:</strong></span>
                <span><?php echo htmlspecialchars($appointment['appointment_time']); ?></span>
            </div>
            <div class="detail-row">
                <span><strong>Amount Paid:</strong></span>
                <span>₹<?php echo number_format($appointment['payment_amount'], 2); ?></span>
            </div>
            <div class="detail-row">
                <span><strong>Payment Gateway:</strong></span>
                <span><?php echo htmlspecialchars($appointment['payment_gateway']); ?></span>
            </div>
            <div class="detail-row">
                <span><strong>Status:</strong></span>
                <span style="color: #10b981; font-weight: 600;">Confirmed</span>
            </div>
        </div>
        
        <p style="font-size: 14px; color: #6b7280;">Check your email for the complete invoice and appointment details.</p>
        
        <a href="myapp.php" class="btn-primary">View My Appointments</a>
        <a href="dashboard.php" class="btn-primary" style="background: #6b7280; margin-left: 10px;">Back to Dashboard</a>
    </div>
</body>
</html>
