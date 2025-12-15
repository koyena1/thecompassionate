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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
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

        /* --- APPOINTMENTS STYLES --- */
        .appt-tabs { display: flex; gap: 20px; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .tab-btn { background: none; border: none; font-size: 16px; font-weight: 500; color: var(--text-light); cursor: pointer; padding: 5px 10px; position: relative; }
        .tab-btn.active { color: var(--primary-blue); }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -11px; left: 0; width: 100%; height: 3px; background: var(--primary-blue); border-radius: 3px; }

        .appt-card {
            background: var(--white); border-radius: var(--radius); padding: 25px; box-shadow: var(--shadow); margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; transition: 0.3s;
        }
        .appt-card:hover { transform: translateY(-5px); }
        .appt-details { display: flex; gap: 20px; align-items: center; }
        .doctor-img { width: 60px; height: 60px; border-radius: 15px; object-fit: cover; }
        .appt-info h4 { font-size: 16px; margin-bottom: 5px; }
        .appt-info span { font-size: 13px; color: var(--text-light); display: block; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        
        /* Dynamic Status Colors */
        .status-Confirmed { background: rgba(31, 182, 255, 0.1); color: var(--primary-blue); }
        .status-Completed { background: rgba(0, 182, 155, 0.1); color: #00B69B; }
        .status-Cancelled { background: rgba(255, 92, 96, 0.1); color: var(--primary-red); }
        .status-Pending { background: #ffedd5; color: #9a3412; }

        .appt-actions { display: flex; gap: 10px; }
        .btn-join {
            background: var(--primary-blue); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 8px; text-decoration: none;
        }
        .btn-outline {
            background: transparent; border: 1px solid #eee; color: var(--text-light); padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px;
        }
        .btn-outline:hover { background: #f5f5f5; color: var(--text-dark); }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
            .appt-card { flex-direction: column; align-items: flex-start; gap: 15px; } 
            .appt-actions { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div class="welcome-text">
                <h1>My Appointments</h1>
                <p>Manage your upcoming and past visits</p>
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

        <div id="appointments-section">
            <div class="appt-tabs">
                <button class="tab-btn active">All Appointments</button>
            </div>

            <div class="appointments-list">
                <?php
                    // Fetch ALL appointments for the logged-in patient
                    // Joining with admin_users table to get Doctor's name if assigned
                    $sql_all = "SELECT a.*, d.full_name as doctor_name, d.specialization 
                                FROM appointments a 
                                LEFT JOIN admin_users d ON a.admin_id = d.admin_id
                                WHERE a.patient_id = $patient_id 
                                ORDER BY a.appointment_date DESC";
                    $res_all = $conn->query($sql_all);

                    if ($res_all) {
                        if ($res_all->num_rows > 0) {
                            while($row = $res_all->fetch_assoc()){
                                $status = $row['status'];
                                $btn_html = "";
                                
                                // Logic for button display based on Status
                                if($status == 'Confirmed'){
                                    $link = !empty($row['meeting_link']) ? $row['meeting_link'] : '#';
                                    $btn_html = '<a href="'.$link.'" target="_blank" class="btn-join"><i class="fa-solid fa-video"></i> Join Meeting</a>';
                                } elseif ($status == 'Completed'){
                                    $btn_html = '<button class="btn-outline"><i class="fa-solid fa-download"></i> Prescription</button>';
                                } elseif ($status == 'Pending'){
                                    $btn_html = '<span style="font-size:12px; color:#aaa;">Waiting for confirmation...</span>';
                                } else {
                                    $btn_html = '<span style="font-size:12px; color:#aaa;">No actions</span>';
                                }

                                // Doctor Name fallback
                                $doctorName = !empty($row['doctor_name']) ? htmlspecialchars($row['doctor_name']) : 'Doctor Assigned Soon';
                                $specialization = !empty($row['specialization']) ? htmlspecialchars($row['specialization']) : 'General';

                                echo '
                                <div class="appt-card">
                                    <div class="appt-details">
                                        <img src="https://i.pravatar.cc/150?img=59" alt="Doctor" class="doctor-img">
                                        <div class="appt-info">
                                            <h4>'.$doctorName.'</h4>
                                            <span>'.$specialization.'</span>
                                            <span style="color: var(--text-dark); font-weight: 500; margin-top: 5px;">
                                                <i class="fa-regular fa-clock"></i> '.date("d M Y", strtotime($row['appointment_date'])).' at '.date("g:i A", strtotime($row['appointment_time'])).'
                                            </span>
                                            <span style="font-size:12px; color:#777; display:block; margin-top:3px;">Reason: '.htmlspecialchars($row['initial_health_issue']).'</span>
                                        </div>
                                    </div>
                                    <div class="appt-actions">
                                        <span class="status-badge status-'.$status.'">'.$status.'</span>
                                        '.$btn_html.'
                                    </div>
                                </div>';
                            }
                        } else {
                            echo "<p style='text-align:center; color:#888; margin-top:20px;'>No appointments found.</p>";
                        }
                    } else {
                        echo "<p>Error fetching appointments: " . $conn->error . "</p>";
                    }
                ?>
            </div>
        </div>

    </main>

</body>
</html>