<?php 
session_start();
include '../config/db.php'; 

// 1. Fetch dark mode preference for the session if not set
if(!isset($_SESSION['pref_dark_mode'])) {
    if(isset($_SESSION['user_id'])) {
        $p_id = $_SESSION['user_id'];
        $res = $conn->query("SELECT pref_dark_mode FROM patients WHERE patient_id = '$p_id'");
        if($res && $row = $res->fetch_assoc()) {
            $_SESSION['pref_dark_mode'] = $row['pref_dark_mode'];
        }
    }
}
$dark_class = (isset($_SESSION['pref_dark_mode']) && $_SESSION['pref_dark_mode'] == 1) ? 'dark-mode' : '';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../dashboard.php");
    exit();
}

$patient_id = $_SESSION['user_id']; 

// Fetch Patient Details
$sql_patient = "SELECT * FROM patients WHERE patient_id = '$patient_id'";
$result_patient = $conn->query($sql_patient);
$patient_data = $result_patient->fetch_assoc();

$display_name = !empty($patient_data['full_name']) ? $patient_data['full_name'] : 'Patient';
$db_image = !empty($patient_data['profile_image']) ? $patient_data['profile_image'] : 'https://i.pravatar.cc/150?img=33';
$display_img = $db_image . "?v=" . time();

// --- AJAX HANDLER: Only runs when a date is clicked ---
if (isset($_GET['ajax_fetch_date'])) {
    $target_date = $_GET['ajax_fetch_date'];
    $sql = "SELECT * FROM appointments WHERE patient_id = '$patient_id' AND appointment_date = '$target_date' AND status NOT IN ('Cancelled') ORDER BY appointment_time ASC";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        while($row = $res->fetch_assoc()) {
            $f_time = date("h:i A", strtotime($row['appointment_time']));
            echo '<div class="request-item">
                    <div class="patient-info">
                        <div class="schedule-icon"><i class="fa-solid fa-clock"></i></div>
                        <div class="patient-text"><h4>Consultation</h4><p>'.htmlspecialchars($row['initial_health_issue']).' at '.$f_time.'</p>';
            if(!empty($row['meeting_link'])) echo '<div style="margin-top:8px;"><a href="'.$row['meeting_link'].'" target="_blank" class="join-btn"><i class="fa-solid fa-video"></i> Join Session</a></div>';
            echo '</div></div><span class="status-badge">Scheduled</span></div>';
        }
    } else {
        echo "<div class='empty-state'><i class='fa-solid fa-calendar-day'></i><p>No appointments for this date.</p></div>";
    }
    exit;
}

// --- REAL DATA FETCHING ---
$today = date('Y-m-d');
$visit_sql = "SELECT COUNT(*) as total FROM appointments WHERE patient_id = '$patient_id' AND appointment_date >= '$today' AND status IN ('Confirmed', 'Approved', 'Pending')";
$real_upcoming_count = ($res = $conn->query($visit_sql)) ? $res->fetch_assoc()['total'] : 0;

$presc_sql = "SELECT COUNT(*) as total FROM prescriptions WHERE patient_id = '$patient_id'";
$real_prescription_count = ($res = $conn->query($presc_sql)) ? $res->fetch_assoc()['total'] : 0;

