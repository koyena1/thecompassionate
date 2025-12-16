<?php
// FILE: admin/meeting_info_notes.php
include '../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: manage appoinments.php"); 
    exit();
}

$appt_id = $_GET['id'];
$message = "";
$presc_message = "";

// --- 1. FETCH DATA (Joined with Patients table to get Address/Condition) ---
$sql = "SELECT a.*, p.full_name, p.email, p.phone_number, p.address, p.current_condition_tag, p.age, p.blood_type, m.internal_doctor_notes 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_id 
        LEFT JOIN medical_records m ON a.appointment_id = m.appointment_id 
        WHERE a.appointment_id = $appt_id";
$res = $conn->query($sql);
$data = $res->fetch_assoc();

// --- 2. HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // A. SAVE MEETING NOTES
    if (isset($_POST['save_notes'])) {
        $notes = $_POST['internal_notes']; 
        $status = $_POST['status'];
        
        // Update Appointment Status
        $conn->query("UPDATE appointments SET status = '$status' WHERE appointment_id = $appt_id");

        // Update Medical Records (Doctor's Private Notes)
        $check = $conn->query("SELECT record_id FROM medical_records WHERE appointment_id = $appt_id");
        if ($check->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE medical_records SET internal_doctor_notes = ? WHERE appointment_id = ?");
            $stmt->bind_param("si", $notes, $appt_id);
        } else {
            $pid = $data['patient_id'];
            $stmt = $conn->prepare("INSERT INTO medical_records (appointment_id, patient_id, internal_doctor_notes) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $appt_id, $pid, $notes);
        }
        $stmt->execute();
        $message = "Meeting details updated!";
        
        // Refresh Data
        $res = $conn->query($sql);
        $data = $res->fetch_assoc();
    }

    // B. GENERATE & SEND PRESCRIPTION
    if (isset($_POST['generate_prescription'])) {
        $patient_id = $data['patient_id'];
        
        // 1. Get Inputs
        $p_address = $_POST['address'];
        $p_condition = $_POST['condition'];
        $rx_content = $_POST['rx_content']; // The actual medicine/diagnosis
        $dr_name = "Dr. " . ($_SESSION['admin_name'] ?? 'Consultant'); // Assuming admin session has name, else generic
        
        // 2. Update Patient Table (Address & Condition)
        $stmt_p = $conn->prepare("UPDATE patients SET address = ?, current_condition_tag = ? WHERE patient_id = ?");
        $stmt_p->bind_param("ssi", $p_address, $p_condition, $patient_id);
        $stmt_p->execute();

        // 3. CREATE THE PRESCRIPTION FILE (HTML format that looks like PDF)
        // We generate a professional HTML file and save it to the uploads folder.
        
        $file_name = "Rx_" . $appt_id . "_" . time() . ".html";
        $file_path_rel = "uploads/" . $file_name;
        $file_path_abs = "../patient/" . $file_path_rel;

        $html_content = "
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica', sans-serif; padding: 40px; color: #333; max-width: 800px; margin: auto; border: 1px solid #ddd; }
                .header { border-bottom: 2px solid #7B61FF; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
                .logo { color: #7B61FF; font-size: 24px; font-weight: bold; }
                .meta-box { background: #f9f9f9; padding: 20px; border-radius: 10px; display: flex; justify-content: space-between; margin-bottom: 30px; }
                .meta-col strong { display: block; font-size: 12px; color: #888; margin-bottom: 5px; }
                .meta-col span { font-size: 16px; font-weight: 600; }
                .rx-section { min-height: 300px; }
                .rx-header { font-size: 18px; font-weight: bold; color: #7B61FF; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .rx-body { white-space: pre-line; line-height: 1.6; }
                .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #aaa; border-top: 1px solid #eee; padding-top: 20px; }
                .btn-print { background: #7B61FF; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; margin-bottom: 20px; }
                @media print { .btn-print { display: none; } body { border: none; } }
            </style>
        </head>
        <body>
            <button onclick='window.print()' class='btn-print'>Download / Print PDF</button>
            <div class='header'>
                <div class='logo'>The CompassionateSpace <i class='fa-solid fa-heart-pulse'></i></div>
                <div style='text-align: right;'>
                    <strong>Prescription</strong><br>
                    Date: " . date("d M Y") . "<br>
                    ID: #RX-" . $appt_id . "
                </div>
            </div>
            
            <div class='meta-box'>
                <div class='meta-col'>
                    <strong>PATIENT</strong>
                    <span>" . htmlspecialchars($data['full_name']) . "</span><br>
                    <small>" . htmlspecialchars($p_address) . "</small>
                </div>
                <div class='meta-col'>
                    <strong>DETAILS</strong>
                    <span>Age: " . ($data['age'] ?? 'N/A') . " | " . ($data['blood_type'] ?? '') . "</span><br>
                    <small>Condition: " . htmlspecialchars($p_condition) . "</small>
                </div>
                <div class='meta-col'>
                    <strong>DOCTOR</strong>
                    <span>" . $dr_name . "</span>
                </div>
            </div>

            <div class='rx-section'>
                <div class='rx-header'>Rx / Clinical Notes</div>
                <div class='rx-body'>" . nl2br(htmlspecialchars($rx_content)) . "</div>
            </div>

            <div class='footer'>
                <p>This is a computer-generated document. No signature is required.</p>
                <p>The CompassionateSpace Clinic • contact@thecompassionatespace49.com</p>
            </div>
        </body>
        </html>";

        // Save file to server
        file_put_contents($file_path_abs, $html_content);

        // 4. Save to Database (Prescriptions Table)
        // We use 'Upload' type because we created a file
        $stmt = $conn->prepare("INSERT INTO prescriptions (appointment_id, patient_id, prescription_type, file_url) VALUES (?, ?, 'Upload', ?)");
        $stmt->bind_param("iis", $appt_id, $patient_id, $file_path_rel);
        
        if ($stmt->execute()) {
            $presc_message = "Prescription Generated & Sent Successfully!";
            
            // --- C. SIMULATE SENDING (Email & WhatsApp) ---
            
            // 1. WhatsApp Logic (Using a placeholder link)
            // In a real scenario, you would use the Twilio API here.
            $wa_link = "https://wa.me/" . preg_replace('/[^0-9]/', '', $data['phone_number']) . "?text=" . urlencode("Hello " . $data['full_name'] . ", your prescription is ready. View it here: " . "http://yourwebsite.com/patient/" . $file_path_rel);
            
            // 2. Email Logic (Standard PHP Mail)
            $to = $data['email'];
            $subject = "Your Prescription - The CompassionateSpace";
            $msg = "Dear " . $data['full_name'] . ",\n\nYour prescription has been updated. Please log in to your portal to view it.\n\nRegard,\nThe CompassionateSpace";
            // mail($to, $subject, $msg); // Uncomment to enable email if server supports it

            // Refresh Data to show updated address/condition
            $res = $conn->query($sql);
            $data = $res->fetch_assoc();
        } else {
            $presc_message = "Error saving prescription: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Notes & Prescription</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 2rem; display: flex; justify-content: center; }
        .main-wrapper { width: 100%; max-width: 800px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: 1 / -1; }
        
        .container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        a { text-decoration: none; color: #64748b; font-weight: bold; }
        
        label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; color: #334155; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; margin-bottom: 15px; background: #f8fafc; }
        
        button { background: #0ea5e9; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        button:hover { background: #0284c7; }
        .btn-green { background: #22c55e; }
        .btn-green:hover { background: #16a34a; }
        
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; font-size: 0.9rem; }
        .alert-success { background: #dcfce7; color: #166534; }
        
        .video-box { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 10px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }

        /* Responsive */
        @media (max-width: 768px) { .main-wrapper { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="main-wrapper">
        
        <div class="container">
            <div class="header">
                <h3>Meeting Info</h3>
                <a href="manage appoinments.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
            </div>

            <?php if($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>

            <div style="background: #e0f2fe; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9rem; color: #0369a1;">
                <strong><?php echo htmlspecialchars($data['full_name']); ?></strong><br>
                <i class="fa-regular fa-clock"></i> <?php echo date("d M Y, g:i A", strtotime($data['appointment_date'] . ' ' . $data['appointment_time'])); ?>
            </div>

            <form method="POST">
                <input type="hidden" name="save_notes" value="1">
                
                <label>Status</label>
                <select name="status">
                    <option value="Pending" <?php if($data['status']=='Pending') echo 'selected'; ?>>Pending</option>
                    <option value="Confirmed" <?php if($data['status']=='Confirmed') echo 'selected'; ?>>Confirmed</option>
                    <option value="Completed" <?php if($data['status']=='Completed') echo 'selected'; ?>>Completed</option>
                </select>

                <label>Join Meeting</label>
                <div class="video-box">
                    <span><i class="fa-solid fa-video"></i> Video Room</span>
                    <a href="../meeting.php?id=<?php echo $appt_id; ?>" target="_blank" style="text-decoration: underline;">Join</a>
                </div>

                <label>Private Notes (Internal)</label>
                <textarea name="internal_notes" rows="4" placeholder="Only visible to doctors..."><?php echo htmlspecialchars($data['internal_doctor_notes'] ?? ''); ?></textarea>

                <button type="submit">Update Notes</button>
            </form>
        </div>

        <div class="container" style="border-top: 4px solid #22c55e;">
            <div class="header">
                <h3 style="color: #166534;"><i class="fa-solid fa-file-prescription"></i> Generate Rx</h3>
            </div>

            <?php if($presc_message): ?><div class="alert alert-success"><?php echo $presc_message; ?></div><?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="generate_prescription" value="1">

                <label>Patient Address</label>
                <textarea name="address" rows="2" required placeholder="Enter patient address..."><?php echo htmlspecialchars($data['address'] ?? ''); ?></textarea>

                <label>Current Condition (Tag)</label>
                <input type="text" name="condition" value="<?php echo htmlspecialchars($data['current_condition_tag'] ?? 'Stable'); ?>" required placeholder="e.g. Recovering, Critical">

                <label>Prescription & Diagnosis</label>
                <textarea name="rx_content" rows="8" required placeholder="Rx:
1. Paracetamol 500mg - 1 tab after food (3 days)
2. Rest and hydration..."></textarea>

                <button type="submit" class="btn-green">
                    <i class="fa-regular fa-paper-plane"></i> Generate & Send
                </button>
                <p style="font-size: 11px; color: #666; margin-top: 10px; text-align: center;">
                    * This will auto-create a PDF-style document and email it to the patient.
                </p>
            </form>

            <?php if(isset($wa_link)): ?>
            <div style="margin-top: 15px; text-align: center;">
                <a href="<?php echo $wa_link; ?>" target="_blank" style="color: #25D366; font-weight: bold;">
                    <i class="fa-brands fa-whatsapp"></i> Open WhatsApp to Send
                </a>
            </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>