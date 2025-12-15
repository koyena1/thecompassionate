<?php
// FILE: psychiatrist/admin/meeting_info_notes.php
include '../config/db.php';

if (!isset($_GET['id'])) {
    // FIXED LINK: manage appoinments.php
    header("Location: manage appoinments.php"); 
    exit();
}

$appt_id = $_GET['id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $notes = $_POST['internal_notes']; 
    $status = $_POST['status'];

    $conn->query("UPDATE appointments SET status = '$status' WHERE appointment_id = $appt_id");

    // Check if record exists
    $check = $conn->query("SELECT record_id FROM medical_records WHERE appointment_id = $appt_id");
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE medical_records SET internal_doctor_notes = ? WHERE appointment_id = ?");
        $stmt->bind_param("si", $notes, $appt_id);
        $stmt->execute();
    } else {
        $p_res = $conn->query("SELECT patient_id FROM appointments WHERE appointment_id = $appt_id");
        $pid = $p_res->fetch_assoc()['patient_id'];
        $stmt = $conn->prepare("INSERT INTO medical_records (appointment_id, patient_id, internal_doctor_notes) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $appt_id, $pid, $notes);
        $stmt->execute();
    }
    $message = "Updated successfully!";
}

$res = $conn->query("SELECT a.*, p.full_name, m.internal_doctor_notes FROM appointments a JOIN patients p ON a.patient_id = p.patient_id LEFT JOIN medical_records m ON a.appointment_id = m.appointment_id WHERE a.appointment_id = $appt_id");
$data = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Notes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 2rem; display: flex; justify-content: center; }
        .container { background: white; width: 100%; max-width: 600px; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        a { text-decoration: none; color: #64748b; font-weight: bold; }
        label { display: block; margin-bottom: 5px; font-weight: 600; }
        textarea, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 15px; }
        button { background: #0ea5e9; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; cursor: pointer; }
        .video-box { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 10px; border-radius: 5px; display: flex; justify-content: space-between; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Meeting Info</h2>
            <a href="manage appoinments.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>

        <?php if($message): ?><p style="color: green; text-align: center;"><?php echo $message; ?></p><?php endif; ?>

        <div style="background: #f8fafc; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <p><strong>Patient:</strong> <?php echo htmlspecialchars($data['full_name']); ?></p>
            <p><strong>Date:</strong> <?php echo $data['appointment_date']; ?> at <?php echo $data['appointment_time']; ?></p>
        </div>

        <form method="POST">
            <label>Status</label>
            <select name="status">
                <option value="Pending" <?php if($data['status']=='Pending') echo 'selected'; ?>>Pending</option>
                <option value="Confirmed" <?php if($data['status']=='Confirmed') echo 'selected'; ?>>Confirmed</option>
                <option value="Completed" <?php if($data['status']=='Completed') echo 'selected'; ?>>Completed</option>
                <option value="Cancelled" <?php if($data['status']=='Cancelled') echo 'selected'; ?>>Cancelled</option>
            </select>

            <label>Video Consultation</label>
            <div class="video-box">
                <span><i class="fa-solid fa-video"></i> Auto-generated Room</span>
                <a href="../meeting.php?id=<?php echo $appt_id; ?>" target="_blank" style="color: #166534; font-weight: bold;">Join Now &rarr;</a>
            </div>

            <label>Doctor's Notes</label>
            <textarea name="internal_notes" rows="5"><?php echo htmlspecialchars($data['internal_doctor_notes'] ?? ''); ?></textarea>

            <button type="submit">Save Details</button>
        </form>
    </div>
</body>
</html>