<?php 
// FILE: patient/healtht.php
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

$display_name = !empty($patient_data['full_name']) ? $patient_data['full_name'] : 'Patient';
$db_image = !empty($patient_data['profile_image']) ? $patient_data['profile_image'] : 'https://i.pravatar.cc/150?img=33';
$display_img = $db_image . "?v=" . time();

// --- FETCH TIMELINE UPDATES ---
$timeline_sql = "SELECT * FROM medical_records WHERE patient_id = '$patient_id' ORDER BY created_at DESC";
$timeline_res = $conn->query($timeline_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Timeline | Patient Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #F8F9FD;
            --sidebar-width: 240px;
            --primary-brown: #7B3F00;
            --primary-purple: #7B61FF;
            --primary-blue: #1FB6FF;
            --text-dark: #2D3436;
            --text-light: #9DA4B0;
            --white: #FFFFFF;
            --shadow: 0 10px 30px rgba(0,0,0,0.04);
            --radius: 24px;
            --border-line: #edf2f7;
        }

        body.dark-mode {
            --bg-color: #0F172A;
            --text-dark: #F8FAFC;
            --text-light: #94A3B8;
            --white: #1E293B;
            --shadow: 0 10px 30px rgba(0,0,0,0.2);
            --border-line: #2D3A4F;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
            transition: all 0.3s ease;
            overflow-x: hidden;
        }

        /* --- SIDEBAR & TOGGLE LOGIC --- */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--primary-brown);
            position: fixed;
            height: 100%;
            left: 0; top: 0;
            z-index: 1001;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Desktop Toggle */
        body.sidebar-off .sidebar { transform: translateX(-100%); }
        body.sidebar-off .main-content { margin-left: 0; width: 100%; }

        /* Sidebar Close Button */
        .sidebar-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s ease;
            z-index: 1002;
        }
        .sidebar-close-btn:hover { background: rgba(255, 255, 255, 0.3); transform: rotate(90deg); }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 40px;
            width: calc(100% - var(--sidebar-width));
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1000;
            backdrop-filter: blur(2px);
        }

        /* --- HEADER --- */
        header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 40px;
        }

        .welcome-container { display: flex; align-items: center; gap: 15px; }
        .welcome-text h1 { font-size: 26px; font-weight: 700; color: var(--text-dark); }
        .welcome-text p { color: var(--text-light); font-size: 14px; }
        
        /* HAMBURGER ICON STYLING */
        #toggle-btn { 
            font-size: 22px; 
            cursor: pointer; 
            color: var(--text-dark); 
            background: var(--white);
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
            transition: 0.3s;
        }
        #toggle-btn:hover { transform: scale(1.05); }

        .user-profile { display: flex; align-items: center; gap: 15px; }
        .profile-info img { width: 48px; height: 48px; border-radius: 14px; object-fit: cover; box-shadow: var(--shadow); border: 2px solid var(--white); }

        /* --- TIMELINE DESIGN --- */
        .timeline-container {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            padding: 20px 0;
        }

        .timeline-container::before {
            content: '';
            position: absolute;
            left: 31px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--border-line);
            border-radius: 10px;
        }

        .timeline-item {
            position: relative;
            padding-left: 80px;
            margin-bottom: 50px;
        }

        .timeline-marker {
            position: absolute;
            left: 20px;
            top: 0;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: var(--white);
            border: 4px solid var(--primary-purple);
            box-shadow: 0 0 0 5px var(--bg-color);
            z-index: 2;
            transition: 0.3s;
        }

        .timeline-item.history .timeline-marker { border-color: var(--text-light); }

        .timeline-date-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 12px;
            display: block;
        }

        .timeline-card {
            background: var(--white);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.02);
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .timeline-card::after {
            content: '';
            position: absolute;
            left: 0; top: 0; width: 5px; height: 100%;
            background: var(--primary-purple);
            opacity: 0;
            transition: 0.3s;
        }

        .timeline-card:hover { transform: translateX(10px); }
        .timeline-card:hover::after { opacity: 1; }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .card-header h4 { font-size: 18px; font-weight: 700; color: var(--text-dark); }

        .status-badge {
            font-size: 11px;
            padding: 6px 14px;
            border-radius: 50px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-latest { background: rgba(123, 97, 255, 0.1); color: var(--primary-purple); }
        .status-history { background: rgba(0, 0, 0, 0.05); color: var(--text-dark); }
        body.dark-mode .status-history { background: rgba(255, 255, 255, 0.05); }

        .diagnosis-text {
            font-size: 14px;
            color: var(--text-dark);
            line-height: 1.8;
            opacity: 0.8;
        }

        .doctor-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-line);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .doctor-footer img {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            object-fit: cover;
        }

        .doctor-footer span { font-size: 12px; font-weight: 600; color: var(--text-light); }

        /* Responsive Mobile Handling */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; padding: 25px; width: 100%; }
            body.toggled .sidebar { transform: translateX(0); }
            body.toggled .sidebar-overlay { display: block; }
            
            .timeline-container::before { left: 21px; }
            .timeline-marker { left: 10px; }
            .timeline-item { padding-left: 50px; }
        }
    </style>
