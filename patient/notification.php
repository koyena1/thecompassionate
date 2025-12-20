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
            --text-dark: #2D3436; /* Dark text for light mode headers */
            --text-main: #4A4A4A; /* Contrast text for descriptions */
            --text-light: #757575; /* Visible grey for sub-text */
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
        body { background-color: var(--bg-color); color: var(--text-dark); display: flex; min-height: 100vh; transition: 0.3s ease; }
        
        /* SIDEBAR */
        .sidebar { 
            width: var(--sidebar-width); 
            background: var(--primary-brown); 
            position: fixed; 
            height: 100%; 
            left: 0; top: 0; 
            z-index: 1001;
            transition: transform 0.3s ease;
        }

        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 40px; width: 100%; transition: 0.3s ease; }
        
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        
        /* FIXED: Ensuring the sub-title below "Notifications" is visible */
        .welcome-text h1 { font-size: 26px; font-weight: 700; color: var(--text-dark); }
        .welcome-text p { color: var(--text-dark); font-size: 14px; opacity: 1; } /* Resetting opacity */
        
        #toggle-btn { font-size: 22px; cursor: pointer; margin-right: 20px; color: var(--text-dark); display: none; }

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

        /* FIXED: Notification Body Text visibility */
        .notify-text h4 { font-size: 16px; font-weight: 600; margin-bottom: 4px; color: var(--text-dark); }
        .notify-text p { font-size: 14px; color: var(--text-main); line-height: 1.5; font-weight: 400; }
        
        /* FIXED: Time text visibility */
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
            #toggle-btn { display: block; } 
            .main-content { margin-left: 0; padding: 25px; } 
            .notify-item { flex-direction: column; align-items: flex-start; } 
            .notify-action { margin-left: 0; width: 100%; margin-top: 15px; } 
        }
    </style>
</head>
<body class="<?php echo $dark_class; ?>">

    <div class="sidebar-overlay" id="overlay"></div>

    <aside class="sidebar" id="sidebar">
        <i class="fa-solid fa-xmark" id="close-btn" style="position:absolute; top:25px; right:25px; color:white; font-size:24px; cursor:pointer; display:none;"></i>
        <?php include 'sidebar.php'; ?>
    </aside>

    <main class="main-content">
        <header>
            <div class="welcome-container">
                <i class="fa-solid fa-bars-staggered" id="toggle-btn"></i>
                <div class="welcome-text">
                    <h1>Notifications</h1>
                    <p>Track updates for your medical journey</p> </div>
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
                            <p><?php echo htmlspecialchars($row['message']); ?></p> <span class="time-badge"><i class="fa-regular fa-clock" style="margin-right:5px;"></i><?php echo $timeTxt; ?></span>
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
                    echo "<div class='empty-state'><p>No notifications found.</p></div>";
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

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => body.classList.add('toggled'));
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', () => body.classList.remove('toggled'));
        }
        overlay.addEventListener('click', () => body.classList.remove('toggled'));
    </script>
</body>
</html>