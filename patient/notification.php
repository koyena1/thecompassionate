<?php 
include '../config/db.php'; 
session_start();

// --- CHECK LOGIN ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id']; 

// --- FETCH PATIENT DETAILS (Photo & Name) ---
$sql_patient = "SELECT * FROM patients WHERE patient_id = '$patient_id'";
$result_patient = $conn->query($sql_patient);
$patient_data = $result_patient->fetch_assoc();

// 1. Prepare Name
$display_name = !empty($patient_data['full_name']) ? $patient_data['full_name'] : 'Patient';

// 2. Prepare Image (Logic to use uploaded photo or fallback)
$db_image = !empty($patient_data['profile_image']) ? $patient_data['profile_image'] : 'https://i.pravatar.cc/150?img=33';
$display_img = $db_image . "?v=" . time(); // Add timestamp to force refresh
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- CONSISTENT DASHBOARD STYLES --- */
        :root {
            --bg-color: #F5F6FA;
            --sidebar-width: 240px;
            --primary-purple: #7B61FF;
            --primary-blue: #1FB6FF;
            --primary-green: #00B69B;
            --primary-red: #FF5C60;
            --primary-orange: #FFB800;
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
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            background: #7B3F00;
            padding: 30px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            left: 0;
            top: 0;
        }

        .logo { font-size: 24px; font-weight: 700; margin-bottom: 50px; color: var(--white); display: flex; gap: 10px; }

        .menu-item {
            display: flex; align-items: center; gap: 15px; padding: 15px;
            color: var(--text-light); text-decoration: none; border-radius: 12px;
            margin-bottom: 5px; transition: 0.3s; font-size: 14px;
        }
        .menu-item:hover, .menu-item.active { background-color: var(--text-dark); color: var(--white); }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px 40px;
            width: 100%;
        }

        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .welcome-text h1 { font-size: 24px; font-weight: 600; }

        /* --- NOTIFICATION SPECIFIC STYLES --- */
        .notify-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .notify-list { display: flex; flex-direction: column; gap: 15px; }
        
        .notify-item { 
            background: var(--white); 
            border-radius: var(--radius); 
            padding: 20px; 
            box-shadow: var(--shadow); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            transition: 0.3s; 
            position: relative; 
        }
        
        .notify-item.unread { 
            background: #f8faff; 
            border-left: 4px solid var(--primary-blue); 
        }
        
        .notify-content-wrapper { display: flex; align-items: center; gap: 20px; flex: 1; }
        
        .notify-icon { 
            width: 50px; height: 50px; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 20px; flex-shrink: 0; 
        }
        
        .notify-icon.green { background: rgba(0, 182, 155, 0.1); color: var(--primary-green); }
        .notify-icon.blue { background: rgba(31, 182, 255, 0.1); color: var(--primary-blue); }
        .notify-icon.orange { background: rgba(255, 184, 0, 0.1); color: var(--primary-orange); }
        .notify-icon.red { background: rgba(255, 92, 96, 0.1); color: var(--primary-red); }
        .notify-icon.purple { background: rgba(123, 97, 255, 0.1); color: var(--primary-purple); }
        
        .notify-text h4 { font-size: 15px; margin-bottom: 4px; color: var(--text-dark); }
        .notify-text p { font-size: 12px; color: var(--text-light); margin-bottom: 6px; }
        .notify-time { font-size: 11px; color: var(--text-light); font-weight: 500; }
        
        .notify-action { display: flex; gap: 15px; align-items: center; }
        
        .btn-join-small { 
            background: var(--primary-blue); color: white; border: none; 
            padding: 6px 14px; border-radius: 6px; font-size: 12px; 
            cursor: pointer; text-decoration: none; 
        }
        
        .btn-outline-small {
            background: transparent; border: 1px solid #eee; color: var(--text-light); 
            padding: 6px 14px; border-radius: 6px; font-size: 12px; cursor: pointer;
        }
        
        .dismiss-btn { 
            background: none; border: none; color: #ccc; 
            cursor: pointer; font-size: 16px; transition: 0.3s; margin-left: 10px; 
        }
        .dismiss-btn:hover { color: var(--primary-red); }

        .section-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
            .notify-item { flex-direction: column; align-items: flex-start; gap: 15px; }
            .notify-action { width: 100%; justify-content: flex-end; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div class="welcome-text">
                <h1>Notifications</h1>
                <p>Stay updated with your health journey</p>
            </div>
            
            <div class="user-profile">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="text-align: right;">
                        <h4 style="font-size: 14px;"><?php echo htmlspecialchars($display_name); ?></h4>
                        <p style="font-size: 11px; color: var(--text-light);">Patient</p>
                    </div>
                    <img src="<?php echo htmlspecialchars($display_img); ?>" alt="Profile" style="width:45px; height:45px; border-radius:12px; object-fit:cover;">
                </div>
            </div>
        </header>

        <div class="notify-container">
            <div class="section-header">
                <h3 style="font-size: 18px;">All Notifications</h3>
                <button class="btn-outline-small">Mark all as read</button>
            </div>

            <div class="notify-list">
                
                <div class="notify-item unread">
                    <div class="notify-content-wrapper">
                        <div class="notify-icon green"><i class="fa-solid fa-calendar-check"></i></div>
                        <div class="notify-text">
                            <h4>Booking Confirmed</h4>
                            <p>Your appointment with Dr. Frank Marley is confirmed for 12 May.</p>
                            <span class="notify-time">2 mins ago</span>
                        </div>
                    </div>
                    <button class="dismiss-btn" title="Dismiss"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="notify-item unread">
                    <div class="notify-content-wrapper">
                        <div class="notify-icon blue"><i class="fa-solid fa-video"></i></div>
                        <div class="notify-text">
                            <h4>Meeting Link Added</h4>
                            <p>Dr. Frank added a video link for your upcoming consultation.</p>
                            <span class="notify-time">15 mins ago</span>
                        </div>
                    </div>
                    <div class="notify-action">
                        <a href="#" class="btn-join-small">Join</a>
                        <button class="dismiss-btn" title="Dismiss"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>

                <div class="notify-item">
                    <div class="notify-content-wrapper">
                        <div class="notify-icon red"><i class="fa-solid fa-file-medical"></i></div>
                        <div class="notify-text">
                            <h4>Prescription Uploaded</h4>
                            <p>Dr. Stephen has uploaded the report for your checkup.</p>
                            <span class="notify-time">1 day ago</span>
                        </div>
                    </div>
                    <div class="notify-action">
                        <button class="btn-outline-small">Download</button>
                        <button class="dismiss-btn" title="Dismiss"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>

                <div class="notify-item">
                    <div class="notify-content-wrapper">
                        <div class="notify-icon orange"><i class="fa-regular fa-clock"></i></div>
                        <div class="notify-text">
                            <h4>Appointment Reminder</h4>
                            <p>Reminder: You have a visit scheduled tomorrow at 2:00 PM.</p>
                            <span class="notify-time">1 day ago</span>
                        </div>
                    </div>
                    <button class="dismiss-btn" title="Dismiss"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="notify-item">
                    <div class="notify-content-wrapper">
                        <div class="notify-icon purple"><i class="fa-solid fa-user-doctor"></i></div>
                        <div class="notify-text">
                            <h4>Follow-up Required</h4>
                            <p>Dr. Savannah suggests a follow-up visit regarding your viral fever.</p>
                            <span class="notify-time">3 days ago</span>
                        </div>
                    </div>
                    <button class="dismiss-btn" title="Dismiss"><i class="fa-solid fa-xmark"></i></button>
                </div>

            </div>
        </div>
    </main>

    <script>
        // Dismiss Logic
        const dismissBtns = document.querySelectorAll('.dismiss-btn');
        dismissBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const item = this.closest('.notify-item');
                item.style.opacity = '0';
                item.style.transform = 'translateX(20px)';
                setTimeout(() => item.remove(), 300);
            });
        });
    </script>
</body>
</html>