<?php 
    session_start(); // Added to access the dark mode session variable
    include('../config/db.php'); 
    $current_page = basename($_SERVER['PHP_SELF']);

    // --- DARK MODE LOGIC ---
    // Check if a preference is stored in the session (set by your profile page)
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
        /* CSS Variables for easy theme switching */
        :root {
            --page-bg: #f8f9fa;
            --card-bg: #ffffff;
            --text-main: #2D3436;
            --text-sub: #636e72;
            --border-color: #eeeeee;
            --feature-bg: #fcfcfc;
        }

        /* DARK MODE OVERRIDES */
        body.dark-mode {
            --page-bg: #1a1a2e;
            --card-bg: #16213e;
            --text-main: #e0e0e0;
            --text-sub: #b0b0b0;
            --border-color: #252545;
            --feature-bg: #0f3460;
        }

        body { background: var(--page-bg); margin: 0; font-family: 'Inter', 'Segoe UI', sans-serif; transition: background 0.3s ease; color: var(--text-main); }
        
        .main-content { margin-left: 240px; padding: 50px 20px; min-height: 100vh; transition: margin-left 0.3s ease; }

        .profile-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: background 0.3s ease;
        }

        /* Hero Section remains consistent in both modes */
        .profile-hero {
            background: linear-gradient(135deg, #7B3F00 0%, #5D3000 100%);
            padding: 60px 40px;
            display: flex;
            align-items: center;
            gap: 40px;
            color: white;
        }

        .hero-img-container { position: relative; }

        .profile-img {
            width: 200px;
            height: 200px;
            border-radius: 25px;
            object-fit: cover;
            border: 6px solid rgba(255,255,255,0.15);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .img-placeholder {
            width: 200px;
            height: 200px;
            background: #fdfdfd;
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            color: #7B3F00;
        }

        .hero-text h1 { font-size: 36px; margin: 0; font-weight: 800; letter-spacing: -1px; }
        .hero-text p { font-size: 18px; opacity: 0.9; margin: 10px 0 20px 0; font-weight: 300; }
        
        .badge-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .verified-badge {
            background: rgba(255,255,255,0.2);
            padding: 6px 15px;
            border-radius: 50px;
            font-size: 13px;
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Content Section */
        .profile-content { padding: 50px; display: grid; grid-template-columns: 1.8fr 1fr; gap: 50px; }

        .bio-section h3 {
            font-size: 22px;
            color: var(--text-main);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .bio-section h3::after {
            content: "";
            flex-grow: 1;
            height: 1px;
            background: var(--border-color);
        }

        .bio-text {
            font-size: 16px;
            line-height: 1.8;
            color: var(--text-sub);
            text-align: justify;
        }

        /* Sidebar info cards */
        .info-panel { display: flex; flex-direction: column; gap: 20px; }
        
        .feature-card {
            background: var(--feature-bg);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            transition: 0.3s;
        }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.03); }

        .feature-card i {
            width: 45px;
            height: 45px;
            background: #fff3e6;
            color: #7B3F00;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .feature-card h4 { margin: 0 0 5px 0; color: var(--text-main); font-size: 15px; }
        .feature-card p { margin: 0; color: #7B3F00; font-weight: 700; font-size: 16px; }

        .book-btn {
            background: #7B3F00;
            color: white;
            text-decoration: none;
            padding: 20px;
            border-radius: 20px;
            text-align: center;
            font-weight: 700;
            font-size: 18px;
            display: block;
            margin-top: 10px;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(123, 63, 0, 0.2);
        }
        .book-btn:hover { background: #5D3000; transform: scale(1.02); }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; }
            .profile-content { grid-template-columns: 1fr; }
            .profile-hero { flex-direction: column; text-align: center; }
            .badge-row { justify-content: center; }
        }
    </style>
</head>
<body class="<?php echo $dark_class; ?>">

    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="profile-wrapper">
            
            <div class="profile-hero">
                <div class="hero-img-container">
                    <?php if(!empty($doctor['profile_image'])): ?>
                        <img src="../<?php echo $doctor['profile_image']; ?>" class="profile-img">
                    <?php else: ?>
                        <div class="img-placeholder">
                            <i class="fa-solid fa-user-doctor"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="hero-text">
                    <div class="badge-row">
                        <span class="verified-badge"><i class="fa-solid fa-circle-check"></i> Verified Provider</span>
                        <span class="verified-badge"><i class="fa-solid fa-award"></i> Top Rated</span>
                    </div>
                    <h1><?php echo htmlspecialchars($doctor['full_name']); ?></h1>
                    <p><?php echo htmlspecialchars($doctor['specialization']); ?> Specialist</p>
                </div>
            </div>

            <div class="profile-content">
                
                <div class="bio-section">
                    <h3><i class="fa-solid fa-book-medical"></i> Professional Biography</h3>
                    <div class="bio-text">
                        <?php echo nl2br(htmlspecialchars($doctor['about_doctor'] ?? 'Biography details are currently being updated by the clinical staff. Please check back soon for more information about the doctor\'s background and expertise.')); ?>
                    </div>
                </div>

                <div class="info-panel">
                    <div class="feature-card">
                        <i class="fa-regular fa-clock"></i>
                        <h4>Clinic Hours</h4>
                        <p><?php echo htmlspecialchars($doctor['clinic_hours']); ?></p>
                    </div>

                    <div class="feature-card">
                        <i class="fa-regular fa-envelope"></i>
                        <h4>Direct Contact</h4>
                        <p><?php echo htmlspecialchars($doctor['email']); ?></p>
                    </div>

                    <a href="book_appointment.php" class="book-btn">
                        <i class="fa-regular fa-calendar-check"></i> Book Appointment
                    </a>
                </div>

            </div>
        </div>
    </div>

</body>
</html>