<?php 
include '../config/db.php'; 
session_start();

// --- CHECK LOGIN ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id']; 

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
        /* --- CONSISTENT DASHBOARD STYLES --- */
        :root {
            --bg-color: #F5F6FA;
            --sidebar-width: 240px;
            --primary-purple: #7B61FF;
            --primary-red: #FF5C60;
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

        .presc-card:hover { transform: translateY(-5px); border-color: #eee; }

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
        
        .presc-actions { display: flex; gap: 10px; margin-top: 5px; }
        
        .btn-download {
            flex: 1; background: var(--text-dark); color: white;
            border: none; padding: 10px; border-radius: 8px;
            cursor: pointer; font-size: 13px; text-decoration: none;
            display: flex; justify-content: center; align-items: center; gap: 8px;
            transition: 0.3s;
        }
        
        .btn-view {
            width: 40px; background: #f5f5f5; color: var(--text-dark);
            border: none; border-radius: 8px; cursor: pointer;
            display: flex; justify-content: center; align-items: center;
            transition: 0.3s; text-decoration: none;
        }

        .btn-download:hover { background: #000; }
        .btn-view:hover { background: #e0e0e0; }

        .no-data { grid-column: 1 / -1; text-align: center; color: var(--text-light); padding: 50px; }

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
            
            <?php
            // --- DYNAMICALLY FETCH PRESCRIPTIONS ---
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
                    
                    // Determine File Type & Links
                    if ($row['prescription_type'] == 'Upload') {
                        $icon = '<i class="fa-solid fa-file-pdf"></i>';
                        $title = "Prescription File";
                        $link = $row['file_url']; // Link to uploaded file
                        $download_attr = "download";
                    } else {
                        // Digital (Text) Prescription
                        $icon = '<i class="fa-solid fa-file-prescription"></i>';
                        $title = "Digital Prescription";
                        // Create a data URI to allow downloading text as a file
                        $text_content = htmlspecialchars($row['digital_content']);
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

</body>
</html>