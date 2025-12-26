<?php
session_start();

// =======================================================================
// SECURITY CHECK - Must be logged in as admin
// =======================================================================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// =======================================================================
// 1. AJAX HANDLER: MARK NOTIFICATION AS READ
// =======================================================================
if (isset($_POST['mark_read_id'])) {
    include '../config/db.php'; 

    $app_id = intval($_POST['mark_read_id']);
    
    // Update status to 1 (Read)
    $stmt = $conn->prepare("UPDATE appointments SET admin_read_status = 1 WHERE appointment_id = ?");
    $stmt->bind_param("i", $app_id);
    $stmt->execute();
    exit; 
}

// =======================================================================
// 2. AJAX HANDLER: FETCH NOTIFICATIONS
// =======================================================================
if (isset($_GET['fetch_notifications'])) {
    include '../config/db.php'; 

    // A. Get Count of UNREAD notifications
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
                p.address,
                p.allergies
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

    header('Content-Type: application/json');
    echo json_encode(['unread_count' => $unreadCount, 'notifications' => $items]);
    exit; 
}

// =======================================================================
// AJAX HANDLER: FETCH ALL PRESCRIPTIONS
// =======================================================================
if (isset($_GET['fetch_all_prescriptions'])) {
    include '../config/db.php'; 
    $sql = "SELECT pr.*, p.full_name, a.appointment_date 
            FROM prescriptions pr
            JOIN patients p ON pr.patient_id = p.patient_id
            JOIN appointments a ON pr.appointment_id = a.appointment_id
            ORDER BY pr.created_at DESC";
    $result = $conn->query($sql);
    $prescriptions = [];
    while($row = $result->fetch_assoc()) {
        $prescriptions[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($prescriptions);
    exit;
}

// =======================================================================
// 3. MAIN PAGE DATA FETCHING
// =======================================================================
include '../config/db.php';
$conn_main = new mysqli($servername, $username, $password, $dbname);

$real_patient_count = 0;
$todays_appointments_count = 0;
$total_visitors_count = 0; 
$prescription_count = 0;
$admin_display_name = "Admin";

// Real Chart Data Initialization
$distribution_data = ['Pending' => 0, 'Confirmed' => 0, 'Cancelled' => 0];
$weekly_labels = [];
$weekly_counts = [];
$age_data = ['Under 18' => 0, '18-35' => 0, '36-50' => 0, '50+' => 0];

if (!$conn_main->connect_error) {
    // 3.1 Stats Counts
    $res = $conn_main->query("SELECT COUNT(DISTINCT patient_id) as total_patients FROM appointments");
    if ($res) $real_patient_count = $res->fetch_assoc()['total_patients'];

    $today_date = date('Y-m-d');
    $res = $conn_main->query("SELECT COUNT(*) as today_count FROM appointments WHERE appointment_date = '$today_date' AND status != 'Cancelled'");
    if ($res) $todays_appointments_count = $res->fetch_assoc()['today_count'];

    $res = $conn_main->query("SELECT COUNT(*) as visitor_count FROM site_visitors");
    if ($res) $total_visitors_count = $res->fetch_assoc()['visitor_count'];

    $res = $conn_main->query("SELECT full_name FROM admin_users LIMIT 1");
    if ($res) $admin_display_name = $res->fetch_assoc()['full_name'];

    $res = $conn_main->query("SELECT COUNT(*) as total_prescriptions FROM prescriptions");
    if ($res) $prescription_count = $res->fetch_assoc()['total_prescriptions'];

    // 3.2 Appointment Distribution Data
    $res = $conn_main->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
    while($row = $res->fetch_assoc()){
        if(isset($distribution_data[$row['status']])) $distribution_data[$row['status']] = (int)$row['count'];
    }

    // 3.3 Weekly Activity (Last 7 Days)
    $res = $conn_main->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM appointments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date ASC");
    while($row = $res->fetch_assoc()){
        $weekly_labels[] = date('D', strtotime($row['date']));
        $weekly_counts[] = (int)$row['count'];
    }

    // 3.4 Patient Age Spread Data
    $res = $conn_main->query("SELECT 
        SUM(CASE WHEN age < 18 THEN 1 ELSE 0 END) as age1,
        SUM(CASE WHEN age BETWEEN 18 AND 35 THEN 1 ELSE 0 END) as age2,
        SUM(CASE WHEN age BETWEEN 36 AND 50 THEN 1 ELSE 0 END) as age3,
        SUM(CASE WHEN age > 50 THEN 1 ELSE 0 END) as age4
        FROM patients");
    $row = $res->fetch_assoc();
    $age_data = [(int)$row['age1'], (int)$row['age2'], (int)$row['age3'], (int)$row['age4']];
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
            transition: background-color 0.3s, color 0.3s;
        }

        /* --- DARK MODE STYLES --- */
        body.dark-mode {
            background-color: #121212;
            color: #e0e0e0;
        }
        body.dark-mode #sidebar, 
        body.dark-mode .navbar, 
        body.dark-mode .card, 
        body.dark-mode .modal-content,
        body.dark-mode .notification-dropdown {
            background-color: #1e1e1e;
            color: #e0e0e0;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            border: none;
        }
        
        body.dark-mode #sidebar ul li a, 
        body.dark-mode .text-dark,
        body.dark-mode .notification-header,
        body.dark-mode .card-title {
            color: #ffffff !important;
        }
        
        #sidebar ul ul a {
            color: #555 !important; 
        }
        body.dark-mode #sidebar ul ul a {
            background: #2a2a2a !important;
            color: #ffffff !important;
        }
        
        body.dark-mode #sidebar ul li a:hover, 
        body.dark-mode #sidebar ul li a.active {
            background: #2a2a2a;
            color: var(--accent-color) !important;
        }
        body.dark-mode .bg-light, 
        body.dark-mode .card.bg-light, 
        body.dark-mode .notification-header {
            background-color: #252525 !important;
        }
        body.dark-mode .table {
            color: #e0e0e0;
            border-color: #444;
        }
        body.dark-mode .border-top, body.dark-mode .border-bottom {
            border-color: #333 !important;
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

        #sidebar .sidebar-header { padding: 20px; background: transparent; }
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
            color: var(--accent-color) !important;
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
        #sidebar ul ul a:hover, #sidebar ul ul a.active { background: #eef2f7 !important; color: var(--accent-color) !important; border-left: 4px solid transparent !important; }

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
        .notification-list { max-height: 350px; overflow-y: auto; padding: 0; }
        
        .notification-item { 
            padding: 15px; 
            border-bottom: 1px solid #eee; 
            cursor: pointer; 
            transition: background 0.2s; 
            display: flex !important; 
            align-items: center; 
            width: 100%; 
            text-align: left;
            color: var(--text-dark);
        }
        .notification-item:hover { background: #eef2f7; }
        
        /* UNREAD NOTIFICATION TEXT FIX FOR KOYENA NAME */
        .notification-item.unread { 
            background-color: #fdfbf7 !important; 
            border-left: 3px solid #e67e22; 
        }
        .notification-item.unread h6 {
            color: #333 !important; 
        }
        .notification-item.unread .text-muted {
            color: #666 !important; 
        }

        body.dark-mode .notification-item:not(.unread) h6 {
            color: #ffffff !important;
        }
        body.dark-mode .notification-item:not(.unread) .text-muted {
            color: #bbbbbb !important; 
        }

        .notification-item .icon-box { min-width: 40px; width: 40px; height: 40px; border-radius: 50%; background: rgba(52, 152, 219, 0.1); color: var(--accent-color); display: flex; align-items: center; justify-content: center; margin-right: 15px; }
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

        .modal-label-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: bold;
            display: block;
            color: #888;
            margin-bottom: 2px;
        }
        body.dark-mode .modal-label-title {
            color: #aaa;
        }
        .modal-label-value {
            font-weight: 600;
            color: inherit;
        }
        body.dark-mode .modal-body .text-muted {
            color: #ccc !important; 
        }

        /* --- MOBILE NAVBAR ICONS FIX --- */
        .mobile-icons {
            display: flex;
            align-items: center;
        }
        @media (max-width: 991px) {
            .navbar-nav {
                flex-direction: row !important;
                gap: 15px;
            }
            .navbar-nav .nav-item {
                margin: 0;
            }
            .admin-name-mobile {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <nav id="sidebar">
            <div class="sidebar-header d-flex justify-content-between align-items-center">
                <h3><i class="fas fa-heartbeat text-danger me-2"></i><?php echo $admin_display_name; ?></h3>
                <div id="sidebarClose" class="d-lg-none" style="cursor: pointer; font-size: 1.5rem; color: #333;">
                    <i class="fas fa-times"></i>
                </div>
            </div>

            <ul class="list-unstyled components">
                <p class="ms-3">Emergency help</p>
                <li>
                    <a href="#dashboardSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
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
                        <li><a href="clinical_updates_documentation.php">Clinical updates & documentation</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#docSub" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                    <ul class="collapse list-unstyled" id="docSub">
                        <li><a href="doctor_profile.php">Doctor Profiles</a></li>
                        <li><a href="followup_communication.php">Follow-up Communication</a></li>
                    </ul>
                </li>
                <li><a href="#"><i class="fas fa-cubes"></i> Features</a></li>
                <!-- <li>
                    <a href="#authSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-lock"></i> Health Record
                    </a>
                    <ul class="collapse list-unstyled" id="authSubmenu">
                        <li><a href="manage_healthtimeline.php">Timeline View</a></li>
                    </ul>
                </li> -->
                <li><a href="payment_details.php"><i class="fas fa-money-bill-wave"></i> Payment Details</a></li>
            </ul>
        </nav>

        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-light"><i class="fas fa-align-left"></i></button>
                    
                    <div class="mobile-icons ms-auto">
                        <ul class="nav navbar-nav align-items-center">
                            <li class="nav-item">
                                <a class="nav-link text-dark" href="javascript:void(0)" onclick="toggleTheme()" title="Toggle Theme">
                                    <i class="fas fa-moon" id="themeIcon"></i>
                                </a>
                            </li>
                            <li class="nav-item dropdown ms-2">
                                <a class="nav-link position-relative" href="#" id="navbarDropdownBell" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell"></i>
                                    <span id="bellBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end shadow notification-dropdown" aria-labelledby="navbarDropdownBell">
                                    <div class="notification-header"><span>Recent Bookings</span><small class="text-muted">Last 5</small></div>
                                    <div id="notificationList" class="notification-list"></div>
                                    <div class="text-center p-2 border-top bg-light">
                                        <a href="manage appoinments.php" class="text-decoration-none small text-muted">View All</a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown ms-3">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_display_name); ?>&background=random" class="rounded-circle" width="32" height="32">
                                    <span class="fw-semibold text-dark ms-2 admin-name-mobile"><?php echo $admin_display_name; ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
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
                            <a href="patient list.php" class="card-status status-blue text-white text-decoration-none">View</a>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card p-3 text-center h-100">
                            <div class="card-icon text-info"><i class="fas fa-calendar-alt"></i></div>
                            <h3 class="card-title mb-0"><?php echo $todays_appointments_count; ?></h3>
                            <p class="card-subtitle mb-2">Appointments</p>
                            <a href="manage appoinments.php" class="card-status status-cyan text-white text-decoration-none">View</a>
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
                        <div class="card p-3 text-center h-100" onclick="showPrescriptionList()" style="cursor: pointer;">
                             <div class="card-icon text-danger"><i class="fas fa-prescription-bottle-alt"></i></div>
                             <h3 class="card-title mb-0"><?php echo $prescription_count; ?></h3>
                             <p class="card-subtitle mb-2">Prescription</p>
                             <span class="card-status status-red">Click to View</span>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="card p-3 h-100">
                            <div class="chart-header">
                                <h5 class="chart-title">Appointment Distribution</h5>
                            </div>
                            <div class="chart-container"><canvas id="distributionChart"></canvas></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card p-3 h-100">
                            <div class="chart-header">
                                <h5 class="chart-title">Weekly Activity</h5>
                            </div>
                            <div class="chart-container"><canvas id="weeklyActivityChart"></canvas></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card p-3 h-100">
                            <div class="chart-header">
                                <h5 class="chart-title">Patient Age Spread</h5>
                            </div>
                            <div class="chart-container"><canvas id="ageSpreadChart"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="patientDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
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
                                <label class="modal-label-title">Problem / Reason</label>
                                <span id="modalReason" class="modal-label-value text-dark"></span>
                            </div>
                            <div class="col-6">
                                <label class="modal-label-title">Age</label>
                                <span id="modalAge" class="modal-label-value"></span>
                            </div>
                            <div class="col-6">
                                <label class="modal-label-title">Blood Type</label>
                                <span id="modalBlood" class="modal-label-value text-danger"></span>
                            </div>
                            <div class="col-6">
                                <label class="modal-label-title">Phone Number</label>
                                <span id="modalPhone" class="modal-label-value"></span>
                            </div>
                            <div class="col-6">
                                <label class="modal-label-title">Height / Weight</label>
                                <span id="modalStats" class="modal-label-value"></span>
                            </div>
                            <div class="col-12">
                                <label class="modal-label-title">Email Address</label>
                                <span id="modalEmail" class="modal-label-value"></span>
                            </div>
                            <div class="col-12">
                                <label class="modal-label-title">Address</label>
                                <span id="modalAddress" class="modal-label-value small text-muted"></span>
                            </div>
                            <div class="col-12 border-top pt-2">
                                <label class="modal-label-title text-danger">ALLERGIES</label>
                                <span id="modalAllergies" class="modal-label-value text-danger"></span>
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

    <div class="modal fade" id="prescriptionListModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Generated Prescriptions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="prescriptionTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // FIXED: Robust Sidebar Highlighting Logic
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname.split("/").pop() || "dashboard.php";
            const sidebarLinks = document.querySelectorAll('#sidebar a');
            
            sidebarLinks.forEach(link => {
                const href = link.getAttribute('href');
                
                // Clear active states first
                link.classList.remove('active');
                const parentCollapse = link.closest('.collapse');
                if (parentCollapse) {
                    const toggle = document.querySelector(`[href="#${parentCollapse.id}"]`);
                    if(toggle) toggle.classList.remove('active');
                }

                // Apply active only to current page link and its parent
                if (href && href === currentPath) {
                    link.classList.add('active');
                    if (parentCollapse) {
                        parentCollapse.classList.add('show');
                        const toggle = document.querySelector(`[href="#${parentCollapse.id}"]`);
                        if(toggle) toggle.classList.add('active');
                    }
                }
            });

            // Prevent dashboard menu from staying highlighted when navigating to doctors, etc.
            if (currentPath !== "dashboard.php") {
                const dashToggle = document.querySelector('[href="#dashboardSubmenu"]');
                const dashCollapse = document.getElementById('dashboardSubmenu');
                if (dashToggle && dashCollapse && !dashCollapse.querySelector('.active')) {
                     dashToggle.classList.remove('active');
                     const dashBsCollapse = bootstrap.Collapse.getInstance(dashCollapse);
                     if(dashBsCollapse) dashBsCollapse.hide();
                }
            }
        });

        function toggleTheme() {
            const body = document.body;
            const icon = document.getElementById('themeIcon');
            body.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                icon.classList.replace('fa-moon', 'fa-sun');
                localStorage.setItem('theme', 'dark');
            } else {
                icon.classList.replace('fa-sun', 'fa-moon');
                localStorage.setItem('theme', 'light');
            }
        }

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
            document.getElementById('themeIcon').classList.replace('fa-moon', 'fa-sun');
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        }
        document.getElementById('sidebarCollapse').addEventListener('click', toggleSidebar);
        document.getElementById('sidebarClose').addEventListener('click', toggleSidebar);

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
                                ${patient.admin_read_status == 0 ? '<small class="text-danger ms-2"><i class="fas fa-circle" style="font-size:8px;"></i></small>' : ''}
                            `;
                            item.onclick = function(e) {
                                e.preventDefault();
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
            document.getElementById('modalReason').innerText = data.initial_health_issue || 'No reason provided';
            document.getElementById('modalAge').innerText = (data.age || '-') + ' Yrs';
            document.getElementById('modalBlood').innerText = data.blood_type || '-';
            document.getElementById('modalEmail').innerText = data.email || '-';
            document.getElementById('modalPhone').innerText = data.phone_number || '-';
            document.getElementById('modalStats').innerText = (data.height || '-') + 'cm / ' + (data.weight || '-') + 'kg';
            document.getElementById('modalAddress').innerText = data.address || 'No address provided';
            document.getElementById('modalAllergies').innerText = data.allergies || 'None';

            const nameEncoded = encodeURIComponent(data.full_name || 'User');
            document.getElementById('modalImg').src = `https://ui-avatars.com/api/?name=${nameEncoded}&background=random`;
            new bootstrap.Modal(document.getElementById('patientDetailsModal')).show();
        }

        function showPrescriptionList() {
            fetch('dashboard.php?fetch_all_prescriptions=1')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('prescriptionTableBody');
                    tbody.innerHTML = '';
                    if(data.length > 0) {
                        data.forEach(item => {
                            const row = `<tr>
                                <td><strong>${item.full_name}</strong></td>
                                <td>${item.appointment_date}</td>
                                <td><span class="badge bg-primary">${item.prescription_type}</span></td>
                                <td><a href="../patient/${item.file_url}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf"></i> View</a></td>
                            </tr>`;
                            tbody.insertAdjacentHTML('beforeend', row);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No prescriptions found</td></tr>';
                    }
                    new bootstrap.Modal(document.getElementById('prescriptionListModal')).show();
                });
        }

        document.addEventListener('DOMContentLoaded', loadNotifications);
        setInterval(loadNotifications, 10000); 

        const opt = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };

        new Chart(document.getElementById('distributionChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Confirmed', 'Cancelled'],
                datasets: [{
                    data: [<?php echo $distribution_data['Pending']; ?>, <?php echo $distribution_data['Confirmed']; ?>, <?php echo $distribution_data['Cancelled']; ?>],
                    backgroundColor: ['#f1c40f', '#2ecc71', '#e74c3c']
                }]
            },
            options: opt
        });

        new Chart(document.getElementById('weeklyActivityChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($weekly_labels); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode($weekly_counts); ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: opt
        });

        new Chart(document.getElementById('ageSpreadChart'), {
            type: 'bar',
            data: {
                labels: ['<18', '18-35', '36-50', '50+'],
                datasets: [{
                    label: 'Number of Patients',
                    data: <?php echo json_encode($age_data); ?>,
                    backgroundColor: '#9b59b6'
                }]
            },
            options: opt
        });
    </script>
</body>
</html>