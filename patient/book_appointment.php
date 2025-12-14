<?php 
include '../config/db.php'; 
session_start();

// --- GET CURRENT USER ID ---
// Use session if available, otherwise default to 1 (for testing)
$patient_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;

$message = "";
$messageType = "";

// --- FETCH PATIENT DETAILS (Photo, Name, Email) ---
$sql_patient = "SELECT * FROM patients WHERE patient_id = '$patient_id'";
$result_patient = $conn->query($sql_patient);
$patient_data = $result_patient->fetch_assoc();

// 1. Prepare Name & Email
$display_name = !empty($patient_data['full_name']) ? $patient_data['full_name'] : 'Patient';
$display_email = !empty($patient_data['email']) ? $patient_data['email'] : '';

// 2. Prepare Image (Logic to use uploaded photo or fallback)
$db_image = !empty($patient_data['profile_image']) ? $patient_data['profile_image'] : 'https://i.pravatar.cc/150?img=33';
$display_img = $db_image . "?v=" . time(); // Add timestamp to force refresh

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $time = $_POST['time']; // This will now receive HH:MM format from the input
    $issue = $_POST['issue'];
    $status = 'Pending'; 

    $sql = "INSERT INTO appointments (patient_id, appointment_date, appointment_time, initial_health_issue, status) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("issss", $patient_id, $date, $time, $issue, $status);
        if ($stmt->execute()) {
            $message = "Appointment booked successfully! The admin will review it.";
            $messageType = "success";
        } else {
            $message = "Error booking appointment: " . $conn->error;
            $messageType = "error";
        }
        $stmt->close();
    } else {
        $message = "Database error: " . $conn->error;
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- COPYING YOUR DASHBOARD STYLES FOR CONSISTENCY --- */
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
            --success-color: #00B69B;
            --error-color: #FF5C60;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body {
            background-color: var(--bg-color);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling (Matches Dashboard) */
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

        /* --- FORM STYLES --- */
        .booking-container {
            background: var(--white);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group { margin-bottom: 20px; }
        
        .form-group label {
            display: block; margin-bottom: 8px; font-size: 14px;
            font-weight: 500; color: var(--text-dark);
        }

        /* Added cursor pointer for time input to make it feel clickable */
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 12px; border: 1px solid #eee;
            border-radius: 10px; outline: none; background: #FAFAFA;
            font-family: 'Poppins', sans-serif; color: var(--text-dark);
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--primary-blue); background: #fff;
        }

        .full-width { grid-column: span 2; }

        .book-btn {
            background: var(--primary-blue); color: white; border: none;
            padding: 15px 30px; border-radius: 12px; font-weight: 600;
            cursor: pointer; width: 100%; margin-top: 10px; transition: 0.3s;
        }

        .book-btn:hover { background: #0d9adb; }

        /* Alert Messages */
        .alert {
            padding: 15px; border-radius: 10px; margin-bottom: 20px;
            text-align: center; font-size: 14px;
        }
        .alert.success { background: rgba(0, 182, 155, 0.1); color: var(--success-color); border: 1px solid var(--success-color); }
        .alert.error { background: rgba(255, 92, 96, 0.1); color: var(--error-color); border: 1px solid var(--error-color); }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { display: none; } /* Simplified for mobile */
            .main-content { margin-left: 0; padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div class="welcome-text">
                <h1>Book Appointment</h1>
                <p>Schedule a visit with our doctors</p>
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

        <?php if(!empty($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="booking-container">
            <form method="POST" action="">
                <div class="form-grid">
                    
                    <div class="form-group">
                        <label>Select Date</label>
                        <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label>Preferred Time</label>
                        <input type="time" name="time" required>
                    </div>

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($display_name); ?>" disabled style="background: #eee; cursor: not-allowed;">
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Email</label>
                        <input type="text" value="<?php echo htmlspecialchars($display_email); ?>" disabled style="background: #eee; cursor: not-allowed;">
                    </div>

                    <div class="form-group full-width">
                        <label>Health Issue (Reason for visit)</label>
                        <textarea name="issue" rows="4" placeholder="Describe your symptoms (e.g., Fever, Back pain...)" required></textarea>
                    </div>

                    <div class="full-width">
                        <button type="submit" class="book-btn">Confirm Appointment</button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</body>
</html>