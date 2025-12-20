<?php 
// FILE: psychiatrist/admin/manage appoinments.php
include '../config/db.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #0ea5e9; --sidebar-bg: #1e293b; --bg-body: #f1f5f9; --card-bg: #ffffff; --text-main: #334155; --danger: #ef4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; height: 100vh; background: var(--bg-body); overflow: hidden; }
        
        /* Sidebar Styling */
        aside { width: 260px; background: var(--sidebar-bg); color: white; display: flex; flex-direction: column; transition: 0.3s; z-index: 1000; }
        .brand { padding: 2rem; font-size: 1.5rem; font-weight: bold; color: var(--primary); border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        nav { flex: 1; padding-top: 1rem; }
        
        /* Sidebar Buttons & Links */
        .nav-link, nav button { 
            background: none; 
            border: none; 
            width: 100%; 
            padding: 1rem 2rem; 
            text-align: left; 
            color: #94a3b8; 
            cursor: pointer; 
            display: flex; 
            gap: 10px; 
            font-size: 1rem; 
            text-decoration: none;
            transition: 0.2s;
        }
        .nav-link:hover, nav button:hover { color: white; background: rgba(255,255,255,0.05); }
        nav button.active { background: #334155; color: white; border-left: 4px solid var(--primary); }
        
        /* Main Content Area */
        main { flex: 1; padding: 2rem; overflow-y: auto; color: var(--text-main); position: relative; }
        header { display: flex; align-items: center; gap: 15px; margin-bottom: 1.5rem; }
        
        /* Mobile Toggle */
        .mobile-toggle { display: none; font-size: 1.5rem; cursor: pointer; color: var(--sidebar-bg); }

        .stat-card { background: var(--card-bg); padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); display: flex; gap: 1rem; align-items: center; margin-bottom: 2rem; width: fit-content; min-width: 200px;}
        
        /* Responsive Table */
        .table-container { width: 100%; overflow-x: auto; background: var(--card-bg); border-radius: 10px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; color: #64748b; }
        
        .btn { padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; display: inline-block; white-space: nowrap; }
        .btn-reschedule { background: #e0f2fe; color: var(--primary); }
        .btn-video { background: #7B61FF; color: white; margin-left: 5px; }
        
        .status { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .status-Confirmed { background: #dcfce7; color: #166534; }
        .status-Pending { background: #ffedd5; color: #9a3412; }
        
        .view-section { display: none; }
        .view-section.active-view { display: block; }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            aside { position: absolute; left: -260px; height: 100%; }
            aside.open { left: 0; }
            .mobile-toggle { display: block; }
            main { padding: 1rem; }
            .stat-card { width: 100%; }
            .btn-video { margin-left: 0; margin-top: 5px; }
            .close-sidebar { display: block; color: white; font-size: 1.2rem; cursor: pointer; }
        }

        @media (min-width: 769px) {
            .close-sidebar { display: none; }
        }
    </style>
</head>
<body>
    <aside id="sidebar">
        <div class="brand">
            <span><i class="fa-solid fa-heart-pulse"></i> AdminPanel</span>
            <i class="fa-solid fa-xmark close-sidebar" onclick="toggleMenu()"></i>
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            
            <button class="nav-btn active" onclick="showSection('today', this); toggleMenu(true)"><i class="fa-solid fa-calendar-day"></i> Today</button>
            <button class="nav-btn" onclick="showSection('upcoming', this); toggleMenu(true)"><i class="fa-solid fa-calendar-week"></i> Upcoming</button>
            <button class="nav-btn" onclick="showSection('manage', this); toggleMenu(true)"><i class="fa-solid fa-calendar-xmark"></i> Manage All</button>
        </nav>
    </aside>

    <main>
        <header>
            <i class="fa-solid fa-bars mobile-toggle" onclick="toggleMenu()"></i>
            <h2 id="page-title">Today's Appointments</h2>
        </header>

        <section id="today" class="view-section active-view">
            <div class="stat-card">
                <div style="font-size: 2rem; color: var(--primary); margin-right: 15px;"><i class="fa-solid fa-user-check"></i></div>
                <div>
                    <?php
                        $today = date('Y-m-d');
                        $res = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = '$today'");
                        $row = $res->fetch_assoc();
                    ?>
                    <h3><?php echo $row['count']; ?></h3>
                    <p>Total Today</p>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead><tr><th>Time</th><th>Patient</th><th>Issue</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php
                            $sql = "SELECT a.*, p.full_name FROM appointments a JOIN patients p ON a.patient_id = p.patient_id WHERE a.appointment_date = '$today' ORDER BY a.appointment_time ASC";
                            $result = $conn->query($sql);
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . date("g:i A", strtotime($row['appointment_time'])) . "</td>";
                                    echo "<td><strong>" . htmlspecialchars($row['full_name']) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($row['initial_health_issue']) . "</td>";
                                    echo "<td><span class='status status-" . $row['status'] . "'>" . $row['status'] . "</span></td>";
                                    echo "<td>
                                            <a href='meeting_info_notes.php?id=" . $row['appointment_id'] . "' class='btn btn-reschedule'>Manage</a>
                                            <a href='../meeting.php?id=" . $row['appointment_id'] . "' target='_blank' class='btn btn-video'><i class='fa-solid fa-video'></i> Join Call</a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else { echo "<tr><td colspan='5'>No appointments today.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="upcoming" class="view-section">
            <div class="table-container">
                <table>
                    <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php
                            $sql = "SELECT a.*, p.full_name FROM appointments a JOIN patients p ON a.patient_id = p.patient_id WHERE a.appointment_date > '$today' ORDER BY a.appointment_date ASC";
                            $result = $conn->query($sql);
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['appointment_date']}</td>
                                        <td>".date("g:i A", strtotime($row['appointment_time']))."</td>
                                        <td>".htmlspecialchars($row['full_name'])."</td>
                                        <td><span class='status status-{$row['status']}'>{$row['status']}</span></td>
                                        <td><a href='meeting_info_notes.php?id={$row['appointment_id']}' class='btn btn-reschedule'>View</a></td>
                                      </tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="manage" class="view-section">
            <div class="table-container">
                <table>
                    <thead><tr><th>Date</th><th>Patient</th><th>Status</th><th>Manage</th></tr></thead>
                    <tbody>
                        <?php
                            $sql = "SELECT a.*, p.full_name FROM appointments a JOIN patients p ON a.patient_id = p.patient_id ORDER BY a.appointment_date DESC";
                            $result = $conn->query($sql);
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['appointment_date']}</td>
                                        <td>".htmlspecialchars($row['full_name'])."</td>
                                        <td>{$row['status']}</td>
                                        <td><a href='meeting_info_notes.php?id={$row['appointment_id']}' class='btn btn-reschedule'>Edit Info</a></td>
                                      </tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        function showSection(id, btn) {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active-view'));
            document.getElementById(id).classList.add('active-view');
            
            // Remove active class from buttons AND links
            document.querySelectorAll('.nav-btn, .nav-link').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const titles = {'today':"Today's Appointments", 'upcoming':"Upcoming Schedule", 'manage':"Manage Bookings"};
            document.getElementById('page-title').innerText = titles[id];
        }

        function toggleMenu(forceClose = false) {
            const sidebar = document.getElementById('sidebar');
            if(window.innerWidth <= 768) {
                if(forceClose) {
                    sidebar.classList.remove('open');
                } else {
                    sidebar.classList.toggle('open');
                }
            }
        }
    </script>
</body>
</html>