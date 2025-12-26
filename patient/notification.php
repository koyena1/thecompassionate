<?php 
include '../config/db.php'; 
session_start();

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

// --- 1. AUTO-MARK AS READ ON PAGE LOAD ---
$conn->query("UPDATE notifications SET is_read = 1 WHERE patient_id = '$patient_id' AND is_read = 0");

// --- 2. FETCH NOTIFICATIONS ---
$sql_notifs = "SELECT * FROM notifications WHERE patient_id = '$patient_id' ORDER BY created_at DESC";
$result_notifs = $conn->query($sql_notifs);

// Fetch Patient Details
$sql_patient = "SELECT * FROM patients WHERE patient_id = '$patient_id'";
$result_patient = $conn->query($sql_patient);
$patient_data = $result_patient->fetch_assoc();
$display_name = !empty($patient_data['full_name']) ? $patient_data['full_name'] : 'Patient';
$db_image = !empty($patient_data['profile_image']) ? $patient_data['profile_image'] : 'https://i.pravatar.cc/150?img=33';
$display_img = $db_image . "?v=" . time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Patient Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --bg-color: #F8F9FD; 
            --sidebar-width: 240px; 
            --primary-brown: #7B3F00;
            --primary-purple: #7B61FF; 
            --primary-blue: #1FB6FF; 
            --primary-green: #00B69B; 
            --primary-red: #FF5C60; 
            --text-dark: #2D3436; 
            --text-main: #4A4A4A; 
            --text-light: #757575; 
            --white: #FFFFFF; 
            --shadow: 0 10px 30px rgba(0,0,0,0.06); 
            --radius: 24px; 
            --border-color: #f1f1f1;
        }

        body.dark-mode {
            --bg-color: #0F172A;
            --text-dark: #F8FAFC; 
            --text-main: #E2E8F0;
            --text-light: #94A3B8;
            --white: #1E293B;
            --shadow: 0 10px 30px rgba(0,0,0,0.2);
            --border-color: #2D3A4F;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-dark); display: flex; min-height: 100vh; transition: 0.3s ease; overflow-x: hidden; }
        
        /* SIDEBAR TOGGLE CLASSES */
        body.sidebar-closed .sidebar { transform: translateX(-100%); }
        body.sidebar-closed .main-content { margin-left: 0; width: 100%; }

        /* SIDEBAR */
        .sidebar { 
            width: var(--sidebar-width); 
            background: var(--primary-brown); 
            position: fixed; 
            height: 100%; 
            left: 0; top: 0; 
            z-index: 1001;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Sidebar Close Button (X) */
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
        
        .sidebar-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        /* OVERLAY */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            backdrop-filter: blur(2px);
        }
        body.sidebar-open .sidebar-overlay { display: block; }

        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 40px; width: calc(100% - var(--sidebar-width)); transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1), width 0.4s ease; }
        
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        
        .welcome-text h1 { font-size: 26px; font-weight: 700; color: var(--text-dark); }
        .welcome-text p { color: var(--text-dark); font-size: 14px; opacity: 1; } 
        
        /* HAMBURGER ICON */
        .toggle-btn { 
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
            margin-right: 20px;
            transition: all 0.3s ease;
        }
        .toggle-btn:hover { transform: scale(1.05); }

        .notify-container { max-width: 900px; margin: 0 auto; }
        .section-header { margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }
        .section-header h3 { font-size: 18px; font-weight: 600; color: var(--text-dark); }
        
        .notify-list { display: flex; flex-direction: column; gap: 16px; }
        
        .notify-item { 
            background: var(--white); 
            border-radius: var(--radius); 
            padding: 24px; 
            box-shadow: var(--shadow); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            transition: 0.4s; 
            border: 1px solid var(--border-color);
        }

        .notify-content-wrapper { display: flex; align-items: center; gap: 20px; flex: 1; }
        
        .notify-icon { 
            width: 56px; height: 56px; border-radius: 18px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 22px; flex-shrink: 0; 
        }

        .notify-icon.green { background: rgba(0, 182, 155, 0.12); color: var(--primary-green); }
        .notify-icon.blue { background: rgba(31, 182, 255, 0.12); color: var(--primary-blue); }
        .notify-icon.red { background: rgba(255, 92, 96, 0.12); color: var(--primary-red); }
        .notify-icon.purple { background: rgba(123, 97, 255, 0.12); color: var(--primary-purple); }

        .notify-text h4 { font-size: 16px; font-weight: 600; margin-bottom: 4px; color: var(--text-dark); }
        .notify-text p { font-size: 14px; color: var(--text-main); line-height: 1.5; font-weight: 400; }
        
        .time-badge { 
            font-size: 11px; color: var(--text-dark); font-weight: 500; 
            background: rgba(0,0,0,0.04); padding: 5px 12px; border-radius: 50px; 
            display: inline-block; margin-top: 8px;
        }

        body.dark-mode .time-badge { background: rgba(255,255,255,0.06); }

        .notify-action { display: flex; gap: 12px; margin-left: 20px; }

        .btn-action {
            padding: 10px 20px; border-radius: 12px; font-size: 13px; 
            font-weight: 600; cursor: pointer; text-decoration: none; 
            transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;
        }

        .btn-join { background: var(--primary-blue); color: white; border: none; }
        .btn-view { background: var(--bg-color); color: var(--text-dark); border: 1px solid var(--border-color); }

        @media (max-width: 768px) { 
            .sidebar { transform: translateX(-100%); } 
            body.sidebar-open .sidebar { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 25px; width: 100%; } 
            .notify-item { flex-direction: column; align-items: flex-start; } 
            .notify-action { margin-left: 0; width: 100%; margin-top: 15px; } 
        }
    </style>
