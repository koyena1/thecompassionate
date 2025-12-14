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
    <title>My Prescriptions</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- CONSISTENT DASHBOARD STYLES --- */
        :root {
            --bg-color: #F5F6FA;
            --sidebar-width: 240px;
            --primary-purple: #7B61FF;
            --primary-red: #FF5C60;
            --primary-orange: #FFB800;
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

        /* --- PRESCRIPTIONS SPECIFIC STYLES --- */
        .presc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .presc-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 15px;
            transition: 0.3s;
            border: 1px solid transparent;
        }

        .presc-card:hover {
            transform: translateY(-5px);
            border-color: #eee;
        }

        .presc-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .file-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 92, 96, 0.1);
            color: var(--primary-red);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .presc-info h4 { font-size: 16px; margin-bottom: 2px; }
        .presc-info p { font-size: 12px; color: var(--text-light); }
        
        .presc-actions {
            display: flex;
            gap: 10px;
            margin-top: 5px;
        }
        
        .btn-download {
            flex: 1;
            background: var(--text-dark);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        
        .btn-view {
            width: 40px;
            background: #f5f5f5;
            color: var(--text-dark);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: 0.3s;
        }

        .btn-download:hover { background: #000; }
        .btn-view:hover { background: #e0e0e0; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
            .presc-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div class="welcome-text">
                <h1>My Prescriptions</h1>
                <p>View and download your medical records</p>
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

        <div class="presc-grid">
            
            <div class="presc-card">
                <div class="presc-header">
                    <div class="file-icon"><i class="fa-solid fa-file-pdf"></i></div>
                    <div class="presc-info">
                        <h4>Viral Fever Treatment</h4>
                        <p>Dr. Savannah • 10 April 2024</p>
                    </div>
                </div>
                <div class="presc-actions">
                    <button class="btn-download"><i class="fa-solid fa-download"></i> Download</button>
                    <button class="btn-view"><i class="fa-regular fa-eye"></i></button>
                </div>
            </div>

            <div class="presc-card">
                <div class="presc-header">
                    <div class="file-icon"><i class="fa-solid fa-file-pdf"></i></div>
                    <div class="presc-info">
                        <h4>General Checkup Report</h4>
                        <p>Dr. Stephen • 22 March 2024</p>
                    </div>
                </div>
                <div class="presc-actions">
                    <button class="btn-download"><i class="fa-solid fa-download"></i> Download</button>
                    <button class="btn-view"><i class="fa-regular fa-eye"></i></button>
                </div>
            </div>

            <div class="presc-card">
                <div class="presc-header">
                    <div class="file-icon"><i class="fa-solid fa-file-pdf"></i></div>
                    <div class="presc-info">
                        <h4>Heart Rate Monitoring</h4>
                        <p>Dr. Frank • 15 Feb 2024</p>
                    </div>
                </div>
                <div class="presc-actions">
                    <button class="btn-download"><i class="fa-solid fa-download"></i> Download</button>
                    <button class="btn-view"><i class="fa-regular fa-eye"></i></button>
                </div>
            </div>

            <div class="presc-card">
                <div class="presc-header">
                    <div class="file-icon"><i class="fa-solid fa-file-pdf"></i></div>
                    <div class="presc-info">
                        <h4>Blood Test Results</h4>
                        <p>Lab Assistant • 10 Feb 2024</p>
                    </div>
                </div>
                <div class="presc-actions">
                    <button class="btn-download"><i class="fa-solid fa-download"></i> Download</button>
                    <button class="btn-view"><i class="fa-regular fa-eye"></i></button>
                </div>
            </div>

        </div>
    </main>

</body>
</html>