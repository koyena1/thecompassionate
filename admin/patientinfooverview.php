<?php
// FILE: admin/patientinfooverview.php
include '../config/db.php';
session_start();

// 1. Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "No patient selected. <a href='patient_list.php'>Go back</a>";
    exit;
}

$patient_id = intval($_GET['id']);

// 2. Fetch Patient Details
$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    echo "Patient not found.";
    exit;
}

// 3. Fetch Medical Records (History)
$history_query = "SELECT * FROM medical_records WHERE patient_id = $patient_id ORDER BY created_at DESC";
$history_res = $conn->query($history_query);

// 4. Fetch Upcoming Appointments
$appt_query = "SELECT * FROM appointments WHERE patient_id = $patient_id AND status != 'Cancelled' ORDER BY appointment_date ASC";
$appt_res = $conn->query($appt_query);

// 5. Fetch Prescriptions (Simulating Medications list)
$presc_query = "SELECT * FROM prescriptions WHERE patient_id = $patient_id ORDER BY created_at DESC";
$presc_res = $conn->query($presc_query);

// Helpers for display
$age = $patient['age'] ?? 'N/A';
$blood = $patient['blood_type'] ?? 'Unknown';
$height = $patient['height'] != '0' ? $patient['height'] . ' cm' : 'N/A';
$weight = $patient['weight'] != '0' ? $patient['weight'] . ' kg' : 'N/A';
$allergies = !empty($patient['allergies']) ? $patient['allergies'] : 'No known allergies';

