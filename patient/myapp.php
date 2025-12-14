<?php 
// FILE: psychiatrist/patient/myappoints.php
include '../config/db.php'; 

// Simulate logged-in user (Replace '1' with $_SESSION['patient_id'] later)
$patient_id = 1; 

// Fetch Patient Info
$sql_patient = "SELECT * FROM patients WHERE patient_id = $patient_id";
$res_patient = $conn->query($sql_patient);
$patient = $res_patient->fetch_assoc();
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
        /* --- 1. CSS VARIABLES & RESET --- */
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

        /* --- 3. MAIN CONTENT --- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px 40px;
            transition: margin-left 0.3s ease;
            width: 100%;
        }

        body.toggled .sidebar { transform: translateX(-100%); }
        body.toggled .main-content { margin-left: 0; }

        #toggle-btn {
            font-size: 24px; cursor: pointer; margin-right: 20px; color: var(--text-dark); transition: 0.3s; display: block;
        }
        #toggle-btn:hover { color: var(--primary-blue); }

        header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;
        }

        .search-bar {
            background: var(--white); padding: 10px 20px; border-radius: 30px; display: flex; align-items: center; gap: 10px; width: 300px; color: var(--text-light); box-shadow: var(--shadow);
        }

        .search-bar input { border: none; outline: none; width: 100%; color: var(--text-dark); }

        .user-profile { display: flex; align-items: center; gap: 20px; }
        .icon-btn {
            background: var(--white); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--text-light); box-shadow: var(--shadow); cursor: pointer;
        }

        .profile-info { display: flex; align-items: center; gap: 10px; }
        .profile-info img { width: 45px; height: 45px; border-radius: 12px; object-fit: cover; }

        /* Welcome Text */
        .welcome-container { display: flex; align-items: center; }
        .welcome-text h1 { font-size: 24px; font-weight: 600; }
        .welcome-text p { color: var(--text-light); font-size: 14px; }

        /* Stats Cards */
        .stats-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;
        }

        .card {
            padding: 25px; border-radius: var(--radius); color: var(--white); display: flex; align-items: center; gap: 20px; box-shadow: var(--shadow); cursor: pointer; transition: all 0.3s;
        }
        .card:hover { transform: translateY(-8px); }
        .card-icon {
            background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;
        }
        .card-info h2 { font-size: 28px; }
        .card-info span { font-size: 13px; opacity: 0.9; }

        .card.purple { background: var(--primary-purple); }
        .card.red { background: var(--primary-red); }
        .card.orange { background: var(--primary-orange); }
        .card.blue { background: var(--primary-blue); }

        /* Dashboard Lower Grid */
        .dashboard-grid {
            display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 25px;
        }

        .panel {
            background: var(--white); border-radius: var(--radius); padding: 25px; box-shadow: var(--shadow);
        }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .panel-header h3 { font-size: 18px; }
        .view-all { color: var(--primary-blue); font-size: 12px; cursor: pointer; text-decoration: none; }

        /* Request Items (Used in overview) */
        .request-item {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; transition: all 0.5s ease;
        }
        .hidden-section, .hidden { display: none !important; }

        .patient-info { display: flex; align-items: center; gap: 15px; }
        .patient-info img { width: 45px; height: 45px; border-radius: 50%; }
        .patient-text h4 { font-size: 14px; margin-bottom: 2px; }
        .patient-text p { font-size: 11px; color: var(--text-light); }

        /* Chart */
        .chart-container { display: flex; justify-content: center; align-items: center; margin: 20px 0; position: relative; height: 200px; width: 100%; }

        /* --- BOOKING FORM --- */
        .booking-container { background: var(--white); border-radius: var(--radius); padding: 40px; box-shadow: var(--shadow); max-width: 800px; margin: 0 auto; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: var(--text-dark); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #eee; border-radius: 10px; outline: none; background: #FAFAFA; color: var(--text-dark); }
        .full-width { grid-column: span 2; }
        .book-btn { background: var(--primary-blue); color: white; border: none; padding: 15px 30px; border-radius: 12px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 10px; transition: 0.3s; }
        .book-btn:hover { background: #0d9adb; }

        /* --- MY APPOINTMENTS STYLES --- */
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

        /* Responsive */
        @media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .dashboard-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; padding: 20px; }
            body.toggled .sidebar { transform: translateX(0); }
            body.toggled .main-content { opacity: 0.5; pointer-events: none; }
            .search-bar { width: 100%; } .appt-card { flex-direction: column; align-items: flex-start; gap: 15px; } .appt-actions { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php';?>
  
    <main class="main-content">
        <header>
            <div class="welcome-container">
                <i class="fa-solid fa-bars" id="toggle-btn"></i>
                <div class="welcome-text">
                    <h1>Welcome, <?php echo htmlspecialchars($patient['full_name']); ?></h1>
                    <p>Track your health journey here</p>
                </div>
            </div>
            
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search Doctors, Appointments...">
            </div>

            <div class="user-profile">
                <div class="icon-btn"><i class="fa-regular fa-bell"></i></div>
                <div class="profile-info">
                    <div style="text-align: right;">
                        <h4 style="font-size: 14px;"><?php echo htmlspecialchars($patient['full_name']); ?></h4>
                        <p style="font-size: 11px; color: var(--text-light);">Patient</p>
                    </div>
                    <img src="https://i.pravatar.cc/150?img=33" alt="Patient Profile">
                </div>
            </div>
        </header>

        <div id="overview-section">
            <section class="stats-grid">
                <?php 
                    $sql_count = "SELECT count(*) as count FROM appointments WHERE patient_id = $patient_id AND appointment_date >= CURDATE()";
                    $row_count = $conn->query($sql_count)->fetch_assoc();
                ?>
                <div class="card purple">
                    <div class="card-icon"><i class="fa-regular fa-calendar"></i></div>
                    <div class="card-info"><h2><?php echo $row_count['count']; ?></h2><span>Upcoming Visits</span></div>
                </div>
                <div class="card red">
                    <div class="card-icon"><i class="fa-solid fa-file-medical"></i></div>
                    <div class="card-info"><h2>12</h2><span>Prescriptions</span></div>
                </div>
                <div class="card orange">
                    <div class="card-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                    <div class="card-info"><h2>Good</h2><span>Health Status</span></div>
                </div>
                <div class="card blue">
                    <div class="card-icon"><i class="fa-solid fa-wallet"></i></div>
                    <div class="card-info"><h2>$120</h2><span>Pending Bill</span></div>
                </div>
            </section>

            <section class="dashboard-grid">
                <div class="panel">
                    <div class="panel-header"><h3>Recent Notifications</h3><a href="#" class="view-all">View All</a></div>
                    <div id="request-list">
                        <div class="request-item">
                            <div class="patient-info">
                                <img src="https://i.pravatar.cc/150?img=11" alt="">
                                <div class="patient-text"><h4>Admin</h4><p>Appointment confirmed</p></div>
                            </div>
                            <span style="font-size: 12px; color: var(--text-light);">10:00 AM</span>
                        </div>
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
                            $sql_upcoming = "SELECT a.*, d.full_name as doctor_name 
                                             FROM appointments a 
                                             LEFT JOIN admin_users d ON a.admin_id = d.admin_id
                                             WHERE a.patient_id = $patient_id AND a.appointment_date >= CURDATE()
                                             ORDER BY a.appointment_date ASC LIMIT 3";
                            $res_upcoming = $conn->query($sql_upcoming);

                            if($res_upcoming->num_rows > 0){
                                while($row = $res_upcoming->fetch_assoc()){
                                    echo '<div class="request-item">';
                                    echo '  <div class="patient-info">';
                                    echo '      <img src="https://i.pravatar.cc/150?img=59" alt="">';
                                    echo '      <div class="patient-text"><h4>'.htmlspecialchars($row['doctor_name']).'</h4><p>'.date("d M", strtotime($row['appointment_date'])).'</p></div>';
                                    echo '  </div>';
                                    echo '  <span style="font-size: 12px; color: var(--primary-blue); background: rgba(31, 182, 255, 0.1); padding: 4px 8px; border-radius: 4px;">'.date("g:i A", strtotime($row['appointment_time'])).'</span>';
                                    echo '</div>';
                                }
                            } else {
                                echo "<p style='color:#888; text-align:center;'>No upcoming appointments</p>";
                            }
                        ?>
                    </div>
                </div>
            </section>
        </div>

        <div id="booking-section" class="hidden-section">
            <h2 style="margin-bottom: 20px;">Book a New Appointment</h2>
            <div class="booking-container">
                <form id="appointmentForm">
                    <div class="form-grid">
                        <div class="form-group"><label>Select Date</label><input type="date" required></div>
                        <div class="form-group"><label>Available Timeslot</label><select required><option value="" disabled selected>Select Time</option><option>09:00 AM - 10:00 AM</option><option>10:00 AM - 11:00 AM</option></select></div>
                        <div class="form-group"><label>Full Name</label><input type="text" value="<?php echo htmlspecialchars($patient['full_name']); ?>" required></div>
                        <div class="form-group"><label>Age</label><input type="number" value="<?php echo htmlspecialchars($patient['age']); ?>" required></div>
                        <div class="form-group"><label>Phone Number</label><input type="tel" value="<?php echo htmlspecialchars($patient['phone_number']); ?>" required></div>
                        <div class="form-group"><label>Email Address</label><input type="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required></div>
                        <div class="form-group full-width"><label>Health Issue</label><textarea rows="4" placeholder="Describe symptoms..."></textarea></div>
                        <div class="full-width"><button type="submit" class="book-btn">Confirm & Pay</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div id="appointments-section" class="hidden-section">
            <h2 style="margin-bottom: 20px;">My Appointments</h2>

            <div class="appt-tabs">
                <button class="tab-btn active" onclick="filterAppts('all')">All</button>
            </div>

            <div class="appointments-list">
                <?php
                    $sql_all = "SELECT a.*, d.full_name as doctor_name, d.specialization 
                                FROM appointments a 
                                LEFT JOIN admin_users d ON a.admin_id = d.admin_id
                                WHERE a.patient_id = $patient_id 
                                ORDER BY a.appointment_date DESC";
                    $res_all = $conn->query($sql_all);

                    if($res_all->num_rows > 0){
                        while($row = $res_all->fetch_assoc()){
                            $status = $row['status'];
                            $btn_html = "";
                            
                            // UPDATED LOGIC: Allows joining if status is Confirmed OR Pending (For testing)
                            if($status == 'Confirmed' || $status == 'Pending'){
                                $btn_html = '<a href="../meeting.php?id='.$row['appointment_id'].'" target="_blank" class="btn-join"><i class="fa-solid fa-video"></i> Join Meeting</a>';
                            } elseif ($status == 'Completed'){
                                $btn_html = '<button class="btn-outline"><i class="fa-solid fa-download"></i> Prescription</button>';
                            } else {
                                $btn_html = '<span style="font-size:12px; color:#aaa;">No actions</span>';
                            }

                            echo '
                            <div class="appt-card">
                                <div class="appt-details">
                                    <img src="https://i.pravatar.cc/150?img=59" alt="Doctor" class="doctor-img">
                                    <div class="appt-info">
                                        <h4>'.htmlspecialchars($row['doctor_name']).'</h4>
                                        <span>'.htmlspecialchars($row['specialization'] ?? 'General').'</span>
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
                        echo "<p>No appointments found.</p>";
                    }
                ?>
            </div>
        </div>

    </main>

    <script>
        // --- NAVIGATION LOGIC ---
        const navOverview = document.getElementById('nav-overview');
        const navBooking = document.getElementById('nav-booking');
        const navAppointments = document.getElementById('nav-appointments'); 

        const overviewSection = document.getElementById('overview-section');
        const bookingSection = document.getElementById('booking-section');
        const appointmentsSection = document.getElementById('appointments-section'); 

        const allMenuItems = document.querySelectorAll('.menu-item');
        const allSections = [overviewSection, bookingSection, appointmentsSection];

        function switchSection(activeItem, sectionToShow) {
            allMenuItems.forEach(item => item.classList.remove('active'));
            if(activeItem) activeItem.classList.add('active');
            allSections.forEach(sec => sec.classList.add('hidden-section'));
            sectionToShow.classList.remove('hidden-section');
        }

        if(navOverview) navOverview.addEventListener('click', (e) => { e.preventDefault(); switchSection(navOverview, overviewSection); });
        if(navBooking) navBooking.addEventListener('click', (e) => { e.preventDefault(); switchSection(navBooking, bookingSection); });
        if(navAppointments) navAppointments.addEventListener('click', (e) => { e.preventDefault(); switchSection(navAppointments, appointmentsSection); });

        // --- DASHBOARD LOGIC ---
        const toggleBtn = document.getElementById('toggle-btn');
        const body = document.body;
        if(toggleBtn) toggleBtn.addEventListener('click', () => { body.classList.toggle('toggled'); });

        // Chart
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