<?php 
include '../config/db.php'; 
session_start();

// --- CHECK LOGIN ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php"); 
    exit();
}

$patient_id = $_SESSION['user_id']; 

// --- FETCH DARK MODE PREFERENCE ---
if(!isset($_SESSION['pref_dark_mode'])) {
    $pref_res = $conn->query("SELECT pref_dark_mode FROM patients WHERE patient_id = '$patient_id'");
    $pref_row = $pref_res->fetch_assoc();
    $_SESSION['pref_dark_mode'] = $pref_row['pref_dark_mode'] ?? 0;
}
$dark_class = ($_SESSION['pref_dark_mode'] == 1) ? 'dark-mode' : '';

// --- FETCH PATIENT DETAILS ---
$sql_patient = "SELECT * FROM patients WHERE patient_id = '$patient_id'";
$result_patient = $conn->query($sql_patient);
$patient_data = $result_patient->fetch_assoc();

// 1. Prepare Name
$display_name = !empty($patient_data['full_name']) ? $patient_data['full_name'] : 'Patient';

// 2. Prepare Image (Logic to use uploaded photo or fallback)
$db_image = !empty($patient_data['profile_image']) ? $patient_data['profile_image'] : 'https://i.pravatar.cc/150?img=33';
$display_img = $db_image . "?v=" . time(); 
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
        :root {
            --bg-color: #F5F6FA;
            --sidebar-width: 260px;
            --primary-purple: #7B61FF;
            --primary-red: #FF5C60;
            --primary-blue: #1FB6FF;
            --text-dark: #2D3436;
            --text-light: #A0A4A8;
            --white: #FFFFFF;
            --shadow: 0 4px 15px rgba(0,0,0,0.03);
            --radius: 20px;
            --border-color: #eee;
        }

        body.dark-mode {
            --bg-color: #0F172A;
            --text-dark: #F8FAFC;
            --text-light: #94A3B8;
            --white: #1E293B;
            --shadow: 0 4px 15px rgba(0,0,0,0.2);
            --border-color: #252545;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
            transition: background 0.3s ease;
        }

        /* --- SIDEBAR STYLING --- */
        .sidebar {
            width: var(--sidebar-width);
            background: #7B3F00;
            padding: 30px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            left: 0; top: 0;
            z-index: 1001;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.sidebar-off .sidebar { transform: translateX(-100%); }
        body.sidebar-off .main-content { margin-left: 0; padding-left: 40px; }

        .sidebar-close-btn {
            position: absolute;
            top: 20px; right: 20px; width: 32px; height: 32px;
            border-radius: 50%; background: rgba(255, 255, 255, 0.2);
            color: white; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; transition: all 0.3s ease; z-index: 1002;
        }
        .sidebar-close-btn:hover { background: rgba(255, 255, 255, 0.3); transform: rotate(90deg); }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0; background: rgba(0,0,0,0.4);
            z-index: 1000; backdrop-filter: blur(2px);
        }
        body.mobile-sidebar-on .sidebar-overlay { display: block; }

        /* --- MAIN CONTENT --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px 40px 30px 70px;
            width: calc(100% - var(--sidebar-width));
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .welcome-container { display: flex; align-items: center; gap: 15px; }

        #toggle-btn {
            font-size: 20px; cursor: pointer; color: var(--text-dark);
            background: var(--white); width: 45px; height: 45px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px; box-shadow: var(--shadow); border: none;
            transition: 0.3s;
        }
        #toggle-btn:hover { transform: scale(1.05); }

        .user-profile { display: flex; align-items: center; gap: 20px; }
        .profile-info { display: flex; align-items: center; gap: 10px; }
        .profile-info img { width: 45px; height: 45px; border-radius: 12px; object-fit: cover; border: 2px solid var(--white); }

        /* --- APPOINTMENTS STYLES --- */
        .appt-tabs { display: flex; gap: 20px; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        .tab-btn { background: none; border: none; font-size: 16px; font-weight: 500; color: var(--text-light); cursor: pointer; padding: 5px 10px; position: relative; }
        .tab-btn.active { color: var(--primary-blue); }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -11px; left: 0; width: 100%; height: 3px; background: var(--primary-blue); border-radius: 3px; }

        .appt-card {
            background: var(--white); border-radius: var(--radius); padding: 25px; box-shadow: var(--shadow); margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; transition: 0.3s;
        }
        .appt-card:hover { transform: translateY(-5px); }
        .appt-details { display: flex; gap: 20px; align-items: center; }
        /* Doctor image removed per request */
        .appt-info h4 { font-size: 16px; margin-bottom: 5px; }
        .appt-info span { font-size: 13px; color: var(--text-light); display: block; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-Confirmed { background: rgba(31, 182, 255, 0.1); color: var(--primary-blue); }
        .status-Completed { background: rgba(0, 182, 155, 0.1); color: #00B69B; }
        .status-Cancelled { background: rgba(255, 92, 96, 0.1); color: var(--primary-red); }
        .status-Pending { background: #ffedd5; color: #9a3412; }

        .appt-actions { display: flex; gap: 10px; }
        .btn-join {
            background: var(--primary-blue); color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 8px; text-decoration: none;
        }
        .btn-outline {
            background: transparent; border: 1px solid var(--border-color); color: var(--text-light); padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px;
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .main-content { margin-left: 0; padding: 20px; width: 100%; }
            body.mobile-sidebar-on .sidebar { transform: translateX(0); }
            .appt-card { flex-direction: column; align-items: flex-start; gap: 15px; } 
            .appt-actions { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body class="<?php echo $dark_class; ?>">

    <div class="sidebar-overlay" id="overlay"></div>

    <div class="sidebar" id="sidebar-container">
        <button class="sidebar-close-btn" id="sidebar-close-btn"><i class="fa-solid fa-times"></i></button>
        <?php include 'sidebar.php'; ?>
    </div>

    <main class="main-content">
        <header>
            <div class="welcome-container">
                <button id="toggle-btn"><i class="fa-solid fa-bars"></i></button>
                <div class="welcome-text">
                    <h1 style="font-size: 24px; font-weight: 700;">My Appointments</h1>
                    <p style="color: var(--text-light); font-size: 14px;">Manage your medical consultations</p>
                </div>
            </div>
            
            <div class="user-profile">
                <div class="profile-info">
                    <div style="text-align: right;">
                        <h4 style="font-size: 14px;"><?php echo htmlspecialchars($display_name); ?></h4>
                        <p style="font-size: 11px; color: var(--text-light);">Patient Profile</p>
                    </div>
                    <img src="<?php echo htmlspecialchars($display_img); ?>" alt="Patient Profile">
                </div>
            </div>
        </header>

        <div id="appointments-section">
            <div class="appt-tabs"><button class="tab-btn active">All Appointments</button></div>

            <div class="appointments-list">
                <?php
                    $sql_all = "SELECT a.*, d.full_name as doctor_name, d.specialization 
                                FROM appointments a 
                                LEFT JOIN admin_users d ON a.admin_id = d.admin_id
                                WHERE a.patient_id = $patient_id 
                                ORDER BY a.appointment_date DESC";
                    $res_all = $conn->query($sql_all);

                    if ($res_all && $res_all->num_rows > 0) {
                        while($row = $res_all->fetch_assoc()){
                            $status = $row['status'];
                            $btn_html = "";
                            
                            if($status == 'Confirmed'){
                                $link = !empty($row['meeting_link']) ? $row['meeting_link'] : '../meeting.php?id='.$row['appointment_id'];
                                $btn_html = '<a href="'.$link.'" target="_blank" class="btn-join"><i class="fa-solid fa-video"></i> Join Meeting</a>';
                            } elseif ($status == 'Completed'){
                                $btn_html = '<button class="btn-outline"><i class="fa-solid fa-download"></i> Rx</button>';
                            }

                            $doctorName = ($row['admin_id'] == 1) ? 'Dr. Usri Sengupta' : (htmlspecialchars($row['doctor_name']) ?: 'Assigned Soon');
                            $specialization = htmlspecialchars($row['specialization'] ?: 'General');

                            echo '
                            <div class="appt-card">
                                <div class="appt-details">
                                    <div class="appt-info">
                                        <h4>'.$doctorName.'</h4>
                                        <span>'.$specialization.'</span>
                                        <span style="color: var(--text-dark); font-weight: 500; margin-top: 5px;">
                                            <i class="fa-regular fa-clock"></i> '.date("d M Y", strtotime($row['appointment_date'])).' at '.date("g:i A", strtotime($row['appointment_time'])).'
                                        </span>
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
                ?>
            </div>
        </div>
    </main>

    <script>
        const toggleBtn = document.getElementById('toggle-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const overlay = document.getElementById('overlay');
        const body = document.body;

        function handleSidebar() {
            if (window.innerWidth > 768) { body.classList.toggle('sidebar-off'); } 
            else { body.classList.toggle('mobile-sidebar-on'); }
        }

        function closeSidebar() {
            body.classList.remove('mobile-sidebar-on');
            if (window.innerWidth > 768) body.classList.add('sidebar-off');
        }

        if (toggleBtn) toggleBtn.addEventListener('click', handleSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', () => body.classList.remove('mobile-sidebar-on'));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });
    </script>
</body>
</html>