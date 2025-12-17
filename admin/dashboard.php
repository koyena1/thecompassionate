<?php
session_start();

// =======================================================================
// 1. AJAX HANDLER: MARK NOTIFICATION AS READ
//    (Called when admin opens the modal)
// =======================================================================
if (isset($_POST['mark_read_id'])) {
    include '../config/db.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { exit; }

    $app_id = intval($_POST['mark_read_id']);
    
    // Update status to 1 (Read)
    $stmt = $conn->prepare("UPDATE appointments SET admin_read_status = 1 WHERE appointment_id = ?");
    $stmt->bind_param("i", $app_id);
    $stmt->execute();
    exit; // Stop here
}

// =======================================================================
// 2. AJAX HANDLER: FETCH NOTIFICATIONS
//    (Called every few seconds by JS)
// =======================================================================
if (isset($_GET['fetch_notifications'])) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "safe_space_db";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { echo json_encode([]); exit; }

    // A. Get Count of UNREAD notifications for the Red Dot
    $countSql = "SELECT COUNT(*) as unread_count FROM appointments WHERE admin_read_status = 0";
    $countResult = $conn->query($countSql);
    $unreadCount = 0;
    if ($countResult) {
        $row = $countResult->fetch_assoc();
        $unreadCount = $row['unread_count'];
    }

    // B. Get Top 5 Recent Appointments
    $sql = "SELECT 
                a.appointment_id, 
                a.appointment_date, 
                a.appointment_time,
                a.initial_health_issue,
                a.admin_read_status,
                p.full_name, 
                p.age, 
                p.blood_type, 
                p.height, 
                p.weight,
                p.phone_number, 
                p.email, 
                p.address 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.patient_id
            ORDER BY a.created_at DESC LIMIT 5";

    $result = $conn->query($sql);
    $items = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }

    // Return both count and list
    header('Content-Type: application/json');
    echo json_encode(['unread_count' => $unreadCount, 'notifications' => $items]);
    exit; 
}

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Authentication logic here
}

// =======================================================================
// 3. MAIN PAGE DATA FETCHING
// =======================================================================
include '../config/db.php';
$conn_main = new mysqli($servername, $username, $password, $dbname);

$real_patient_count = 0;
$todays_appointments_count = 0;
$total_visitors_count = 0; // New count for visitor tracking

