<?php 
include '../config/db.php'; 
session_start();

// --- CHECK LOGIN ---
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

// --- FETCH PATIENT DETAILS ---
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
    <title>My Prescriptions</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #F5F6FA;
            --sidebar-width: 260px;
            --primary-brown: #7B3F00;
            --primary-purple: #7B61FF;
            --primary-red: #FF5C60;
            --text-dark: #2D3436;
            --text-light: #A0A4A8;
            --white: #FFFFFF;
            --shadow: 0 4px 15px rgba(0,0,0,0.03);
            --radius: 20px;
            --btn-view-bg: #f0f0f0;
        }

        body.dark-mode {
            --bg-color: #0F172A;
            --text-dark: #F8FAFC;
            --text-light: #94A3B8;
            --white: #1E293B;
            --shadow: 0 4px 15px rgba(0,0,0,0.2);
            --btn-view-bg: #252d4a;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
            transition: background 0.3s ease;
        }

        /* --- SIDEBAR STYLING --- */
        .sidebar {
            width: var(--sidebar-width);
            background: #7B3F00;
            padding: 30px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            z-index: 1001;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            left: 0; top: 0;
        }

        body.sidebar-off .sidebar { transform: translateX(-100%); }
        body.sidebar-off .main-content { margin-left: 0; padding-left: 40px; }

        .sidebar-close-btn {
            position: absolute;
            top: 20px; right: 20px; width: 32px; height: 32px;
            border-radius: 50%; background: rgba(255, 255, 255, 0.2);
            color: white; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; transition: all 0.3s ease; z-index: 1002;
        }
        .sidebar-close-btn:hover { background: rgba(255, 255, 255, 0.3); transform: rotate(90deg); }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0; background: rgba(0,0,0,0.4);
            z-index: 1000; backdrop-filter: blur(2px);
        }
        body.mobile-sidebar-on .sidebar-overlay { display: block; }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px 40px 30px 70px; 
            width: calc(100% - var(--sidebar-width));
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .welcome-container { display: flex; align-items: center; gap: 15px; }

        #toggle-btn {
            font-size: 20px; cursor: pointer; color: var(--text-dark);
            background: var(--white); width: 45px; height: 45px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px; box-shadow: var(--shadow); border: none;
            transition: 0.3s;
        }
        #toggle-btn:hover { transform: scale(1.05); }

        .user-profile { display: flex; align-items: center; gap: 20px; }
        .profile-info { display: flex; align-items: center; gap: 10px; }
        .profile-info img { width: 45px; height: 45px; border-radius: 12px; object-fit: cover; }

        /* --- PRESCRIPTION CARDS --- */
        .presc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .presc-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 20px;
            transition: 0.3s;
            border: 1px solid transparent;
        }

        .presc-card:hover { transform: translateY(-5px); border-color: rgba(123, 63, 0, 0.2); }
        .presc-header { display: flex; align-items: center; gap: 15px; }

        .file-icon {
            width: 50px; height: 50px;
            background: rgba(255, 92, 96, 0.1);
            color: var(--primary-red);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }

        .presc-info h4 { font-size: 16px; margin-bottom: 2px; }
        .presc-info p { font-size: 12px; color: var(--text-light); }
        
        .presc-actions { display: flex; gap: 12px; margin-top: 5px; align-items: stretch; }
        
        /* UPDATED: Download button changed from Purple to Brown */
        .btn-download {
            flex: 1; 
            background: var(--primary-brown); 
            color: white;
            border: none; padding: 12px; border-radius: 12px;
            cursor: pointer; font-size: 14px; font-weight: 500;
            text-decoration: none; display: flex; justify-content: center; 
            align-items: center; gap: 8px; transition: all 0.3s ease;
        }
        
        .btn-view {
            width: 48px; background: var(--btn-view-bg); color: var(--text-dark);
            border: none; border-radius: 12px; cursor: pointer;
            display: flex; justify-content: center; align-items: center;
            transition: all 0.3s ease; text-decoration: none; font-size: 18px;
        }

        .btn-download:hover { background: #5D3000; transform: scale(1.02); }
        .btn-view:hover { background: #dcdde1; color: var(--primary-brown); }

        .no-data { grid-column: 1 / -1; text-align: center; color: var(--text-light); padding: 50px; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            body.mobile-sidebar-on .sidebar { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; width: 100%; }
            .presc-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="<?php echo $dark_class; ?>">

    <div class="sidebar-overlay" id="overlay"></div>

    <div class="sidebar" id="sidebar-container">
        <button class="sidebar-close-btn" id="sidebar-close-btn">
            <i class="fa-solid fa-times"></i>
        </button>
        <?php include 'sidebar.php'; ?>
    </div>

    <main class="main-content">
        <header>
            <div class="welcome-container">
                <button id="toggle-btn"><i class="fa-solid fa-bars"></i></button>
                <div class="welcome-text">
                    <h1>My Prescriptions</h1>
                    <p style="color: var(--text-light);">View and download your medical records</p>
                </div>
            </div>
            
            <div class="user-profile">
                <div class="profile-info">
                    <div style="text-align: right;">
                        <h4 style="font-size: 14px;"><?php echo htmlspecialchars($display_name); ?></h4>
                        <p style="font-size: 11px; color: var(--text-light);">Patient Portal</p>
                    </div>
                    <img src="<?php echo htmlspecialchars($display_img); ?>" alt="Patient Profile">
                </div>
            </div>
        </header>

        <div class="presc-grid">
            <?php
            $p_sql = "SELECT pr.*, a.appointment_date, ad.full_name as dr_name 
                      FROM prescriptions pr 
                      JOIN appointments a ON pr.appointment_id = a.appointment_id 
                      JOIN admin_users ad ON a.admin_id = ad.admin_id 
                      WHERE pr.patient_id = '$patient_id' 
                      ORDER BY pr.created_at DESC";
            $p_res = $conn->query($p_sql);

            if ($p_res->num_rows > 0) {
                while($row = $p_res->fetch_assoc()) {
                    $date = date("d F Y", strtotime($row['appointment_date']));
                    $doctor = htmlspecialchars($row['dr_name']);
                    
                    if ($row['prescription_type'] == 'Upload') {
                        $icon = '<i class="fa-solid fa-file-pdf"></i>';
                        $title = "Prescription File";
                        $link = $row['file_url']; 
                        $download_attr = "download";
                    } else {
                        $icon = '<i class="fa-solid fa-file-prescription"></i>';
                        $title = "Digital Prescription";
                        $link = "data:text/plain;charset=utf-8," . rawurlencode($row['digital_content']);
                        $download_attr = "download='prescription_".$date.".txt'";
                    }
            ?>
            
            <div class="presc-card">
                <div class="presc-header">
                    <div class="file-icon"><?php echo $icon; ?></div>
                    <div class="presc-info">
                        <h4><?php echo $title; ?></h4>
                        <p>Dr. <?php echo $doctor; ?> • <?php echo $date; ?></p>
                    </div>
                </div>
                <div class="presc-actions">
                    <?php if($row['prescription_type'] == 'Upload'): ?>
                        <a href="<?php echo htmlspecialchars($link); ?>" class="btn-download" download>
                            <i class="fa-solid fa-download"></i> Download
                        </a>
                        <a href="<?php echo htmlspecialchars($link); ?>" class="btn-view" target="_blank">
                            <i class="fa-regular fa-eye"></i>
                        </a>
                    <?php else: ?>
                         <a href="<?php echo $link; ?>" class="btn-download" <?php echo $download_attr; ?>>
                            <i class="fa-solid fa-download"></i> Download
                        </a>
                        <button class="btn-view" onclick="alert('<?php echo str_replace(array("\r", "\n"), "\\n", addslashes($row['digital_content'])); ?>')">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php 
                }
            } else {
                echo '<div class="no-data"><i class="fa-solid fa-folder-open" style="font-size: 40px; margin-bottom: 10px;"></i><p>No prescriptions found yet.</p></div>';
            }
            ?>
        </div>
    </main>

    <script>
        const toggleBtn = document.getElementById('toggle-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const overlay = document.getElementById('overlay');
        const body = document.body;

        function handleSidebar() {
            if (window.innerWidth > 768) {
                body.classList.toggle('sidebar-off'); 
            } else {
                body.classList.toggle('mobile-sidebar-on'); 
            }
        }

        function closeSidebar() {
            body.classList.remove('mobile-sidebar-on');
            if (window.innerWidth > 768) body.classList.add('sidebar-off');
        }

        if (toggleBtn) toggleBtn.addEventListener('click', handleSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', () => body.classList.remove('mobile-sidebar-on'));

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSidebar();
        });
    </script>
</body>
</html>