// Initials for Avatar
$initials = strtoupper(substr($patient['full_name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Overview - <?php echo htmlspecialchars($patient['full_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- CSS VARIABLES & RESET --- */
        :root {
            --primary: #2563eb;
            --secondary: #64748b;
            --bg-body: #f1f5f9;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #94a3b8;
            --danger: #ef4444;
            --success: #22c55e;
            --warning: #f59e0b;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body { background-color: var(--bg-body); color: var(--text-main); display: flex; min-height: 100vh; }

        /* --- LAYOUT --- */
        .dashboard-container {
            display: grid;
            grid-template-columns: 300px 1fr; /* Sidebar fixed, content flexible */
            gap: 20px;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* --- SIDEBAR (Patient Profile) --- */
        .sidebar {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            height: fit-content;
        }

        .profile-header { text-align: center; margin-bottom: 20px; }
        .avatar {
            width: 120px; height: 120px;
            background-color: #e2e8f0;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; color: var(--secondary);
            overflow: hidden;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .patient-name { font-size: 1.5rem; font-weight: 700; margin-bottom: 5px; }
        .patient-id { color: var(--text-muted); font-size: 0.9rem; }

        .info-list { list-style: none; margin-top: 20px; }
        .info-list li {
            display: flex; justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem;
        }
        .info-list li:last-child { border-bottom: none; }
        .label { color: var(--secondary); font-weight: 500; }
        .value { font-weight: 600; text-align: right; }

        .status-badge {
            display: inline-block; padding: 4px 12px;
            border-radius: 20px; font-size: 0.8rem; font-weight: bold;
            background: #dcfce7; color: var(--success);
            margin-top: 5px;
        }

        /* --- MAIN CONTENT --- */
        .main-content { display: flex; flex-direction: column; gap: 20px; }

        /* Header */
        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            background: var(--bg-card); padding: 15px 25px;
            border-radius: var(--radius); box-shadow: var(--shadow);
        }
        .section-title { font-size: 1.25rem; font-weight: 700; color: var(--text-main); }
        .actions button {
            padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer;
            font-weight: 600; transition: 0.2s; gap: 8px;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-outline { background: transparent; border: 1px solid #cbd5e1; color: var(--secondary); margin-right: 10px; }
        .btn-outline:hover { background: #f8fafc; text-decoration: none; color: var(--secondary); }
        
        a.btn-outline { text-decoration: none; display: inline-block; }

        /* Vitals Grid */
        .vitals-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .vital-card {
            background: var(--bg-card); padding: 20px;
            border-radius: var(--radius); box-shadow: var(--shadow);
            display: flex; align-items: center; gap: 15px;
            border-left: 4px solid var(--primary);
        }
        .vital-icon {
            width: 50px; height: 50px; background: #eff6ff;
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            color: var(--primary); font-size: 1.2rem;
        }
        .vital-info h4 { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 4px; }
        .vital-info p { font-size: 1.5rem; font-weight: 700; }
        .vital-info span { font-size: 0.8rem; color: var(--text-muted); font-weight: 400; }

        /* Detailed Sections */
        .details-grid {
            display: grid; grid-template-columns: 2fr 1fr; /* 2 columns: History vs Sidebar stuff */
            gap: 20px;
        }

        .clinical-col, .alerts-col { display: flex; flex-direction: column; gap: 20px; }

        .card {
            background: var(--bg-card); border-radius: var(--radius);
            box-shadow: var(--shadow); padding: 25px;
        }
        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .card-header h3 { font-size: 1.1rem; }

        /* Tables & Lists */
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        th { color: var(--secondary); font-weight: 600; }
        
        .tag {
            padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600;
        }
        .tag-red { background: #fee2e2; color: var(--danger); }
        .tag-blue { background: #dbeafe; color: var(--primary); }

        .timeline-item {
            display: flex; gap: 15px; margin-bottom: 15px;
        }
        .timeline-dot {
            width: 12px; height: 12px; background: var(--primary);
            border-radius: 50%; margin-top: 6px; flex-shrink: 0;
        }
        .timeline-content h5 { font-size: 1rem; margin-bottom: 4px; }
        .timeline-content p { color: var(--secondary); font-size: 0.9rem; }
        .timeline-date { color: var(--text-muted); font-size: 0.8rem; }

        /* Responsive */
        @media (max-width: 900px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .details-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        
        <aside class="sidebar">
            <div class="profile-header">
                <div class="avatar">
                   <?php echo $initials; ?>
                </div>
                <h2 class="patient-name"><?php echo htmlspecialchars($patient['full_name']); ?></h2>
                <p class="patient-id">ID: #<?php echo $patient['patient_id']; ?></p>
                <span class="status-badge"><?php echo htmlspecialchars($patient['current_condition_tag'] ?? 'Stable'); ?></span>
            </div>

            <ul class="info-list">
                <li><span class="label">Age</span> <span class="value"><?php echo $age; ?> yrs</span></li>
                <li><span class="label">Blood Type</span> <span class="value"><?php echo $blood; ?></span></li>
                <li><span class="label">Phone</span> <span class="value"><?php echo htmlspecialchars($patient['phone_number']); ?></span></li>
                <li><span class="label">Email</span> <span class="value" style="font-size:0.8rem;"><?php echo htmlspecialchars($patient['email']); ?></span></li>
            </ul>

            <div style="margin-top: 30px;">
                <h4 style="margin-bottom: 10px; font-size: 0.9rem; color: var(--secondary);">Address</h4>
                <div style="background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <p style="font-weight: 600;"><?php echo !empty($patient['address']) ? htmlspecialchars($patient['address']) : 'Not Provided'; ?></p>
                </div>
            </div>
        </aside>

        <main class="main-content">
            
            <header class="top-bar">
    <div class="section-title">Medical Overview</div>
    <div class="actions">
        <a href="manage_healthtimeline.php?patient_id=<?php echo $patient_id; ?>" class="btn-outline" style="color: var(--primary); border-color: var(--primary);">
            <i class="fa-solid fa-pen-to-square"></i> Update Timeline
        </a>
        
        <a href="patient_list.php" class="btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <button class="btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
    </div>
</header>

            <section class="vitals-grid">
                <div class="vital-card" style="border-color: var(--warning);">
                    <div class="vital-icon" style="color: var(--danger); background: #fef2f2;"><i class="fa-solid fa-droplet"></i></div>
                    <div class="vital-info">
                        <h4>Blood Type</h4>color: var(--danger);
                        <p><?php echo $blood; ?></p>
                    </div>
                </div>
                <div class="vital-card" style="border-color: var(--success);">
                    <div class="vital-icon" style="color: var(--success); background: #f0fdf4;"><i class="fa-solid fa-weight-scale"></i></div>
                    <div class="vital-info">
                        <h4>Weight</h4>
                        <p><?php echo $weight; ?></p>
                    </div>
                </div>
                <div class="vital-card" style="border-color: var(--danger);">
                    <div class="vital-icon" style="color: var(--warning); background: #fffbeb;"><i class="fa-solid fa-ruler-vertical"></i></div>
                    <div class="vital-info">
                        <h4>Height</h4>
                        <p><?php echo $height; ?></p>
                    </div>
                </div>
            </section>

            <div class="details-grid">
                
                <div class="clinical-col">
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-pills"></i> Recent Prescriptions</h3>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Details/File</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($presc_res->num_rows > 0): ?>
                                    <?php while($rx = $presc_res->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $rx['prescription_type']; ?></td>
                                            <td>
                                                <?php 
                                                // --- FIXED DOWNLOAD LOGIC ---
                                                if($rx['prescription_type'] == 'Upload' && $rx['file_url']) {
                                                    $dbPath = $rx['file_url'];
                                                    
                                                    // Determine correct path relative to this file
                                                    // If DB stores "uploads/file.pdf", path is "../uploads/file.pdf"
                                                    // If DB stores "file.pdf", path is "../uploads/file.pdf"
                                                    if (strpos($dbPath, 'uploads/') === 0) {
                                                        $finalPath = "../" . $dbPath;
                                                    } else {
                                                        $finalPath = "../uploads/" . $dbPath;
                                                    }

                                                    echo "<a href='" . htmlspecialchars($finalPath) . "' download class='btn-download' style='color:var(--primary); font-weight:600; text-decoration:none;'>
                                                            <i class='fa-solid fa-download'></i> Download File
                                                          </a>";
                                                } else {
                                                    echo htmlspecialchars(substr($rx['digital_content'], 0, 50)) . '...';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($rx['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3">No prescriptions found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fa-solid fa-file-medical"></i> Medical History & Notes</h3>
                        </div>
                        <?php if ($history_res->num_rows > 0): ?>
                            <?php while($rec = $history_res->fetch_assoc()): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <span class="timeline-date"><?php echo date('M d, Y H:i', strtotime($rec['created_at'])); ?></span>
                                        <h5><?php echo !empty($rec['diagnosis']) ? htmlspecialchars($rec['diagnosis']) : 'Checkup / Notes'; ?></h5>
                                        <p><?php echo htmlspecialchars($rec['internal_doctor_notes']); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color:var(--text-muted);">No medical records found.</p>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="alerts-col">
                    
                    <div class="card" style="border-top: 4px solid var(--danger);">
                        <div class="card-header">
                            <h3 style="color: var(--danger);"><i class="fa-solid fa-triangle-exclamation"></i> Allergies</h3>
                        </div>
                        <p style="padding: 10px; background: #fff5f5; border-radius: 6px;">
                            <?php echo htmlspecialchars($allergies); ?>
                        </p>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Upcoming Appointments</h3>
                        </div>
                        <?php if ($appt_res->num_rows > 0): ?>
                            <?php while($appt = $appt_res->fetch_assoc()): 
                                // Only show future appointments
                                if(strtotime($appt['appointment_date']) >= strtotime(date('Y-m-d'))):
                            ?>
                                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border-left: 3px solid var(--primary); margin-bottom: 10px;">
                                    <p style="font-weight: 700; font-size: 0.95rem;">General Session</p>
                                    <p style="font-size: 0.85rem; color: var(--secondary);"><?php echo htmlspecialchars($appt['initial_health_issue']); ?></p>
                                    <div style="margin-top: 8px; font-size: 0.8rem; color: var(--text-muted);">
                                        <i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?> &nbsp; 
                                        <i class="fa-regular fa-clock"></i> <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                             <p style="color:var(--text-muted);">No upcoming visits.</p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </main>
    </div>

</body>
</html>