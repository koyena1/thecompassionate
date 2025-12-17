<?php 
// FILE: psychiatrist/patient/book_appointment.php
include '../config/db.php'; 
session_start();

$patient_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$message = "";
$messageType = "";
$popupMessage = ""; 

// Get Patient Info
$res = $conn->query("SELECT * FROM patients WHERE patient_id = '$patient_id'");
$patient_data = $res->fetch_assoc();
$display_name = $patient_data['full_name'] ?? 'Patient';
$display_email = $patient_data['email'] ?? '';

// --- 1. DEFINE ALL POSSIBLE SLOTS ---
$all_time_slots = [
    "10:00:00" => "10:00 AM - 12:00 PM",
    "13:00:00" => "01:00 PM - 03:00 PM",
    "15:00:00" => "03:00 PM - 05:00 PM",
    "17:00:00" => "05:00 PM - 07:00 PM"
];

// --- 2. DETERMINE SELECTED DATE ---
// Priority: POST (if form submitted) -> GET (if date changed) -> Today
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['date'])) {
    $selected_date = $_POST['date'];
} elseif (isset($_GET['date'])) {
    $selected_date = $_GET['date'];
} else {
    $selected_date = date('Y-m-d');
}

// --- 3. FETCH BOOKED SLOTS FOR SELECTED DATE ---
$booked_slots = [];
$slotStmt = $conn->prepare("SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status != 'Cancelled'");
$slotStmt->bind_param("s", $selected_date);
$slotStmt->execute();
$slotResult = $slotStmt->get_result();

while ($row = $slotResult->fetch_assoc()) {
    $booked_slots[] = $row['appointment_time'];
}
$slotStmt->close();

// --- 4. FILTER AVAILABLE SLOTS ---
// Create a new array containing ONLY slots that are NOT in the database
$available_slots = [];
foreach ($all_time_slots as $time_value => $label) {
    if (!in_array($time_value, $booked_slots)) {
        $available_slots[$time_value] = $label;
    }
}

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $issue = $_POST['issue'];
    $status = 'Pending'; 

    // Double check availability (Security measure)
    if (in_array($time, $booked_slots)) {
        $popupMessage = "Sorry, this slot was just booked by someone else! Please choose another.";
        $message = "Slot unavailable.";
        $messageType = "error";
    } else {
        // Slot is free, proceed to insert
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time, initial_health_issue, status) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $patient_id, $date, $time, $issue, $status);
            if ($stmt->execute()) {
                $message = "Appointment booked successfully!";
                $messageType = "success";
                // Refresh booked slots to hide the one just booked immediately
                $booked_slots[] = $time; 
                unset($available_slots[$time]);
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "error";
            }
            $stmt->close();
        }
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
        body { font-family: 'Poppins', sans-serif; background: #F5F6FA; display: flex; }
        .main-content { padding: 40px; width: 100%; margin: 0 auto; max-width: 900px; }
        .booking-container { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }
        input, textarea, select { width: 100%; padding: 12px; border: 1px solid #eee; border-radius: 10px; background: #fafafa; box-sizing: border-box; }
        button { background: #1FB6FF; color: white; border: none; padding: 15px; border-radius: 12px; width: 100%; cursor: pointer; font-weight: 600; margin-top: 10px; }
        button:hover { background: #0d9adb; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 10px; text-align: center; }
        .alert.success { background: #d1fae5; color: #065f46; }
        .alert.error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    
    <?php if (!empty($popupMessage)): ?>
    <script>
        alert("<?php echo $popupMessage; ?>");
    </script>
    <?php endif; ?>

    <main class="main-content">
        <h2>Book Appointment</h2>
        
        <?php if($message): ?><div class="alert <?php echo $messageType; ?>"><?php echo $message; ?></div><?php endif; ?>

        <div class="booking-container">
            <form method="POST">
                <div class="form-grid">
                    <div>
                        <label>Select Date</label>
                        <input type="date" 
                               name="date" 
                               required 
                               min="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo $selected_date; ?>" 
                               onchange="window.location.href='?date='+this.value">
                    </div>
                    <div>
                        <label>Preferred Time Slot</label>
                        <select name="time" required>
                            <option value="">Select a time slot</option>
                            <?php if(empty($available_slots)): ?>
                                <option value="" disabled>No slots available for this date</option>
                            <?php else: ?>
                                <?php foreach($available_slots as $time_value => $label): ?>
                                    <option value="<?php echo $time_value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
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