</head>
<body class="<?php echo $dark_class; ?>">

    <div class="sidebar-overlay" id="overlay"></div>

    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close-btn" id="sidebar-close-btn">
            <i class="fa-solid fa-times"></i>
        </button>
        <?php include 'sidebar.php'; ?>
    </aside>

    <main class="main-content">
        <header>
            <div class="welcome-container">
                <div id="toggle-btn">
                    <i class="fa-solid fa-bars-staggered"></i>
                </div>
                <div class="welcome-text">
                    <h1>Health Journey</h1>
                    <p>Your medical evolution through time</p>
                </div>
            </div>
            
            <div class="user-profile">
                <div class="profile-info">
                    <div style="text-align: right;">
                        <h4 style="font-size: 14px; font-weight: 700;"><?php echo htmlspecialchars($display_name); ?></h4>
                        <p style="font-size: 11px; color: var(--text-light);">Member ID: #<?php echo $patient_id; ?></p>
                    </div>
                    <img src="<?php echo htmlspecialchars($display_img); ?>" alt="Profile">
                </div>
            </div>
        </header>

        <div class="timeline-container">
            <?php 
            if ($timeline_res->num_rows > 0): 
                $count = 0;
                while($row = $timeline_res->fetch_assoc()): 
                    $isLatest = ($count === 0);
                    $itemClass = $isLatest ? 'latest' : 'history';
                    $dateStr = date('d M, Y', strtotime($row['created_at']));
                    $title = !empty($row['diagnosis']) ? htmlspecialchars($row['diagnosis']) : 'Clinical Update';
                    $desc = !empty($row['internal_doctor_notes']) ? htmlspecialchars($row['internal_doctor_notes']) : 'No clinical notes recorded for this session.';
            ?>
                <div class="timeline-item <?php echo $itemClass; ?>">
                    <div class="timeline-marker"></div>
                    <span class="timeline-date-label"><?php echo $dateStr; ?></span>
                    
                    <div class="timeline-card">
                        <div class="card-header">
                            <h4><?php echo $title; ?></h4>
                            <span class="status-badge <?php echo $isLatest ? 'status-latest' : 'status-history'; ?>">
                                <?php echo $isLatest ? 'Latest Update' : 'Historical Record'; ?>
                            </span>
                        </div>
                        
                        <div class="diagnosis-text">
                            <?php echo nl2br($desc); ?>
                        </div>

                        <div class="doctor-footer">
                            <img src="https://ui-avatars.com/api/?name=Clinical+Team&background=7B3F00&color=fff" alt="Team">
                            <span>Verified by Doctor Admin Team</span>
                        </div>
                    </div>
                </div>
            <?php 
                $count++;
                endwhile; 
            else: 
            ?>
                <div class="timeline-item latest">
                    <div class="timeline-marker"></div>
                    <span class="timeline-date-label"><?php echo date('d M, Y'); ?></span>
                    <div class="timeline-card">
                        <div class="card-header">
                            <h4>Welcome to Safe Space</h4>
                        </div>
                        <p class="diagnosis-text">Your medical history and timeline updates will appear here once your physician records your first clinical session.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        const toggleBtn = document.getElementById('toggle-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const overlay = document.getElementById('overlay');
        const body = document.body;

        // Unified toggle function
        function handleSidebar() {
            if (window.innerWidth > 768) {
                body.classList.toggle('sidebar-off'); 
            } else {
                body.classList.toggle('toggled'); 
            }
        }

        function closeSidebar() {
            body.classList.remove('toggled');
            if (window.innerWidth > 768) body.classList.add('sidebar-off');
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', handleSidebar);
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                body.classList.remove('toggled');
            });
        }

        // Keyboard Support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSidebar();
        });
    </script>
</body>
</html>