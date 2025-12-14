<?php
// FILE: psychiatrist/admin/meeting_info_notes.php
include '../config/db.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    // FIXED: Redirects to your specific filename (with space and typo)
    header("Location: manage appoinment.php"); 
    exit();
}

$appt_id = $_GET['id'];
$message = "";

// 1. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $notes = $_POST['internal_notes']; 
    $status = $_POST['status'];

    // Update Appointment Table
    $sql_appt = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
    $stmt = $conn->prepare($sql_appt);
    $stmt->bind_param("si", $status, $appt_id);
    $stmt->execute();

    // Handle Medical Records
    $check_sql = "SELECT record_id FROM medical_records WHERE appointment_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("i", $appt_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $sql_notes = "UPDATE medical_records SET internal_doctor_notes = ? WHERE appointment_id = ?";
        $stmt_notes = $conn->prepare($sql_notes);
        $stmt_notes->bind_param("si", $notes, $appt_id);
        $stmt_notes->execute();
    } else {
        $p_sql = "SELECT patient_id FROM appointments WHERE appointment_id = ?";
        $p_stmt = $conn->prepare($p_sql);
        $p_stmt->bind_param("i", $appt_id);
        $p_stmt->execute();
        $p_res = $p_stmt->get_result();
        $p_row = $p_res->fetch_assoc();
        $pid = $p_row['patient_id'];

        $sql_notes = "INSERT INTO medical_records (appointment_id, patient_id, internal_doctor_notes) VALUES (?, ?, ?)";
        $stmt_notes = $conn->prepare($sql_notes);
        $stmt_notes->bind_param("iis", $appt_id, $pid, $notes);
        $stmt_notes->execute();
    }

    $message = "Appointment updated successfully!";
}

// 2. Fetch Data
$sql = "SELECT a.*, p.full_name, p.email, m.internal_doctor_notes 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        LEFT JOIN medical_records m ON a.appointment_id = m.appointment_id
        WHERE a.appointment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appt_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) { die("Appointment not found."); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointment Info</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0ea5e9; --primary-dark: #0284c7; --bg-body: #f1f5f9; --card-bg: #ffffff; --text-main: #334155; }
        * { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); padding: 2rem; display: flex; justify-content: center; }
        
        .container { background: var(--card-bg); width: 100%; max-width: 600px; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; }
        .back-btn { text-decoration: none; color: #64748b; font-weight: bold; }
        
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        input[type="text"], textarea, select { width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; }
        input:focus, textarea:focus { border-color: var(--primary); }
        
        .btn-save { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 1rem; width: 100%; }
        .btn-save:hover { background: var(--primary-dark); }
        
        .alert { background: #dcfce7; color: #166534; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; text-align: center; }
        
        .patient-details { background: #f8fafc; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        .patient-details p { margin-bottom: 0.5rem; font-size: 0.9rem; }
        
        /* Video info box */
        .video-info { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 10px; border-radius: 6px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h2>Meeting Info & Notes</h2>
            <a href="manage appoinment.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>

        <?php if($message): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="patient-details">
            <p><strong>Patient:</strong> <?php echo htmlspecialchars($data['full_name']); ?></p>
            <p><strong>Date:</strong> <?php echo $data['appointment_date']; ?> at <?php echo $data['appointment_time']; ?></p>
            <p><strong>Initial Issue:</strong> <?php echo htmlspecialchars($data['initial_health_issue']); ?></p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Appointment Status</label>
                <select name="status">
                    <option value="Pending" <?php if($data['status']=='Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Confirmed" <?php if($data['status']=='Confirmed') echo 'selected'; ?>>Confirmed</option>
                    <option value="Cancelled" <?php if($data['status']=='Cancelled') echo 'selected'; ?>>Cancelled</option>
                    <option value="Completed" <?php if($data['status']=='Completed') echo 'selected'; ?>>Completed</option>
                </select>
            </div>

            <div class="form-group">
                <label>Video Consultation</label>
                <div class="video-info">
                    <i class="fa-solid fa-video"></i>
                    <span>System automatically generates meeting room.</span>
                    <a href="../meeting.php?id=<?php echo $appt_id; ?>" target="_blank" style="margin-left:auto; text-decoration:none; color: #059669; font-weight:bold;">Join Now &rarr;</a>
                </div>
            </div>

            <div class="form-group">
                <label>Doctor's Internal Notes</label>
                <textarea name="internal_notes" rows="5" placeholder="Write diagnosis or notes here..."><?php echo htmlspecialchars($data['internal_doctor_notes'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn-save">Save Details</button>
        </form>
    </div>

</body>
</html>