<?php 
// FILE: psychiatrist/admin/manage appoinment.php
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
        :root {
            --primary: #0ea5e9; --primary-dark: #0284c7; --sidebar-bg: #1e293b; --sidebar-text: #94a3b8;
            --bg-body: #f1f5f9; --card-bg: #ffffff; --text-main: #334155; --danger: #ef4444; --success: #22c55e;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; height: 100vh; background-color: var(--bg-body); color: var(--text-main); overflow: hidden; }
        aside { width: 260px; background-color: var(--sidebar-bg); color: white; display: flex; flex-direction: column; transition: 0.3s; }
        .brand { padding: 2rem; font-size: 1.5rem; font-weight: bold; color: var(--primary); border-bottom: 1px solid #334155; }
        nav { flex: 1; padding-top: 1rem; }
        nav button { background: none; border: none; width: 100%; padding: 1rem 2rem; text-align: left; color: var(--sidebar-text); cursor: pointer; font-size: 1rem; transition: 0.2s; display: flex; align-items: center; gap: 10px; }
        nav button:hover, nav button.active { background-color: #334155; color: white; border-left: 4px solid var(--primary); }
        main { flex: 1; padding: 2rem; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h2 { font-size: 1.8rem; color: #1e293b; }
        .view-section { display: none; animation: fadeIn 0.4s ease; }
        .view-section.active-view { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: var(--card-bg); padding: 1.5rem; border-radius: 10px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 50px; height: 50px; border-radius: 50%; background-color: #e0f2fe; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .table-container { background: var(--card-bg); border-radius: 10px; box-shadow: var(--shadow); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #f8fafc; font-weight: 600; color: #64748b; }
        tr:hover { background-color: #f1f5f9; }
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-cancel { background-color: #fee2e2; color: var(--danger); }
        .btn-cancel:hover { background-color: var(--danger); color: white; }
        .btn-reschedule { background-color: #e0f2fe; color: var(--primary); }
        .btn-reschedule:hover { background-color: var(--primary); color: white; }
        .btn-video { background-color: #7B61FF; color: white; margin-left: 5px; }
        .btn-video:hover { background-color: #6347ea; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .status-Confirmed { background: #dcfce7; color: #166534; }
        .status-Pending { background: #ffedd5; color: #9a3412; }
        .status-Cancelled { background: #fee2e2; color: #991b1b; }
        @media(max-width: 768px) { body { flex-direction: column; height: auto; } aside { width: 100%; flex-direction: row; align-items: center; padding: 10px; } .brand { padding: 0.5rem; border: none; font-size: 1.2rem; } nav { display: flex; padding: 0; overflow-x: auto; } nav button { padding: 0.5rem 1rem; font-size: 0.9rem; width: auto; white-space: nowrap;} }
    </style>
</head>
<body>

    <aside>
        <div class="brand"><i class="fa-solid fa-heart-pulse"></i> AdminPanel</div>
        <nav>
            <button class="nav-btn active" onclick="showSection('today', this)">
                <i class="fa-solid fa-calendar-day"></i> Today
            </button>
            <button class="nav-btn" onclick="showSection('upcoming', this)">
                <i class="fa-solid fa-calendar-week"></i> Upcoming
            </button>
            <button class="nav-btn" onclick="showSection('manage', this)">
                <i class="fa-solid fa-calendar-xmark"></i> Manage All
            </button>
        </nav>
    </aside>

    <main>
        <header>
            <h2 id="page-title">Today's Appointments</h2>
            <div class="user-info">Admin: Dr. Smith</div>
        </header>

        <section id="today" class="view-section active-view">
            <div class="stats-grid">
                <?php
                    $today = date('Y-m-d');
                    $sql_count = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = '$today'";
                    $res_count = $conn->query($sql_count);
                    $row_count = $res_count->fetch_assoc();
                ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
                    <div>
                        <h3><?php echo $row_count['count']; ?></h3>
                        <p>Total Today</p>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table id="today-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Patient Name</th>
                            <th>Issue</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $sql = "SELECT a.*, p.full_name FROM appointments a 
                                    JOIN patients p ON a.patient_id = p.patient_id 
                                    WHERE a.appointment_date = '$today'
                                    ORDER BY a.appointment_time ASC";
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
                            } else {
                                echo "<tr><td colspan='5'>No appointments today.</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="upcoming" class="view-section">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $sql = "SELECT a.*, p.full_name FROM appointments a 
                                    JOIN patients p ON a.patient_id = p.patient_id 
                                    WHERE a.appointment_date > '$today'
                                    ORDER BY a.appointment_date ASC";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['appointment_date'] . "</td>";
                                    echo "<td>" . date("g:i A", strtotime($row['appointment_time'])) . "</td>";
                                    echo "<td><strong>" . htmlspecialchars($row['full_name']) . "</strong></td>";
                                    echo "<td><span class='status status-" . $row['status'] . "'>" . $row['status'] . "</span></td>";
                                    echo "<td><a href='meeting_info_notes.php?id=" . $row['appointment_id'] . "' class='btn btn-reschedule'>View</a></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No upcoming appointments.</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="manage" class="view-section">
            <div style="margin-bottom: 1rem; padding: 1rem; background: #fff; border-radius: 8px;">
                <input type="text" placeholder="Search functionality requires JS..." disabled style="padding: 0.5rem; width: 100%; border: 1px solid #ddd; border-radius: 5px;">
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient Name</th>
                            <th>Status</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php
                            $sql = "SELECT a.*, p.full_name FROM appointments a 
                                    JOIN patients p ON a.patient_id = p.patient_id 
                                    ORDER BY a.appointment_date DESC";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['appointment_date'] . "</td>";
                                    echo "<td><strong>" . htmlspecialchars($row['full_name']) . "</strong></td>";
                                    echo "<td>" . $row['status'] . "</td>";
                                    echo "<td>
                                            <a href='meeting_info_notes.php?id=" . $row['appointment_id'] . "' class='btn btn-reschedule'>Edit Info</a>
                                          </td>";
                                    echo "</tr>";
                                }
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        function showSection(sectionId, btnElement) {
            const titles = {
                'today': "Today's Appointments",
                'upcoming': "Upcoming Schedule",
                'manage': "Manage Bookings"
            };
            document.getElementById('page-title').innerText = titles[sectionId];
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active-view'));
            document.getElementById(sectionId).classList.add('active-view');
            document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');
        }
    </script>
</body>
</html>