include 'check_notifications.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --bg-color: #F8F9FD;
            --sidebar-width: 240px;
            --primary-purple: #7B61FF;
            --primary-red: #FF5C60;
            --primary-orange: #FFB800;
            --primary-blue: #1FB6FF;
            --text-dark: #2D3436;
            --text-light: #9DA4B0;
            --white: #FFFFFF;
            --shadow: 0 10px 30px rgba(0,0,0,0.04);
            --radius: 24px;
        }

        body.dark-mode {
            --bg-color: #0F172A;
            --text-dark: #F8FAFC;
            --text-light: #94A3B8;
            --white: #1E293B;
            --shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* --- SIDEBAR TOGGLE LOGIC --- */
        .sidebar {
            width: var(--sidebar-width);
            background: #7B3F00;
            padding: 30px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            z-index: 1001;
            left: 0; top: 0;
            transition: transform 0.3s ease, width 0.3s ease;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px 40px;
            width: calc(100% - var(--sidebar-width));
            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        /* State when sidebar is OFF (Laptop & Mobile) */
        body.sidebar-off .sidebar {
            transform: translateX(-100%);
        }

        body.sidebar-off .main-content {
            margin-left: 0;
            width: 100%;
        }

        /* Overlay for Mobile only */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: 1000;
        }

        /* --- HEADER --- */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .welcome-text h1 { font-size: 26px; font-weight: 700; color: var(--text-dark); }
        .welcome-text p { color: var(--text-light); font-size: 14px; }

        .search-bar {
            background: var(--white);
            padding: 12px 25px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 350px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.02);
        }
        .search-bar input { border: none; outline: none; width: 100%; color: var(--text-dark); background: transparent; font-size: 13px; }

        /* --- UI COMPONENTS (CARDS, PANELS, ETC) --- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            padding: 25px; 
            border-radius: var(--radius); 
            color: #FFFFFF;
            display: flex; 
            align-items: center; 
            gap: 20px;
            position: relative;
            overflow: hidden;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .card::after {
            content: ''; position: absolute; top: -50%; right: -20%; width: 120px; height: 120px;
            background: rgba(255,255,255,0.1); border-radius: 50%;
        }
        .card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        
        .card-icon { 
            background: rgba(255,255,255,0.25); 
            width: 55px; height: 55px; 
            border-radius: 18px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 22px; backdrop-filter: blur(5px);
        }

        .card.purple { background: linear-gradient(135deg, #8E78FF, #7B61FF); }
        .card.red { background: linear-gradient(135deg, #FF7E82, #FF5C60); }
        .card.orange { background: linear-gradient(135deg, #FFC933, #FFB800); }
        .card.blue { background: linear-gradient(135deg, #47C5FF, #1FB6FF); }

        .dashboard-grid { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 25px; }
        .panel { 
            background: var(--white); 
            border-radius: var(--radius); 
            padding: 25px; 
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.03);
        }
        .panel-header h3 { font-size: 17px; font-weight: 600; }
        
        .request-item { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 20px; 
            padding: 12px;
            border-radius: 16px;
            transition: 0.3s;
        }
        .request-item:hover { background: rgba(0,0,0,0.01); }

        .schedule-icon {
            width: 40px; height: 40px; border-radius: 12px; background: #F0F4F8;
            display: flex; align-items: center; justify-content: center; color: #7B3F00;
        }

        .join-btn { 
            background: #7B3F00; 
            color: white; 
            text-decoration: none; 
            padding: 8px 16px; 
            border-radius: 10px; 
            font-size: 12px; 
            font-weight: 600; 
            display: inline-block;
            transition: 0.3s;
        }
        .join-btn:hover { opacity: 0.9; transform: scale(1.05); }

        .status-badge {
            font-size: 11px; font-weight: 600; color: var(--primary-blue);
            background: rgba(31, 182, 255, 0.1); padding: 6px 12px; border-radius: 8px;
        }

        .calendar-dates { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-top: 20px; }
        .date-num { 
            padding: 10px 5px; border-radius: 12px; cursor: pointer; 
            text-align: center; font-size: 13px; font-weight: 500;
        }
        .date-num.active { background: #7B3F00 !important; color: #fff !important; box-shadow: 0 5px 15px rgba(123,63,0,0.3); }

        .empty-state { text-align: center; padding: 40px 0; color: var(--text-light); }
        .empty-state i { font-size: 30px; margin-bottom: 10px; display: block; }

        /* Responsive Mobile Handling */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; width: 100%; padding: 20px; }
            
            /* On mobile, body sidebar-off actually means OPENING it (reverse logic for better ux) */
            body.mobile-sidebar-on .sidebar { transform: translateX(0); }
            body.mobile-sidebar-on .sidebar-overlay { display: block; }
            .search-bar { display: none; }
        }
    </style>
