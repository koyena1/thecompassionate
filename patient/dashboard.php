<?php 
session_start();
include '../config/db.php'; 

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
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

// --- NEW: Include Notification Logic ---
// This will generate notifications if they don't exist and give us $unread_count
include 'check_notifications.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* CSS VARIABLES */
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
            overflow-x: hidden; 
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-width);
            background: #7B3F00;
            padding: 30px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            z-index: 999;
            transition: transform 0.3s ease;
            left: 0;
            top: 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 5px;
            transition: 0.3s;
            font-size: 14px;
        }

        .menu-item:hover, .menu-item.active {
            background-color: var(--text-dark);
            color: var(--white);
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px 40px;
            width: 100%;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .search-bar {
            background: var(--white);
            padding: 10px 20px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 300px;
            color: var(--text-light);
            box-shadow: var(--shadow);
        }

        .search-bar input {
            border: none;
            outline: none;
            width: 100%;
            color: var(--text-dark);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .icon-btn {
            background: var(--white);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            box-shadow: var(--shadow);
            cursor: pointer;
            text-decoration: none; /* Added so link works */
            position: relative; /* For red dot positioning */
        }

        /* --- NEW RED DOT STYLE --- */
        .notify-badge {
            position: absolute;
            top: 0px;
            right: 0px;
            width: 12px;
            height: 12px;
            background-color: var(--primary-red);
            border-radius: 50%;
            border: 2px solid var(--white);
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-info img {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            object-fit: cover;
        }

        .welcome-container { display: flex; align-items: center; }
        .welcome-text h1 { font-size: 24px; font-weight: 600; }
        .welcome-text p { color: var(--text-light); font-size: 14px; }

        #toggle-btn {
            font-size: 24px;
            cursor: pointer;
            margin-right: 20px;
            color: var(--text-dark);
            display: block;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            padding: 25px;
            border-radius: var(--radius);
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: all 0.3s;
        }

        .card:hover { transform: translateY(-5px); }
        
        .card-icon {
            background: rgba(255,255,255,0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .card-info h2 { font-size: 28px; }
        .card-info span { font-size: 13px; opacity: 0.9; }
        .card.purple { background: var(--primary-purple); }
        .card.red { background: var(--primary-red); }
        .card.orange { background: var(--primary-orange); }
        .card.blue { background: var(--primary-blue); }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr;
            gap: 25px;
        }

        .panel {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .panel-header h3 { font-size: 18px; }
        .view-all { color: var(--primary-blue); font-size: 12px; cursor: pointer; text-decoration: none; }

        .request-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .hidden { display: none !important; }

        .patient-info { display: flex; align-items: center; gap: 15px; }
        .patient-info img { width: 45px; height: 45px; border-radius: 50%; }
        .patient-text h4 { font-size: 14px; margin-bottom: 2px; }
        .patient-text p { font-size: 11px; color: var(--text-light); }

        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            position: relative;
            height: 200px; 
            width: 100%;
        }

        /* Calendar */
        .calendar-dates {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 15px;
            font-size: 12px;
            color: var(--text-light);
            text-align: center;
        }
        
        .date-num { padding: 8px; border-radius: 8px; cursor: pointer; }
        .date-num.active { background-color: var(--primary-blue); color: var(--white); }

        .join-btn {
            background-color: var(--primary-blue);
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; padding: 20px; }
            body.toggled .sidebar { transform: translateX(0); }
            body.toggled .main-content { opacity: 0.5; pointer-events: none; }
            .search-bar { width: 100%; order: 3; margin-top: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div class="welcome-container">
                <i class="fa-solid fa-bars" id="toggle-btn"></i>
                <div class="welcome-text">
                    <h1>Welcome, <?php echo htmlspecialchars($display_name); ?></h1>
                    <p>Track your health journey here</p>
                </div>
            </div>
            
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search Doctors, Appointments...">
            </div>

            <div class="user-profile">
                <a href="notification.php" class="icon-btn">
    <i class="fa-regular fa-bell"></i>
    
    <?php if(isset($unread_count) && $unread_count > 0): ?>
        <span class="notify-badge"></span>
    <?php endif; ?>
    
</a>

                <div class="profile-info">
                    <div style="text-align: right;">
                        <h4 style="font-size: 14px;"><?php echo htmlspecialchars($display_name); ?></h4>
                        <p style="font-size: 11px; color: var(--text-light);">Patient</p>
                    </div>
                    <img src="<?php echo htmlspecialchars($display_img); ?>" alt="Patient Profile">
                </div>
            </div>
        </header>

        <section class="stats-grid">
            <div class="card purple"><div class="card-icon"><i class="fa-regular fa-calendar"></i></div><div class="card-info"><h2>04</h2><span>Upcoming Visits</span></div></div>
            <div class="card red"><div class="card-icon"><i class="fa-solid fa-file-medical"></i></div><div class="card-info"><h2>12</h2><span>Prescriptions</span></div></div>
            <div class="card orange"><div class="card-icon"><i class="fa-solid fa-heart-pulse"></i></div><div class="card-info"><h2>Good</h2><span>Health Status</span></div></div>
            <div class="card blue"><div class="card-icon"><i class="fa-solid fa-wallet"></i></div><div class="card-info"><h2>$120</h2><span>Pending Bill</span></div></div>
        </section>

        <section class="dashboard-grid">
            <div class="panel">
                <div class="panel-header"><h3>Recent Notifications</h3><a href="notification.php" class="view-all">View All</a></div>
                <div id="request-list">
                    <?php 
                        // Fetch latest 2 notifications for dashboard widget
                        $dash_notif_sql = "SELECT * FROM notifications WHERE patient_id = '$patient_id' ORDER BY created_at DESC LIMIT 2";
                        $dash_notif_res = $conn->query($dash_notif_sql);
                        
                        if($dash_notif_res->num_rows > 0) {
                            while($d_row = $dash_notif_res->fetch_assoc()) {
                                $d_time = date("h:i A", strtotime($d_row['created_at']));
                    ?>
                        <div class="request-item">
                            <div class="patient-info">
                                <img src="https://i.pravatar.cc/150?img=11" alt="">
                                <div class="patient-text">
                                    <h4><?php echo htmlspecialchars($d_row['title']); ?></h4>
                                    <p><?php echo substr(htmlspecialchars($d_row['message']), 0, 30) . '...'; ?></p>
                                </div>
                            </div>
                            <span style="font-size: 12px; color: var(--text-light);"><?php echo $d_time; ?></span>
                        </div>
                    <?php 
                            }
                        } else {
                            echo '<p style="font-size:12px; color:var(--text-light);">No recent notifications.</p>';
                        }
                    ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><h3>Health Stats</h3><a href="#" class="view-all">Weekly <i class="fa-solid fa-chevron-down"></i></a></div>
                <div class="chart-container"><canvas id="genderChart"></canvas></div>
            </div>

            <div class="panel">
                <div class="panel-header"><h3>Upcoming Schedule</h3><a href="#" class="view-all">See All</a></div>
                <div id="today-appointments">
                    <?php
                        $today = date('Y-m-d');
                        $sql_schedule = "SELECT * FROM appointments WHERE patient_id = $patient_id AND appointment_date >= '$today' AND status = 'Confirmed' ORDER BY appointment_date ASC LIMIT 3";
                        $res_schedule = $conn->query($sql_schedule);
                        if ($res_schedule->num_rows > 0) {
                            while($row = $res_schedule->fetch_assoc()) {
                                $displayDate = ($row['appointment_date'] == $today) ? "Today" : $row['appointment_date'];
                    ?>
                        <div class="request-item">
                            <div class="patient-info">
                                <img src="https://i.pravatar.cc/150?img=59" alt="">
                                <div class="patient-text">
                                    <h4>Dr. Smith</h4> <p><?php echo htmlspecialchars($row['initial_health_issue']); ?></p>
                                    <?php if(!empty($row['meeting_link'])): ?>
                                        <div style="margin-top:5px;"><a href="<?php echo $row['meeting_link']; ?>" target="_blank" class="join-btn"><i class="fa-solid fa-video"></i> Join</a></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span style="font-size: 12px; color: var(--primary-blue); background: rgba(31, 182, 255, 0.1); padding: 4px 8px; border-radius: 4px;"><?php echo $displayDate; ?></span>
                        </div>
                    <?php } } else { echo "<p style='color:var(--text-light); font-size:12px; text-align:center;'>No upcoming appointments.</p>"; } ?>
                </div>
                <div style="margin-top: 30px;">
                    <div class="calendar-dates">
                        <div class="date-num">S</div><div class="date-num">M</div><div class="date-num">T</div><div class="date-num">W</div><div class="date-num">T</div><div class="date-num">F</div><div class="date-num">S</div>
                        <div class="date-num">3</div><div class="date-num">4</div><div class="date-num">5</div><div class="date-num active">6</div><div class="date-num">7</div><div class="date-num">8</div><div class="date-num">9</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        const toggleBtn = document.getElementById('toggle-btn');
        const body = document.body;
        toggleBtn.addEventListener('click', () => { body.classList.toggle('toggled'); });

        const ctx = document.getElementById('genderChart').getContext('2d');
        const genderChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Activity', 'Sleep', 'Water'],
                datasets: [{ data: [60, 25, 15], backgroundColor: ['#FFB800', '#1FB6FF', '#7B61FF'], borderWidth: 0, hoverOffset: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false } } }
        });
    </script>
</body>
</html>