</head>
<body class="<?php echo $dark_class; ?>">

    <div class="sidebar-overlay" id="overlay"></div>

    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close-btn" id="close-btn">
            <i class="fa-solid fa-times"></i>
        </button>
        <?php include 'sidebar.php'; ?>
    </aside>

    <main class="main-content" id="main-content">
        <header>
            <div class="welcome-container" style="display:flex; align-items:center;">
                <div class="toggle-btn" id="toggle-btn">
                    <i class="fa-solid fa-bars-staggered"></i>
                </div>
                <div class="welcome-text">
                    <h1>Notifications</h1>
                    <p>Track updates for your medical journey</p> 
                </div>
            </div>
            
            <div class="user-profile">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="text-align: right;">
                        <h4 style="font-size: 14px; font-weight: 600; color: var(--text-dark);"><?php echo htmlspecialchars($display_name); ?></h4>
                        <p style="font-size: 11px; color: var(--text-light);">Patient Portal</p>
                    </div>
                    <img src="<?php echo htmlspecialchars($display_img); ?>" alt="Profile" style="width:48px; height:48px; border-radius:14px; object-fit:cover; box-shadow: var(--shadow);">
                </div>
            </div>
        </header>

        <div class="notify-container">
            <div class="section-header">
                <h3>Recent Updates</h3>
            </div>

            <div class="notify-list">
                <?php 
                if ($result_notifs->num_rows > 0) {
                    while($row = $result_notifs->fetch_assoc()) {
                        $iconClass = "fa-bell"; $colorClass = "purple"; 

                        if($row['type'] == 'appointment_confirmed') { $iconClass = "fa-calendar-check"; $colorClass = "green"; } 
                        elseif ($row['type'] == 'meeting_link') { $iconClass = "fa-video"; $colorClass = "blue"; } 
                        elseif ($row['type'] == 'prescription') { $iconClass = "fa-file-medical"; $colorClass = "red"; }

                        $timeAgo = time() - strtotime($row['created_at']);
                        if ($timeAgo < 3600) { $timeTxt = floor($timeAgo/60) . " mins ago"; }
                        else if ($timeAgo < 86400) { $timeTxt = floor($timeAgo/3600) . " hours ago"; }
                        else { $timeTxt = floor($timeAgo/86400) . " days ago"; }
                ?>

                <div class="notify-item"> 
                    <div class="notify-content-wrapper">
                        <div class="notify-icon <?php echo $colorClass; ?>">
                            <i class="fa-solid <?php echo $iconClass; ?>"></i>
                        </div>
                        <div class="notify-text">
                            <h4><?php echo htmlspecialchars($row['title']); ?></h4>
                            <p><?php echo htmlspecialchars($row['message']); ?></p> 
                            <span class="time-badge"><i class="fa-regular fa-clock" style="margin-right:5px;"></i><?php echo $timeTxt; ?></span>
                        </div>
                    </div>
                    
                    <div class="notify-action">
                        <?php if($row['type'] == 'meeting_link'): ?>
                            <a href="myapp.php" class="btn-action btn-join"><i class="fa-solid fa-video"></i> Join</a>
                        <?php endif; ?>
                        
                        <?php if($row['type'] == 'prescription'): ?>
                            <a href="prescrip.php" class="btn-action btn-view"><i class="fa-regular fa-eye"></i> View Rx</a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php 
                    } 
                } else {
                    echo "<div class='empty-state' style='text-align:center; padding:40px; color:var(--text-light);'><p>No notifications found.</p></div>";
                }
                ?>
            </div>
        </div>
    </main>

    <script>
        const toggleBtn = document.getElementById('toggle-btn');
        const closeBtn = document.getElementById('close-btn');
        const overlay = document.getElementById('overlay');
        const body = document.body;

        // Toggle Sidebar
        toggleBtn.addEventListener('click', function() {
            if (window.innerWidth > 768) {
                // Laptop/Desktop toggle logic
                body.classList.toggle('sidebar-closed');
            } else {
                // Mobile overlay logic
                body.classList.add('sidebar-open');
            }
        });

        // Close Sidebar (Mobile and Desktop)
        function closeSidebar() {
            if (window.innerWidth > 768) {
                body.classList.add('sidebar-closed');
            } else {
                body.classList.remove('sidebar-open');
            }
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeSidebar);
        }
        
        overlay.addEventListener('click', function() {
            body.classList.remove('sidebar-open');
        });

        // Close mobile sidebar on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                body.classList.remove('sidebar-open');
            }
        });

        // Auto-fix layout on resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                body.classList.remove('sidebar-open');
            }
        });
    </script>
</body>
</html>