if (!$conn_main->connect_error) {
    // 3.1 Count Total Patients
    $countSql = "SELECT COUNT(DISTINCT patient_id) as total_patients FROM appointments";
    $result = $conn_main->query($countSql);
    if ($result && $row = $result->fetch_assoc()) {
        $real_patient_count = $row['total_patients'];
    }

    // 3.2 Count Today's Appointments
    $today_date = date('Y-m-d');
    $apptSql = "SELECT COUNT(*) as today_count FROM appointments WHERE appointment_date = '$today_date' AND status != 'Cancelled'";
    $apptResult = $conn_main->query($apptSql);
    if ($apptResult && $apptRow = $apptResult->fetch_assoc()) {
        $todays_appointments_count = $apptRow['today_count'];
    }

    // 3.3 Count Real Site Visitors
    $visitorSql = "SELECT COUNT(*) as visitor_count FROM site_visitors";
    $visitorResult = $conn_main->query($visitorSql);
    if ($visitorResult && $visRow = $visitorResult->fetch_assoc()) {
        $total_visitors_count = $visRow['visitor_count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #7f8c8d;
            --accent-color: #3498db;
            --bg-light: #f5f7fb;
            --text-dark: #343a40;
            --text-muted: #6c757d;
            --sidebar-width: 250px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* --- SIDEBAR STYLES --- */
        #sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 9999;
            background: #fff;
            color: var(--text-dark);
            transition: all 0.3s;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding-bottom: 60px;
            overflow-y: auto;
        }

        #sidebar::-webkit-scrollbar { width: 5px; }
        #sidebar::-webkit-scrollbar-track { background: #f1f1f1; }
        #sidebar::-webkit-scrollbar-thumb { background: #888; border-radius: 5px; }
        #sidebar::-webkit-scrollbar-thumb:hover { background: #555; }

        #sidebar { margin-left: 0; }
        #sidebar.active { margin-left: calc(var(--sidebar-width) * -1); }

        #sidebar .sidebar-header { padding: 20px; background: #fff; }
        #sidebar ul.components { padding: 20px 0; border-bottom: 1px solid #eee; }
        #sidebar ul p { color: var(--text-muted); padding: 10px; }

        #sidebar ul li a {
            padding: 10px 20px;
            font-size: 1.1em;
            display: block;
            color: var(--text-dark);
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
            position: relative;
        }

        #sidebar ul li a:hover,
        #sidebar ul li a.active {
            color: var(--accent-color);
            background: #f8f9fa;
            border-left: 4px solid var(--accent-color);
        }

        #sidebar ul li a i { margin-right: 10px; width: 20px; text-align: center; }

        #sidebar ul li a.dropdown-toggle::after {
            display: block;
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
        }

        #sidebar ul ul a { font-size: 0.9em !important; padding-left: 50px !important; background: #f8f9fa; }
        #sidebar ul ul a:hover { background: #eef2f7 !important; border-left: 4px solid transparent !important; }

        .sidebar-banner { margin: 20px; background: #eef2f7; padding: 15px; border-radius: 10px; text-align: center; }
        .sidebar-footer { width: 100%; padding: 15px; background: #fff; font-size: 0.8em; color: var(--text-muted); border-top: 1px solid #eee; }

        /* --- CONTENT STYLES --- */
        #content {
            width: calc(100% - var(--sidebar-width));
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
            margin-left: var(--sidebar-width); 
        }

        #content.active { margin-left: 0; width: 100%; }

        /* --- NAVBAR STYLES --- */
        .navbar {
            padding: 15px 10px;
            background: #fff;
            border: none;
            border-radius: 0;
            margin-bottom: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        /* --- CARD & CHART STYLES --- */
        .card { border: none; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 0, 0, 0.05); transition: all 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1); }
        .card-icon { font-size: 2em; margin-bottom: 15px; }
        .card-title { font-size: 1.5em; font-weight: bold; }
        .card-subtitle { font-size: 0.9em; color: var(--text-muted); }
        .card-status { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; color: #fff; }
        
        .status-blue { background-color: #3498db; }
        .status-orange { background-color: #e67e22; }
        .status-cyan { background-color: #00bcd4; }
        .status-purple { background-color: #9b59b6; }
        .status-red { background-color: #e74c3c; }

        .chart-container { position: relative; height: 200px; width: 100%; }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .chart-title { font-weight: 600; }
        .chart-percentage { font-size: 0.9em; }

        /* --- NOTIFICATION STYLES --- */
        .notification-dropdown { min-width: 340px; padding: 0; border: none; border-radius: 10px; overflow: hidden; }
        .notification-header { background: #f8f9fa; padding: 12px 15px; font-weight: bold; border-bottom: 1px solid #eee; color: var(--primary-color); display:flex; justify-content:space-between; align-items:center;}
        .notification-list { max-height: 300px; overflow-y: auto; }
        .notification-item { padding: 12px 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; }
        .notification-item:hover { background: #eef2f7; }
        .notification-item.unread { background-color: #fdfbf7; border-left: 3px solid #e67e22; }
        .notification-item .icon-box { width: 40px; height: 40px; border-radius: 50%; background: rgba(52, 152, 219, 0.1); color: var(--accent-color); display: flex; align-items: center; justify-content: center; margin-right: 15px; }
        .pulse-animation { animation: pulse-red 2s infinite; }
        @keyframes pulse-red {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
        }

        @media (max-width: 991px) {
            #sidebar { margin-left: calc(var(--sidebar-width) * -1); }
            #sidebar.active { margin-left: 0; }
            #content { margin-left: 0; width: 100%; }
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <nav id="sidebar">
            <div class="sidebar-header d-flex justify-content-between align-items-center">
                <h3><i class="fas fa-heartbeat text-danger me-2"></i>Admin</h3>
                <div id="sidebarClose" class="d-lg-none" style="cursor: pointer; font-size: 1.5rem; color: #333;">
                    <i class="fas fa-times"></i>
                </div>
            </div>

            <ul class="list-unstyled components">
                <p class="ms-3">Emergency help</p>
                <li>
                    <a href="#dashboardSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle active">
                        <i class="fas fa-desktop"></i> Dashboard
                    </a>
                    <ul class="collapse list-unstyled" id="dashboardSubmenu">
                        <li><a href="manage_home.php">ManageHome</a></li>
                        <li><a href="manage_blog.php">ManageBlog</a></li>
                        <li><a href="manage_services.php">ManageServices</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#appointmentsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                    <ul class="collapse list-unstyled" id="appointmentsSubmenu">
                        <li><a href="manage appoinments.php">Manage appointments</a></li>
                        <li><a href="meeting_info_notes.php">Meeting info & notes</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#patientsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-user-injured"></i> Patients
                    </a>
                    <ul class="collapse list-unstyled" id="patientsSubmenu">
                        <li><a href="patient list.php">Patient information overview</a></li>
                        <li><a href="#">Clinical updates & documentation</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#doctorsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                    <ul class="collapse list-unstyled" id="doctorsSubmenu">
                        <li><a href="#">Automated follow-up workflow</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#featuresSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-cubes"></i> Features
                    </a>
                    <ul class="collapse list-unstyled" id="featuresSubmenu">
                        <li><a href="#">Automated follow-up system</a></li>
                        <li><a href="#">Digital prescription generator</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#formsSubmenu" data-bs-toggle="collapse" aria-expanded="false">
                        <i class="fas fa-file-alt"></i> Online Consult
                    </a>
                </li>
                <li>
                    <a href="#appsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-th"></i> Condition Tags
                    </a>
                    <ul class="collapse list-unstyled" id="appsSubmenu">
                        <li><a href="#">Tag List</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#authSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-lock"></i> Health Record
                    </a>
                    <ul class="collapse list-unstyled" id="authSubmenu">
                        <li><a href="manage_healthtimeline.php">Timeline View</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#"><i class="fas fa-ellipsis-h"></i> Payment Gateway</a>
                </li>
            </ul>

            <div class="sidebar-banner">
                <h5>Make an Appointments</h5>
                <p class="small">Best Health Care here →</p>
            </div>

            <div class="sidebar-footer">
                <p class="mb-0">Rhythm Admin Dashboard</p>
                <p class="mb-0">© 2022 All Rights Reserved</p>
            </div>
        </nav>

        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-light">
                        <i class="fas fa-align-left"></i>
                    </button>
                    
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <form class="d-flex ms-auto me-4">
                            <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                            <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
                        </form>
                        <ul class="nav navbar-nav ml-auto align-items-center">
                            <li class="nav-item dropdown">
                                <a class="nav-link position-relative" href="#" id="navbarDropdownBell" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell"></i>
                                    <span id="bellBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end shadow notification-dropdown" aria-labelledby="navbarDropdownBell">
                                    <div class="notification-header">
                                        <span>Recent Bookings</span>
                                        <small class="text-muted" style="font-weight: normal; font-size: 0.8em">Last 5</small>
                                    </div>
                                    <div id="notificationList" class="notification-list">
                                        <div class="text-center p-3 small text-muted">Loading...</div>
                                    </div>
                                    <div class="text-center p-2 border-top bg-light">
                                        <a href="manage_appointments.php" class="text-decoration-none small text-muted">View All</a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-cog"></i></a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="https://ui-avatars.com/api/?name=Admin&background=random" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;">
                                    <span class="fw-semibold">Admin</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdownUser" style="border: none;">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2 text-muted"></i> My Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card p-3 text-center h-100">
                            <div class="card-icon text-primary"><i class="fas fa-wheelchair"></i></div>
                            <h3 class="card-title mb-0"><?php echo $real_patient_count; ?></h3>
                            <p class="card-subtitle mb-2">Patient</p>
                            <a href="patient list.php" class="card-status status-blue text-white text-decoration-none">Patient</a>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card p-3 text-center h-100">
                            <div class="card-icon text-warning"><i class="fas fa-file-invoice"></i></div>
                            <h3 class="card-title mb-0">23,009</h3>
                            <p class="card-subtitle mb-2">Encounters</p>
                            <span class="card-status status-orange">Encounters</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card p-3 text-center h-100">
                            <div class="card-icon text-info"><i class="fas fa-calendar-alt"></i></div>
                            <h3 class="card-title mb-0"><?php echo $todays_appointments_count; ?></h3>
                            <p class="card-subtitle mb-2">Appointments</p>
                            <a href="manage%20appoinments.php" class="card-status status-cyan text-white text-decoration-none">Appointments</a>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card p-3 text-center h-100">
                            <div class="card-icon" style="color: #9b59b6;"><i class="fas fa-users"></i></div>
                            <h3 class="card-title mb-0"><?php echo $total_visitors_count; ?></h3>
                            <p class="card-subtitle mb-2">Visitors</p>
                            <span class="card-status status-purple">Site Visitors</span>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card p-3 text-center h-100">
                            <div class="card-icon text-danger"><i class="fas fa-prescription-bottle-alt"></i></div>
                            <h3 class="card-title mb-0">14,023</h3>
                            <p class="card-subtitle mb-2">Prescription</p>
                            <span class="card-status status-red">Prescription</span>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="card p-3 h-100">
                            <div class="chart-header">
                                <h5 class="chart-title">New Patient</h5>
                                <span class="chart-percentage text-success">14.22% High</span>
                            </div>
                            <div class="chart-container"><canvas id="newPatientChart"></canvas></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card p-3 h-100">
                            <div class="chart-header">
                                <h5 class="chart-title">OPD Patients</h5>
                                <span class="chart-percentage text-danger">11.12% Less</span>
                            </div>
                            <div class="chart-container"><canvas id="opdPatientsChart"></canvas></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card p-3 h-100">
                            <div class="chart-header">
                                <h5 class="chart-title">Treatment</h5>
                                <span class="chart-percentage text-success">19.5% High</span>
                            </div>
                            <div class="chart-container"><canvas id="treatmentChart"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="patientDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-white border-bottom-0">
                    <h5 class="modal-title fw-bold text-primary">Patient Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pt-0">
                    <img src="" id="modalImg" class="rounded-circle mb-3 shadow-sm" width="100" height="100" style="object-fit:cover;">
                    <h4 id="modalName" class="mb-0 fw-bold"></h4>
                    <p class="text-muted small">Appointment: <span id="modalDate"></span></p>
                    <div class="card bg-light border-0 p-3 mt-3">
                        <div class="row text-start g-3">
                             <div class="col-12 border-bottom pb-2 mb-2">
                                <label class="small text-muted d-block text-uppercase fw-bold">Patient Problem / Reason</label>
                                <span id="modalReason" class="fw-bold text-dark"></span>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted d-block">Age</label>
                                <span id="modalAge" class="fw-semibold"></span>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted d-block">Blood Type</label>
                                <span id="modalBlood" class="fw-bold text-danger"></span>
                            </div>
                            <div class="col-12">
                                <label class="small text-muted d-block">Email</label>
                                <span id="modalEmail" class="fw-semibold"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 justify-content-center">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        }
        document.getElementById('sidebarCollapse').addEventListener('click', toggleSidebar);
        document.getElementById('sidebarClose').addEventListener('click', toggleSidebar);

        // Notifications
        function loadNotifications() {
            fetch('dashboard.php?fetch_notifications=1')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('bellBadge');
                    if (data.unread_count > 0) {
                        badge.innerText = data.unread_count;
                        badge.style.display = 'block';
                        badge.classList.add('pulse-animation');
                    } else {
                        badge.style.display = 'none';
                    }

                    const list = document.getElementById('notificationList');
                    list.innerHTML = ''; 
                    const notifications = data.notifications || [];
                    if (notifications.length > 0) {
                        notifications.forEach(patient => {
                            const item = document.createElement('div');
                            const isUnread = patient.admin_read_status == 0 ? 'unread' : '';
                            item.className = `notification-item ${isUnread}`;
                            item.innerHTML = `
                                <div class="icon-box"><i class="fas fa-calendar-check"></i></div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold">${patient.full_name || 'Guest'}</h6>
                                    <small class="text-muted">Booked: ${patient.appointment_date}</small>
                                </div>
                                ${patient.admin_read_status == 0 ? '<small class="text-danger"><i class="fas fa-circle" style="font-size:8px;"></i></small>' : ''}
                            `;
                            item.onclick = function() {
                                openPatientModal(patient);
                                markAsRead(patient.appointment_id);
                            };
                            list.appendChild(item);
                        });
                    } else {
                        list.innerHTML = '<div class="text-center p-3 small text-muted">No new appointments</div>';
                    }
                });
        }

        function markAsRead(appointmentId) {
            const formData = new FormData();
            formData.append('mark_read_id', appointmentId);
            fetch('dashboard.php', { method: 'POST', body: formData })
            .then(() => loadNotifications());
        }

        function openPatientModal(data) {
            document.getElementById('modalName').innerText = data.full_name || 'N/A';
            document.getElementById('modalDate').innerText = (data.appointment_date || '') + ' ' + (data.appointment_time || '');
            document.getElementById('modalReason').innerText = data.initial_health_issue || 'No reason';
            document.getElementById('modalAge').innerText = (data.age || '-') + ' Yrs';
            document.getElementById('modalBlood').innerText = data.blood_type || '-';
            document.getElementById('modalEmail').innerText = data.email || '-';
            const nameEncoded = encodeURIComponent(data.full_name || 'User');
            document.getElementById('modalImg').src = `https://ui-avatars.com/api/?name=${nameEncoded}&background=random`;
            new bootstrap.Modal(document.getElementById('patientDetailsModal')).show();
        }

        document.addEventListener('DOMContentLoaded', loadNotifications);
        setInterval(loadNotifications, 5000); 

        // Charts
        const opt = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };
        new Chart(document.getElementById('newPatientChart'), { type: 'bar', data: { labels: ['Jan', 'Feb', 'Mar'], datasets: [{ data: [10, 20, 30], backgroundColor: '#3498db' }] }, options: opt });
        new Chart(document.getElementById('opdPatientsChart'), { type: 'line', data: { labels: ['Jan', 'Feb', 'Mar'], datasets: [{ data: [20, 40, 30], borderColor: '#e67e22', fill: true }] }, options: opt });
        new Chart(document.getElementById('treatmentChart'), { type: 'line', data: { labels: ['Jan', 'Feb', 'Mar'], datasets: [{ data: [15, 25, 20], borderColor: '#2ecc71' }] }, options: opt });
    </script>
</body>
</html>