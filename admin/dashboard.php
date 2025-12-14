<?php
session_start();

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); 
    exit(); 
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
                        <ul class="nav navbar-nav ml-auto">
                            <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-expand"></i></a></li>
                            <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-bell"></i></a></li>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Main Sidebar Toggle Logic (Desktop & Mobile)
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        }

        // Event Listener for Desktop/Navbar Toggle Button
        document.getElementById('sidebarCollapse').addEventListener('click', toggleSidebar);

        // Event Listener for Mobile "X" Close Button
        document.getElementById('sidebarClose').addEventListener('click', toggleSidebar);

        // Chart.js Configuration
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