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
    <title>Health Timeline</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- CONSISTENT DASHBOARD STYLES --- */
        :root {
            --bg-color: #F5F6FA;
            --sidebar-width: 240px;
            --primary-purple: #7B61FF;
            --primary-blue: #1FB6FF;
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

        /* User Profile in Header */
        .user-profile { display: flex; align-items: center; gap: 20px; }
        .profile-info { display: flex; align-items: center; gap: 10px; }
        .profile-info img { width: 45px; height: 45px; border-radius: 12px; object-fit: cover; }

        /* --- TIMELINE SPECIFIC STYLES --- */
        .timeline-wrapper {
            position: relative;
            padding-left: 20px;
            border-left: 2px solid #eee;
            margin-left: 10px;
            margin-top: 10px;
            max-width: 800px; /* Constrain width for readability */
        }

        .timeline-item { position: relative; margin-bottom: 40px; }
        .timeline-item:last-child { margin-bottom: 0; }

        .timeline-dot {
            width: 16px; height: 16px;
            background: var(--white);
            border: 3px solid var(--primary-purple);
            border-radius: 50%;
            position: absolute;
            left: -29px; /* Adjust to sit on the line */
            top: 0; z-index: 2;
        }
        
        .timeline-dot.old { border-color: #ccc; }
        .timeline-date { font-size: 12px; color: var(--text-light); margin-bottom: 8px; font-weight: 500; }

        .timeline-content {
            background: var(--white); padding: 25px;
            border-radius: 15px; box-shadow: var(--shadow);
            transition: 0.3s; cursor: default;
        }
        
        .timeline-content:hover { transform: translateX(5px); }

        .timeline-content h4 { font-size: 16px; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center; }
        .timeline-content h4 span { font-size: 11px; background: #f5f5f5; padding: 4px 8px; border-radius: 4px; color: var(--text-dark); font-weight: 400; }
        .timeline-content p { font-size: 13px; color: var(--text-light); line-height: 1.5; }
        
        .doctor-ref {
            margin-top: 15px; padding-top: 15px;
            border-top: 1px solid #f5f5f5;
            display: flex; align-items: center;
            gap: 10px; font-size: 12px; font-weight: 500;
        }
        
        .doctor-ref img { width: 25px; height: 25px; border-radius: 50%; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div class="welcome-text">
                <h1>My Health Journey</h1>
                <p>Track your medical history and diagnosis</p>
            </div>
            
            <div class="user-profile">
                <div class="profile-info">
                    <div style="text-align: right;">
                        <h4 style="font-size: 14px;"><?php echo htmlspecialchars($display_name); ?></h4>
                        <p style="font-size: 11px; color: var(--text-light);">Patient</p>
                    </div>
                    <img src="<?php echo htmlspecialchars($display_img); ?>" alt="Patient Profile">
                </div>
            </div>
        </header>

        <div class="timeline-wrapper">
            
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-date">April 10, 2024</div>
                <div class="timeline-content">
                    <h4>Viral Fever Diagnosis <span>Checked</span></h4>
                    <p>Diagnosed with high fever. Prescribed antibiotics and rest for 3 days. Follow up required if temperature rises above 101°F.</p>
                    <div class="doctor-ref">
                        <img src="https://i.pravatar.cc/150?img=5" alt="Doctor">
                        <span>Dr. Savannah Nguyen (Dermatology)</span>
                    </div>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-dot old"></div>
                <div class="timeline-date">March 22, 2024</div>
                <div class="timeline-content">
                    <h4>Annual General Checkup <span>Routine</span></h4>
                    <p>Overall health stats are good. Blood pressure is normal (120/80). Recommended to increase daily water intake.</p>
                    <div class="doctor-ref">
                        <img src="https://i.pravatar.cc/150?img=11" alt="Doctor">
                        <span>Dr. Stephen Conley (General)</span>
                    </div>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-dot old"></div>
                <div class="timeline-date">Feb 15, 2024</div>
                <div class="timeline-content">
                    <h4>Heart Rate Monitoring <span>Test</span></h4>
                    <p>ECG report generated. Heart rate showed slight fluctuation due to stress. Advised meditation and sleep tracking.</p>
                    <div class="doctor-ref">
                        <img src="https://i.pravatar.cc/150?img=59" alt="Doctor">
                        <span>Dr. Frank Marley (Cardiology)</span>
                    </div>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-dot old"></div>
                <div class="timeline-date">Jan 05, 2024</div>
                <div class="timeline-content">
                    <h4>Initial Registration <span>Admin</span></h4>
                    <p>Patient profile created in MedEx system. Medical history form submitted and insurance details verified.</p>
                </div>
            </div>

        </div>
    </main>

</body>
</html>