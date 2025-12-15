<?php 
include '../config/db.php'; 
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id']; 

// --- 1. AUTO-MARK AS READ ON PAGE LOAD ---
// As soon as the patient opens this page, we mark all unread notifications as 'read' (1).
// This ensures that when they go back to the dashboard, the RED DOT is gone.
$conn->query("UPDATE notifications SET is_read = 1 WHERE patient_id = '$patient_id' AND is_read = 0");

// --- 2. FETCH NOTIFICATIONS ---
// We fetch them normally to display them
$sql_notifs = "SELECT * FROM notifications WHERE patient_id = '$patient_id' ORDER BY created_at DESC";
$result_notifs = $conn->query($sql_notifs);

// Fetch Patient Details for the header
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
    <title>Notifications</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Exact CSS as before */
        :root { --bg-color: #F5F6FA; --sidebar-width: 240px; --primary-purple: #7B61FF; --primary-blue: #1FB6FF; --primary-green: #00B69B; --primary-red: #FF5C60; --primary-orange: #FFB800; --text-dark: #2D3436; --text-light: #A0A4A8; --white: #FFFFFF; --shadow: 0 4px 15px rgba(0,0,0,0.03); --radius: 20px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-dark); display: flex; min-height: 100vh; }
        .sidebar { width: var(--sidebar-width); background: #7B3F00; padding: 30px; display: flex; flex-direction: column; position: fixed; height: 100%; left: 0; top: 0; }
        .menu-item { display: flex; align-items: center; gap: 15px; padding: 15px; color: var(--text-light); text-decoration: none; border-radius: 12px; margin-bottom: 5px; transition: 0.3s; font-size: 14px; }
        .menu-item:hover, .menu-item.active { background-color: var(--text-dark); color: var(--white); }
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 30px 40px; width: 100%; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .welcome-text h1 { font-size: 24px; font-weight: 600; }
        .notify-container { max-width: 900px; margin: 0 auto; }
        .notify-list { display: flex; flex-direction: column; gap: 15px; }
        .notify-item { background: var(--white); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); display: flex; align-items: center; justify-content: space-between; transition: 0.3s; position: relative; }
        .notify-item.unread { background: #f8faff; border-left: 4px solid var(--primary-blue); }
        .notify-content-wrapper { display: flex; align-items: center; gap: 20px; flex: 1; }
        .notify-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .notify-icon.green { background: rgba(0, 182, 155, 0.1); color: var(--primary-green); }
        .notify-icon.blue { background: rgba(31, 182, 255, 0.1); color: var(--primary-blue); }
        .notify-icon.red { background: rgba(255, 92, 96, 0.1); color: var(--primary-red); }
        .notify-icon.purple { background: rgba(123, 97, 255, 0.1); color: var(--primary-purple); }
        .notify-text h4 { font-size: 15px; margin-bottom: 4px; color: var(--text-dark); }
        .notify-text p { font-size: 12px; color: var(--text-light); margin-bottom: 6px; }
        .notify-time { font-size: 11px; color: var(--text-light); font-weight: 500; }
        .notify-action { display: flex; gap: 15px; align-items: center; }
        .btn-join-small { background: var(--primary-blue); color: white; border: none; padding: 6px 14px; border-radius: 6px; font-size: 12px; cursor: pointer; text-decoration: none; }
        .btn-outline-small { background: transparent; border: 1px solid #eee; color: var(--text-light); padding: 6px 14px; border-radius: 6px; font-size: 12px; cursor: pointer; text-decoration: none; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 20px; } .notify-item { flex-direction: column; align-items: flex-start; gap: 15px; } .notify-action { width: 100%; justify-content: flex-end; } }
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
                <span style="font-size:12px; color:var(--text-light);">All marked as read</span>
            </div>

            <div class="notify-list">
                <?php 
                if ($result_notifs->num_rows > 0) {
                    while($row = $result_notifs->fetch_assoc()) {
                        // Determine Styling
                        $bgClass = ""; 
                        $iconClass = "fa-bell"; $colorClass = "purple"; 

                        if($row['type'] == 'appointment_confirmed') { $iconClass = "fa-calendar-check"; $colorClass = "green"; } 
                        elseif ($row['type'] == 'meeting_link') { $iconClass = "fa-video"; $colorClass = "blue"; } 
                        elseif ($row['type'] == 'prescription') { $iconClass = "fa-file-medical"; $colorClass = "red"; }

                        // Calculate Time Ago
                        $timeAgo = time() - strtotime($row['created_at']);
                        if ($timeAgo < 3600) { $timeTxt = floor($timeAgo/60) . " mins ago"; }
                        else if ($timeAgo < 86400) { $timeTxt = floor($timeAgo/3600) . " hours ago"; }
                        else { $timeTxt = floor($timeAgo/86400) . " days ago"; }
                ?>

                <div class="notify-item"> <div class="notify-content-wrapper">
                        <div class="notify-icon <?php echo $colorClass; ?>">
                            <i class="fa-solid <?php echo $iconClass; ?>"></i>
                        </div>
                        <div class="notify-text">
                            <h4><?php echo htmlspecialchars($row['title']); ?></h4>
                            <p><?php echo htmlspecialchars($row['message']); ?></p>
                            <span class="notify-time"><?php echo $timeTxt; ?></span>
                        </div>
                    </div>
                    
                    <?php if($row['type'] == 'meeting_link'): ?>
                        <div class="notify-action"><a href="appointments.php" class="btn-join-small">Join</a></div>
                    <?php endif; ?>
                    
                    <?php if($row['type'] == 'prescription'): ?>
                        <div class="notify-action"><a href="prescriptions.php" class="btn-outline-small">View</a></div>
                    <?php endif; ?>
                </div>

                <?php 
                    } 
                } else {
                    echo "<p style='text-align:center; color:#999;'>No notifications found.</p>";
                }
                ?>
            </div>
        </div>
    </main>
</body>
</html>