</head>
<body class="<?php echo $dark_class; ?>">

    <div class="sidebar-overlay" id="overlay"></div>

    <div class="sidebar" id="sidebar-container">
        <?php include 'sidebar.php'; ?>
    </div>

    <main class="main-content">
        <header>
            <div class="welcome-container" style="display:flex; align-items:center; gap:15px;">
                <i class="fa-solid fa-bars" id="toggle-btn" style="font-size:20px; cursor:pointer;"></i>
                <div class="welcome-text">
                    <h1>Hello, <?php echo htmlspecialchars(explode(' ', $display_name)[0]); ?>! 👋</h1>
                    <p>How are you feeling today?</p>
                </div>
            </div>
            
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass" style="color:var(--text-light)"></i>
                <input type="text" id="searchInput" placeholder="Search appointments or records...">
            </div>

            <div class="user-profile" style="display:flex; align-items:center; gap:20px;">
                <a href="notification.php" class="icon-btn" style="position:relative; text-decoration:none; color:var(--text-light); background:var(--white); padding:10px; border-radius:12px; box-shadow:var(--shadow);">
                    <i class="fa-regular fa-bell"></i>
                    <?php if(isset($unread_count) && $unread_count > 0): ?>
                        <span class="notify-badge" style="position:absolute; top:-2px; right:-2px; background:var(--primary-red); width:10px; height:10px; border-radius:50%; border:2px solid var(--white);"></span>
                    <?php endif; ?>
                </a>

                <div class="profile-info" style="display:flex; align-items:center; gap:12px;">
                    <img src="<?php echo htmlspecialchars($display_img); ?>" alt="Profile" style="width:45px; height:45px; border-radius:14px; object-fit:cover;">
                </div>
            </div>
        </header>

        <section class="stats-grid">
            <a href="myapp.php" style="text-decoration: none;">
                <div class="card purple">
                    <div class="card-icon"><i class="fa-regular fa-calendar"></i></div>
                    <div class="card-info"><h2><?php echo sprintf("%02d", $real_upcoming_count); ?></h2><span>Upcoming Visits</span></div>
                </div>
            </a>
            <a href="prescrip.php" style="text-decoration: none;">
                <div class="card red">
                    <div class="card-icon"><i class="fa-solid fa-pills"></i></div>
                    <div class="card-info"><h2><?php echo sprintf("%02d", $real_prescription_count); ?></h2><span>Prescriptions</span></div>
                </div>
            </a>
            <div class="card orange">
                <div class="card-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                <div class="card-info"><h2>Normal</h2><span>Health Status</span></div>
            </div>
            <div class="card blue">
                <div class="card-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                <div class="card-info"><h2>$120</h2><span>Due Balance</span></div>
            </div>
        </section>

        <section class="dashboard-grid">
            <div class="panel">
                <div class="panel-header" style="display:flex; justify-content:space-between; margin-bottom:20px;">
                    <h3>Notifications</h3><a href="notification.php" style="color:var(--primary-blue); font-size:12px; text-decoration:none;">View All</a>
                </div>
                <div id="request-list">
                    <?php 
                        $dash_notif_sql = "SELECT * FROM notifications WHERE patient_id = '$patient_id' ORDER BY created_at DESC LIMIT 2";
                        $dash_notif_res = $conn->query($dash_notif_sql);
                        if($dash_notif_res && $dash_notif_res->num_rows > 0) {
                            while($d_row = $dash_notif_res->fetch_assoc()) {
                    ?>
                        <div class="request-item">
                            <div class="patient-info" style="display:flex; gap:12px;">
                                <div style="background:#fef3f2; color:#f04438; width:35px; height:35px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:14px;">
                                    <i class="fa-solid fa-bell-concierge"></i>
                                </div>
                                <div class="patient-text">
                                    <h4 style="font-size:13px;"><?php echo htmlspecialchars($d_row['title']); ?></h4>
                                    <p style="font-size:11px; color:var(--text-light);"><?php echo substr(htmlspecialchars($d_row['message']), 0, 30) . '...'; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php } } else { echo '<p style="font-size:12px; color:var(--text-light); text-align:center;">No alerts.</p>'; } ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header" style="margin-bottom:20px;"><h3>Activity Overview</h3></div>
                <div class="chart-container" style="height:200px; position:relative;"><canvas id="genderChart"></canvas></div>
            </div>

            <div class="panel">
                <div class="panel-header" style="display:flex; justify-content:space-between; margin-bottom:20px;">
                    <h3>Upcoming Schedule</h3><a href="myapp.php" style="color:var(--primary-blue); font-size:12px; text-decoration:none;">See All</a>
                </div>
                
                <div id="today-appointments">
                    <?php
                        $sql_schedule = "SELECT * FROM appointments WHERE patient_id = '$patient_id' AND appointment_date = '$today' AND status NOT IN ('Cancelled') ORDER BY appointment_time ASC LIMIT 3";
                        $res_schedule = $conn->query($sql_schedule);
                        if ($res_schedule && $res_schedule->num_rows > 0) {
                            while($row = $res_schedule->fetch_assoc()) {
                                $formatted_time = date("h:i A", strtotime($row['appointment_time']));
                    ?>
                        <div class="request-item">
                            <div class="patient-info" style="display:flex; gap:12px; align-items:flex-start;">
                                <div class="schedule-icon"><i class="fa-solid fa-calendar-check"></i></div>
                                <div class="patient-text">
                                    <h4 style="font-size:13px;">Dr. Usri Sengupta</h4>
                                    <p style="font-size:11px;"><?php echo $formatted_time; ?></p>
                                    <?php if(!empty($row['meeting_link'])): ?>
                                        <div style="margin-top:8px;"><a href="<?php echo $row['meeting_link']; ?>" target="_blank" class="join-btn">Join Now</a></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php } } else { echo "<div class='empty-state'><p style='font-size:12px;'>No sessions today.</p></div>"; } ?>
                </div>

                <div class="calendar-dates">
                    <?php
                        for ($i = -3; $i <= 3; $i++) {
                            $full_date = date('Y-m-d', strtotime("$i days"));
                            $day_val = date('j', strtotime("$i days"));
                            $day_name = date('D', strtotime("$i days"))[0];
                            $is_active = ($i == 0) ? 'active' : '';
                            echo '<div class="date-num '.$is_active.'" onclick="fetchDaySchedule(\''.$full_date.'\', this)">
                                    <span style="font-size:10px; display:block; opacity:0.6;">'.$day_name.'</span>'.$day_val.'
                                  </div>';
                        }
                    ?>
                </div>
            </div>
        </section>
    </main>

    <script>
        const toggleBtn = document.getElementById('toggle-btn');
        const overlay = document.getElementById('overlay');
        const body = document.body;

        toggleBtn.addEventListener('click', () => {
            if (window.innerWidth > 768) {
                // Laptop Toggle: On and Off
                body.classList.toggle('sidebar-off');
            } else {
                // Mobile Toggle: slide In and Out
                body.classList.toggle('mobile-sidebar-on');
            }
        });

        overlay.addEventListener('click', () => {
            body.classList.remove('mobile-sidebar-on');
        });

        // Chart Initialization
        const ctx = document.getElementById('genderChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Activity', 'Sleep', 'Water'],
                datasets: [{ data: [60, 25, 15], backgroundColor: ['#FFB800', '#1FB6FF', '#7B61FF'], borderWidth: 0, hoverOffset: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '80%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } }
        });

        // AJAX Function for Calendar
        function fetchDaySchedule(date, element) {
            document.querySelectorAll('.date-num').forEach(el => el.classList.remove('active'));
            element.classList.add('active');

            fetch('dashboard.php?ajax_fetch_date=' + date)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('today-appointments').innerHTML = html;
                });
        }
    </script>
</body>
</html>