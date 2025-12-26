<?php 
    session_start(); 
    include('../config/db.php'); 
    
    // Get the current file name for sidebar highlighting
    $current_page = basename($_SERVER['PHP_SELF']);

    // --- DARK MODE LOGIC ---
    $dark_class = (isset($_SESSION['pref_dark_mode']) && $_SESSION['pref_dark_mode'] == 1) ? 'dark-mode' : '';

    // Fetch Dr. Usri Sengupta's data (admin_id 1)
    $query = "SELECT * FROM admin_users WHERE admin_id = 1";
    $result = $conn->query($query);
    $doctor = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About the Doctor | Patient Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --page-bg: #f8f9fa;
            --card-bg: #ffffff;
            --text-main: #2D3436;
            --text-sub: #636e72;
            --border-color: #eeeeee;
            --feature-bg: #fcfcfc;
            --sidebar-width: 260px;
        }

        body.dark-mode {
            --page-bg: #1a1a2e;
            --card-bg: #16213e;
            --text-main: #e0e0e0;
            --text-sub: #b0b0b0;
            --border-color: #252545;
            --feature-bg: #0f3460;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { 
            background: var(--page-bg); 
            margin: 0; 
            font-family: 'Inter', 'Segoe UI', sans-serif; 
            transition: background 0.3s ease; 
            color: var(--text-main); 
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* --- SIDEBAR STYLING --- */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #7B3F00 0%, #4a2600 100%);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            z-index: 1001;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            left: 0;
            top: 0;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        body.sidebar-off .sidebar { 
            transform: translateX(-100%); 
        }

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
        
        /* Main Layout Styles */
        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 30px 40px 30px 70px; 
            flex: 1;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
            position: relative;
            width: calc(100% - var(--sidebar-width));
        }

        body.sidebar-off .main-content { 
            margin-left: 0; 
            width: 100%;
            padding-left: 40px;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .hamburger-btn {
            font-size: 20px;
            cursor: pointer;
            color: #7B3F00;
            background: var(--card-bg);
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: none;
            transition: transform 0.2s ease;
        }

        .hamburger-btn:hover { transform: scale(1.05); }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            backdrop-filter: blur(2px);
        }

        /* --- PROFILE UI STYLES --- */
        .profile-wrapper {
            max-width: 1000px;
            background: var(--card-bg);
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .profile-hero {
            background: linear-gradient(135deg, #7B3F00 0%, #5D3000 100%);
            padding: 60px 40px;
            display: flex;
            align-items: center;
            gap: 40px;
            color: white;
        }

        .profile-img {
            width: 180px; height: 180px; border-radius: 25px;
            object-fit: cover; border: 6px solid rgba(255,255,255,0.15);
        }

        .hero-text h1 { font-size: 32px; margin: 0; font-weight: 800; }
        .hero-text p { font-size: 16px; opacity: 0.9; margin: 5px 0 15px 0; }
        
        .badge-row { display: flex; gap: 10px; }
        .verified-badge {
            background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 50px;
            font-size: 12px; display: flex; align-items: center; gap: 6px;
        }

        .profile-content { padding: 40px; display: grid; grid-template-columns: 1.8fr 1fr; gap: 40px; }
        .bio-section h3 { font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .bio-section h3::after { content: ""; flex-grow: 1; height: 1px; background: var(--border-color); }
        .bio-text { font-size: 15px; line-height: 1.7; color: var(--text-sub); }

        .feature-card {
            background: var(--feature-bg); padding: 20px; border-radius: 20px;
            border: 1px solid var(--border-color); margin-bottom: 15px;
        }
        .feature-card i {
            width: 40px; height: 40px; background: #fff3e6; color: #7B3F00;
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 18px; margin-bottom: 10px;
        }
        .feature-card p { margin: 0; color: #7B3F00; font-weight: 700; }

        .book-btn {
            background: #7B3F00; color: white; text-decoration: none; padding: 18px;
            border-radius: 15px; text-align: center; font-weight: 700; display: block;
        }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; width: 100%; padding: 20px; }
            .profile-content { grid-template-columns: 1fr; }
            .profile-hero { flex-direction: column; text-align: center; }
            body.mobile-sidebar-on .sidebar-overlay { display: block; }
        }
    </style>
</head>
<body class="<?php echo $dark_class; ?>">

    <div class="sidebar-overlay" id="overlay"></div>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <button class="hamburger-btn" id="toggle-btn">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h2 style="font-size: 18px; font-weight: 600; margin: 0;">Doctor Profile</h2>
        </div>

        <div class="profile-wrapper">
            <div class="profile-hero">
                <div class="hero-img-container">
                    <?php if(!empty($doctor['profile_image'])): ?>
                        <img src="../<?php echo $doctor['profile_image']; ?>" class="profile-img">
                    <?php else: ?>
                        <div style="width: 180px; height: 180px; background: #fdfdfd; border-radius: 25px; display: flex; align-items: center; justify-content: center; font-size: 60px; color: #7B3F00;">
                            <i class="fa-solid fa-user-doctor"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="hero-text">
                    <div class="badge-row">
                        <span class="verified-badge"><i class="fa-solid fa-circle-check"></i> Verified</span>
                        <span class="verified-badge"><i class="fa-solid fa-award"></i> Top Rated</span>
                    </div>
                    <h1><?php echo htmlspecialchars($doctor['full_name'] ?? 'Doctor Name'); ?></h1>
                    <p><?php echo htmlspecialchars($doctor['specialization'] ?? 'Medical'); ?> Specialist</p>
                </div>
            </div>

            <div class="profile-content">
                <div class="bio-section">
                    <h3>Professional Biography</h3>
                    <div class="bio-text">
                        <?php echo nl2br(htmlspecialchars($doctor['about_doctor'] ?? 'Details are being updated by the medical center. Please check back later for a full professional history.')); ?>
                    </div>
                </div>

                <div class="info-panel">
                    <div class="feature-card">
                        <i class="fa-regular fa-clock"></i>
                        <h4 style="font-size:14px; margin:0 0 5px 0;">Clinic Hours</h4>
                        <p><?php echo htmlspecialchars($doctor['clinic_hours'] ?? 'Mon - Fri: 9AM - 5PM'); ?></p>
                    </div>

                    <div class="feature-card">
                        <i class="fa-regular fa-envelope"></i>
                        <h4 style="font-size:14px; margin:0 0 5px 0;">Contact</h4>
                        <p><?php echo htmlspecialchars($doctor['email'] ?? 'contact@clinic.com'); ?></p>
                    </div>

                    <a href="book_appointment.php" class="book-btn">Book Appointment</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('toggle-btn');
        const overlay = document.getElementById('overlay');
        const body = document.body;

        // Note: The 'sidebar-close-btn' is inside sidebar.php
        // We use event delegation or wait for the DOM to ensure it's clickable
        document.addEventListener('click', function(e) {
            if (e.target && (e.target.id === 'sidebar-close-btn' || e.target.closest('#sidebar-close-btn'))) {
                if(window.innerWidth > 992) body.classList.add('sidebar-off');
                else body.classList.remove('mobile-sidebar-on');
            }
        });

        function toggleMenu() {
            if (window.innerWidth > 992) {
                body.classList.toggle('sidebar-off');
            } else {
                body.classList.toggle('mobile-sidebar-on');
            }
        }

        if(toggleBtn) toggleBtn.addEventListener('click', toggleMenu);
        if(overlay) overlay.addEventListener('click', () => body.classList.remove('mobile-sidebar-on'));
    </script>
</body>
</html>