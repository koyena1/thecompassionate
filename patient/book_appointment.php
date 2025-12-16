<?php 
// FILE: psychiatrist/patient/book_appointment.php
include '../config/db.php'; 
session_start();

$patient_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$message = "";
$messageType = "";
$popupMessage = ""; // Variable to trigger JS popup

// Get Patient Info
$res = $conn->query("SELECT * FROM patients WHERE patient_id = '$patient_id'");
$patient_data = $res->fetch_assoc();
$display_name = $patient_data['full_name'] ?? 'Patient';
$display_email = $patient_data['email'] ?? '';

// Define the 2-hour gap slots (Database Time => Display Time)
$time_slots = [
    "10:00:00" => "10:00 AM - 12:00 PM",
    "13:00:00" => "01:00 PM - 03:00 PM",
    "15:00:00" => "03:00 PM - 05:00 PM",
    "17:00:00" => "05:00 PM - 07:00 PM"
];

// Handle Post
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $time = $_POST['time']; // This will be the start time (e.g., 10:00:00)
    $issue = $_POST['issue'];
    $status = 'Pending'; 

    // 1. Check if the SPECIFIC slot is booked
    $checkStmt = $conn->prepare("SELECT appointment_id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'Cancelled'");
    $checkStmt->bind_param("ss", $date, $time);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // --- SLOT IS TAKEN: FIND SUGGESTIONS ---
        
        // A. Get ALL booked times for this specific date
        $bookedStmt = $conn->prepare("SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status != 'Cancelled'");
        $bookedStmt->bind_param("s", $date);
        $bookedStmt->execute();
        $result = $bookedStmt->get_result();
        
        $booked_times = [];
        while ($row = $result->fetch_assoc()) {
            $booked_times[] = $row['appointment_time']; // Stores '10:00:00', etc.
        }
        $bookedStmt->close();

        // B. Calculate which slots are FREE
        $available_suggestions = [];
        foreach ($time_slots as $slot_time => $slot_label) {
            // If the slot_time is NOT in the booked_times array, it is free
            if (!in_array($slot_time, $booked_times)) {
                $available_suggestions[] = $slot_label;
            }
        }

        // C. Build the Popup Message
        $popupMessage = "This time slot is already booked!";
        
        if (!empty($available_suggestions)) {
            $popupMessage .= "\\n\\nHere are the available slots for " . $date . ":\\n- " . implode("\\n- ", $available_suggestions);
        } else {
            $popupMessage .= "\\n\\nSorry, fully booked! No slots available for this date.";
        }

        $message = "Slot unavailable. Please check the popup for available times.";
        $messageType = "error";

    } else {
        // 2. Slot is free, proceed to insert
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
    $checkStmt->close();
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
        // We use javascript alert to show the message constructed in PHP
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
                        <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label>Preferred Time Slot</label>
                        <select name="time" required>
                            <option value="">Select a time slot</option>
                            <?php foreach($time_slots as $time_value => $label): ?>
                                <option value="<?php echo $time_value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
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