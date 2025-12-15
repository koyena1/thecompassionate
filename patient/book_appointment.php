<?php 
// FILE: psychiatrist/patient/book_appointment.php
include '../config/db.php'; 
session_start();

$patient_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$message = "";
$messageType = "";

// Get Patient Info
$res = $conn->query("SELECT * FROM patients WHERE patient_id = '$patient_id'");
$patient_data = $res->fetch_assoc();
$display_name = $patient_data['full_name'] ?? 'Patient';
$display_email = $patient_data['email'] ?? '';

// Handle Post
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $time = $_POST['time']; // Manual Time Input
    $issue = $_POST['issue'];
    $status = 'Pending'; 

    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time, initial_health_issue, status) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $patient_id, $date, $time, $issue, $status);
        if ($stmt->execute()) {
            $message = "Appointment booked successfully!";
            $messageType = "success";
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
    <style>
        /* Simplified styles */
        body { font-family: 'Poppins', sans-serif; background: #F5F6FA; display: flex; }
        .main-content { padding: 40px; width: 100%; margin: 0 auto; max-width: 900px; }
        .booking-container { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }
        input, textarea { width: 100%; padding: 12px; border: 1px solid #eee; border-radius: 10px; background: #fafafa; }
        button { background: #1FB6FF; color: white; border: none; padding: 15px; border-radius: 12px; width: 100%; cursor: pointer; font-weight: 600; margin-top: 10px; }
        button:hover { background: #0d9adb; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 10px; text-align: center; }
        .alert.success { background: #d1fae5; color: #065f46; }
        .alert.error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <main class="main-content">
        <h2>Book Appointment</h2>
        
        <?php if($message): ?><div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div><?php endif; ?>

        <div class="booking-container">
            <form method="POST">
                <div class="form-grid">
                    <div>
                        <label>Select Date</label>
                        <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label>Preferred Time</label>
                        <input type="time" name="time" required>
                    </div>
                    <div>
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($display_name); ?>" disabled>
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="text" value="<?php echo htmlspecialchars($display_email); ?>" disabled>
                    </div>
                    <div class="full-width">
                        <label>Health Issue</label>
                        <textarea name="issue" rows="4" required placeholder="Describe your issue..."></textarea>
                    </div>
                    <div class="full-width">
                        <button type="submit">Confirm Appointment</button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</body>
</html>