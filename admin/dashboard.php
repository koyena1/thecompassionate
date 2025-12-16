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

    // B. Get Top 5 Recent Appointments (Regardless of read status, so list isn't empty)
    // Added 'initial_health_issue' to fetch the reason
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
// =======================================================================


// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // header("Location: ../login.php"); 
    // exit(); 
}
// ----------------------
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

        /* Desktop Default */
        #sidebar { margin-left: 0; }
        /* Desktop Toggled (Hidden) */
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

        /* Desktop: Expand Content when sidebar is hidden */
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
        .navbar-btn { box-shadow: none; outline: none !important; border: none; }

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
        .text-success { color: #2ecc71 !important; }
        .text-danger { color: #e74c3c !important; }

        .vitals-icon { font-size: 1.5em; color: var(--accent-color); margin-right: 10px; }
        .chart-legend { display: flex; justify-content: center; margin-top: 15px; flex-wrap: wrap; }
        .legend-item { display: flex; align-items: center; margin: 0 10px; font-size: 0.9em; }
        .legend-color { width: 12px; height: 12px; border-radius: 50%; margin-right: 5px; }

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

        /* --- MOBILE RESPONSIVENESS --- */
        @media (max-width: 991px) {
            #sidebar { margin-left: calc(var(--sidebar-width) * -1); }
            #sidebar.active { margin-left: 0; }
            #content { margin-left: 0; width: 100%; }
            #content.active { margin-left: 0; }
            .navbar form { display: none !important; }
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
                        <li><a href="#">Patient information overview</a></li>
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
                    <a href="#formsSubmenu" data-bs-toggle="collapse" aria-expanded="false" >
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
                        <li><a href="#">Timeline View</a></li>
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
                                    <span id="bellBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">
                                        0
                                    </span>
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
                                    <img src="https://ui-avatars.com/api/?name=<?php echo isset($_SESSION['full_name']) ? urlencode($_SESSION['full_name']) : 'Admin'; ?>&background=random" 
                                           class="rounded-circle me-2" 
                                           alt="User" 
                                           width="32" height="32"
                                           style="object-fit: cover;">
                                    <span class="fw-semibold">
                                        <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Admin'; ?>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdownUser" style="border: none;">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2 text-muted"></i> My Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="logout.php">
                                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                                        </a>
                                    </li>
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
                            <h3 class="card-title mb-0">4,569</h3>
                            <p class="card-subtitle mb-2">Patient</p>
                            <span class="card-status status-blue">Patient</span>
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
                            <h3 class="card-title mb-0">56</h3>
                            <p class="card-subtitle mb-2">Appointments</p>
                            <span class="card-status status-cyan">Appointments</span>
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
                    
                   <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="card p-3 h-100">
                            <div class="chart-header">
                                <h5 class="chart-title">New Patient</h5>
                                <span class="chart-percentage text-success">14.22% High</span>
                            </div>
                            <div class="chart-container">
                                <canvas id="newPatientChart"></canvas>
                            </div>
                            <div class="d-flex justify-content-around mt-3 text-center">
                                <div><h6 class="mb-0">Overall</h6><small>78%</small></div>
                                <div><h6 class="mb-0">Monthly</h6><small>17%</small></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card p-3 h-100">
                            <div class="chart-header">
                                <h5 class="chart-title">OPD Patients</h5>
                                <span class="chart-percentage text-danger">11.12% Less</span>
                            </div>
                            <div class="chart-container">
                                <canvas id="opdPatientsChart"></canvas>
                            </div>
                            <div class="d-flex justify-content-around mt-3 text-center">
                                <div><h6 class="mb-0">Overall</h6><small>78%</small></div>
                                <div><h6 class="mb-0">Monthly</h6><small>17%</small></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card p-3 h-100">
                            <div class="chart-header">
                                <h5 class="chart-title">Treatment</h5>
                                <span class="chart-percentage text-success">19.5% High</span>
                            </div>
                            <div class="chart-container">
                                <canvas id="treatmentChart"></canvas>
                            </div>
                            <div class="d-flex justify-content-around mt-3 text-center">
                                <div><h6 class="mb-0">Overall</h6><small>78%</small></div>
                                <div><h6 class="mb-0">Monthly</h6><small>17%</small></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="card p-3 h-100">
                            <h5 class="chart-title mb-3">Patients In</h5>
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="patientsInChart"></canvas>
                            </div>
                            <div class="chart-legend">
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #00c0ef;"></div>ICU
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #3c8dbc;"></div>OPD
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #d2d6de;"></div>Emergency
                                </div>
                            </div>
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
                                <span id="modalReason" class="fw-bold text-dark" style="font-size: 1.1em;"></span>
                            </div>

                            <div class="col-6">
                                <label class="small text-muted d-block">Age</label>
                                <span id="modalAge" class="fw-semibold"></span>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted d-block">Blood Type</label>
                                <span id="modalBlood" class="fw-bold text-danger"></span>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted d-block">Height</label>
                                <span id="modalHeight" class="fw-semibold"></span>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted d-block">Weight</label>
                                <span id="modalWeight" class="fw-semibold"></span>
                            </div>
                            <div class="col-12 pt-1">
                                <label class="small text-muted d-block">Email</label>
                                <span id="modalEmail" class="fw-semibold"></span>
                            </div>
                            <div class="col-12">
                                <label class="small text-muted d-block">Phone</label>
                                <span id="modalPhone" class="fw-semibold"></span>
                            </div>
                             <div class="col-12">
                                <label class="small text-muted d-block">Address</label>
                                <span id="modalAddress" class="fw-semibold text-break"></span>
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
        // Main Sidebar Toggle Logic
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        }
        document.getElementById('sidebarCollapse').addEventListener('click', toggleSidebar);
        document.getElementById('sidebarClose').addEventListener('click', toggleSidebar);

        // --- NOTIFICATION LOGIC START ---
        function loadNotifications() {
            fetch('dashboard.php?fetch_notifications=1')
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('notificationList');
                    const badge = document.getElementById('bellBadge');
                    
                    // 1. UPDATE BADGE (Red Dot)
                    // Only show if there are UNREAD notifications
                    if (data.unread_count > 0) {
                        badge.innerText = data.unread_count;
                        badge.style.display = 'block';
                        badge.classList.add('pulse-animation');
                    } else {
                        badge.style.display = 'none';
                        badge.classList.remove('pulse-animation');
                    }

                    // 2. UPDATE LIST
                    list.innerHTML = ''; // Clear old list
                    const notifications = data.notifications || [];

                    if (notifications.length > 0) {
                        notifications.forEach(patient => {
                            const item = document.createElement('div');
                            // Add 'unread' class if status is 0 to style it differently
                            const isUnread = patient.admin_read_status == 0 ? 'unread' : '';
                            item.className = `notification-item ${isUnread}`;
                            
                            const pName = patient.full_name || 'Guest Patient';
                            
                            item.innerHTML = `
                                <div class="icon-box">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold" style="font-size:0.95rem;">${pName}</h6>
                                    <small class="text-muted" style="font-size:0.8rem;">
                                        Booked: ${patient.appointment_date}
                                    </small>
                                </div>
                                ${patient.admin_read_status == 0 ? '<small class="text-danger"><i class="fas fa-circle" style="font-size:8px;"></i></small>' : ''}
                            `;
                            
                            // On Click: Open Details + Mark as Read
                            item.onclick = function() {
                                openPatientModal(patient);
                                markAsRead(patient.appointment_id);
                            };
                            
                            list.appendChild(item);
                        });
                    } else {
                        list.innerHTML = '<div class="text-center p-3 small text-muted">No new appointments</div>';
                    }
                })
                .catch(err => console.error('Error fetching notifications:', err));
        }

        // New function to update DB status
        function markAsRead(appointmentId) {
            const formData = new FormData();
            formData.append('mark_read_id', appointmentId);

            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(() => {
                // Reload notifications to update the badge immediately
                loadNotifications();
            })
            .catch(err => console.error('Error marking read:', err));
        }

        function openPatientModal(data) {
            // Populate Modal Fields
            document.getElementById('modalName').innerText = data.full_name || 'N/A';
            document.getElementById('modalDate').innerText = (data.appointment_date || '') + ' ' + (data.appointment_time || '');
            
            // NEW: Populate Reason
            document.getElementById('modalReason').innerText = data.initial_health_issue || 'No reason provided';

            document.getElementById('modalAge').innerText = (data.age || '-') + ' Yrs';
            document.getElementById('modalBlood').innerText = data.blood_type || '-';
            document.getElementById('modalHeight').innerText = (data.height || '0') + ' cm';
            document.getElementById('modalWeight').innerText = (data.weight || '0') + ' kg';
            document.getElementById('modalPhone').innerText = data.phone_number || '-';
            document.getElementById('modalEmail').innerText = data.email || '-';
            document.getElementById('modalAddress').innerText = data.address || 'No address provided';

            const nameEncoded = encodeURIComponent(data.full_name || 'User');
            document.getElementById('modalImg').src = `https://ui-avatars.com/api/?name=${nameEncoded}&background=random&size=200`;

            var myModal = new bootstrap.Modal(document.getElementById('patientDetailsModal'));
            myModal.show();
        }

        document.addEventListener('DOMContentLoaded', loadNotifications);
        setInterval(loadNotifications, 5000); 
        // --- NOTIFICATION LOGIC END ---


        // Chart.js Configuration (UNCHANGED)
        const newPatientCtx = document.getElementById('newPatientChart').getContext('2d');
        new Chart(newPatientCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'New Patients',
                    data: [10, 20, 30, 40, 25, 35, 45, 30, 20, 10, 5, 15],
                    backgroundColor: '#3498db',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });

        const opdPatientsCtx = document.getElementById('opdPatientsChart').getContext('2d');
        new Chart(opdPatientsCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    label: 'OPD Patients',
                    data: [20, 40, 30, 50, 35, 45, 30, 20, 30, 20],
                    borderColor: '#e67e22',
                    backgroundColor: 'rgba(230, 126, 34, 0.3)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });

        const treatmentCtx = document.getElementById('treatmentChart').getContext('2d');
        new Chart(treatmentCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    label: 'Treatment',
                    data: [15, 25, 20, 35, 30, 40, 25, 15, 20, 15],
                    borderColor: '#2ecc71',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });

        const patientsInCtx = document.getElementById('patientsInChart').getContext('2d');
        new Chart(patientsInCtx, {
            type: 'doughnut',
            data: {
                labels: ['ICU', 'OPD', 'Emergency'],
                datasets: [{
                    data: [300, 150, 100],
                    backgroundColor: ['#00c0ef', '#3c8dbc', '#d2d6de'],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                }
            }
        });
    </script>
</body>
</html>