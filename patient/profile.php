<?php 
session_start();
include '../config/db.php'; 

// Check if user is logged in as patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id']; 
$success_msg = "";
$error_msg = "";

// --- HANDLE FORM SUBMISSION (UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name  = $conn->real_escape_string($_POST['full_name'] ?? '');
    $raw_age    = $_POST['age'] ?? '';
    $age_sql    = ($raw_age === '') ? "NULL" : "'" . $conn->real_escape_string($raw_age) . "'";

    $phone      = $conn->real_escape_string($_POST['phone'] ?? '');
    $email      = $conn->real_escape_string($_POST['email'] ?? '');
    $address    = $conn->real_escape_string($_POST['address'] ?? '');
    $blood_type = $conn->real_escape_string($_POST['blood_type'] ?? '');
    $height     = $conn->real_escape_string($_POST['height'] ?? '');
    $weight     = $conn->real_escape_string($_POST['weight'] ?? '');
    $allergies  = $conn->real_escape_string($_POST['allergies'] ?? '');

    $pref_email = isset($_POST['pref_email']) ? 1 : 0;
    $pref_sms   = isset($_POST['pref_sms']) ? 1 : 0;
    $pref_dark  = isset($_POST['pref_dark']) ? 1 : 0;

    $image_update_sql = "";
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_extension = pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION);
        $new_filename = "pt_" . $patient_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        if (in_array(strtolower($file_extension), $allowed)) {
            if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                $image_update_sql = ", profile_image = '$target_file'";
            } else {
                $error_msg = "Failed to move uploaded file.";
            }
        } else {
            $error_msg = "Invalid file type.";
        }
    }

    if (empty($error_msg)) {
        $sql_update = "UPDATE patients SET 
            full_name='$full_name', 
            age=$age_sql, 
            phone_number='$phone', 
            email='$email', 
            address='$address', 
            blood_type='$blood_type', 
            height='$height', 
            weight='$weight', 
            allergies='$allergies', 
            pref_email_notif='$pref_email', 
            pref_sms_notif='$pref_sms', 
            pref_dark_mode='$pref_dark' 
            $image_update_sql 
            WHERE patient_id='$patient_id'";
        
        if ($conn->query($sql_update) === TRUE) {
            $success_msg = "Profile updated!";
            $_SESSION['full_name'] = $full_name;
            $_SESSION['pref_dark_mode'] = $pref_dark;
        } else {
            $error_msg = "Database Error: " . $conn->error;
        }
    }
}

// --- FETCH PATIENT DATA ---
$sql_patient = "SELECT * FROM patients WHERE patient_id = '$patient_id'";
$result_patient = $conn->query($sql_patient);
$patient_data = $result_patient->fetch_assoc();

// Store preference in session for global site theme
$_SESSION['pref_dark_mode'] = $patient_data['pref_dark_mode'];

$display_name = $patient_data['full_name'] ?? 'Patient';
$display_age  = $patient_data['age'] ?? ''; 
$display_email = $patient_data['email'] ?? '';
$display_phone = $patient_data['phone_number'] ?? '';
$display_address = $patient_data['address'] ?? '';

$db_image = !empty($patient_data['profile_image']) ? $patient_data['profile_image'] : 'https://i.pravatar.cc/150?img=33';
$display_img = $db_image . "?v=" . time();

$blood = $patient_data['blood_type'] ?? 'O+';
$ht = $patient_data['height'] ?? '0';
$wt = $patient_data['weight'] ?? '0';
$alrg = $patient_data['allergies'] ?? 'None';

$p_email = ($patient_data['pref_email_notif'] == 1) ? 'checked' : '';
$p_sms   = ($patient_data['pref_sms_notif'] == 1) ? 'checked' : '';
$p_dark  = ($patient_data['pref_dark_mode'] == 1) ? 'checked' : '';
$dark_class = ($patient_data['pref_dark_mode'] == 1) ? 'dark-mode' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile & Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-color: #F5F6FA; --sidebar-width: 240px; --primary-blue: #1FB6FF; --text-dark: #2D3436; --text-light: #A0A4A8; --white: #FFFFFF; --shadow: 0 4px 15px rgba(0,0,0,0.03); --radius: 20px; --input-bg: #FAFAFA; --border-color: #eee; }
        body.dark-mode { --bg-color: #1a1a2e; --text-dark: #e0e0e0; --text-light: #b0b0b0; --white: #16213e; --shadow: 0 4px 15px rgba(0,0,0,0.2); --input-bg: #0f3460; --border-color: #0f3460; }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: #7B3F00; padding: 30px; display: flex; flex-direction: column; position: fixed; height: 100%; left: 0; top: 0; z-index: 1001; transition: transform 0.3s ease; }
        .close-sidebar { display: none; position: absolute; top: 20px; right: 20px; color: white; font-size: 24px; cursor: pointer; z-index: 1002; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 1000; }

        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 30px 40px; width: 100%; transition: 0.3s ease; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        .welcome-container { display: flex; align-items: center; }
        .welcome-text h1 { font-size: 24px; font-weight: 600; }
        #toggle-btn { font-size: 24px; cursor: pointer; margin-right: 20px; color: var(--text-dark); display: none; }

        .user-profile { display: flex; align-items: center; gap: 20px; }
        .profile-info img { width: 45px; height: 45px; border-radius: 12px; object-fit: cover; }
        
        .profile-header-card { background: var(--white); border-radius: var(--radius); padding: 30px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 30px; margin-bottom: 30px; }
        .profile-img-container { position: relative; width: 100px; height: 100px; }
        .profile-img-lg { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-blue); }
        .upload-icon { position: absolute; bottom: 0; right: 0; background: var(--primary-blue); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid var(--white); }
        
        .medical-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 30px; }
        .medical-card { background: var(--white); padding: 20px; border-radius: 15px; text-align: center; box-shadow: var(--shadow); }
        .med-input { width: 100%; border: none; background: transparent; font-size: 18px; font-weight: 600; color: var(--text-dark); text-align: center; outline: none; border-bottom: 1px dashed var(--text-light); }
        
        .settings-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; }
        .settings-panel { background: var(--white); border-radius: var(--radius); padding: 30px; box-shadow: var(--shadow); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--input-bg); color: var(--text-dark); outline: none; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary-blue); }
        input:checked + .slider:before { transform: translateX(20px); }
        
        .save-btn { background: var(--text-light); color: #fff; border: none; padding: 12px 25px; border-radius: 10px; cursor: pointer; font-size: 14px; margin-top: 20px; width: 100%; }
        .setting-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--border-color); }
        
        .alert-box { padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; text-align: center; }
        .success { background: rgba(0, 182, 155, 0.1); color: #00B69B; }
        .error { background: rgba(255, 92, 96, 0.1); color: #FF5C60; }

        @media (max-width: 992px) { .medical-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { 
            .sidebar { transform: translateX(-100%); }
            .close-sidebar { display: block; }
            #toggle-btn { display: block; }
            .main-content { margin-left: 0; padding: 20px; }
            body.toggled .sidebar { transform: translateX(0); }
            body.toggled .sidebar-overlay { display: block; }
            .settings-grid, .form-grid, .medical-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="<?php echo $dark_class; ?>">
    <div class="sidebar-overlay" id="overlay"></div>
    <div class="sidebar" id="sidebar">
        <i class="fa-solid fa-xmark close-sidebar" id="close-sidebar-btn"></i>
        <?php include 'sidebar.php'; ?>
    </div>

    <main class="main-content">
        <header>
            <div class="welcome-container">
                <i class="fa-solid fa-bars" id="toggle-btn"></i>
                <div class="welcome-text"><h1>Profile & Settings</h1><p>Manage your account</p></div>
            </div>
            <div class="user-profile">
                <div class="profile-info">
                    <div style="text-align: right;"><h4 style="font-size: 14px;"><?php echo htmlspecialchars($display_name); ?></h4><p style="font-size: 11px; color: var(--text-light);">Patient</p></div>
                    <img src="<?php echo htmlspecialchars($display_img); ?>" alt="Profile">
                </div>
            </div>
        </header>

        <form action="" method="POST" enctype="multipart/form-data">
            <?php if($success_msg) echo "<div class='alert-box success'>$success_msg</div>"; ?>
            <?php if($error_msg) echo "<div class='alert-box error'>$error_msg</div>"; ?>

            <div class="profile-header-card">
                <div class="profile-img-container">
                    <img src="<?php echo htmlspecialchars($display_img); ?>" id="previewImg" class="profile-img-lg">
                    <label for="profileUpload" class="upload-icon"><i class="fa-solid fa-camera"></i></label>
                    <input type="file" id="profileUpload" name="profile_photo" style="display: none;" accept="image/*" onchange="previewFile()">
                </div>
                <div class="profile-header-info">
                    <h2><?php echo htmlspecialchars($display_name); ?></h2>
                    <p>Patient ID: #PT-<?php echo $patient_id; ?></p>
                </div>
            </div>

            <h3 style="margin-bottom: 15px; font-size: 18px;">Medical Profile (Editable)</h3>
            <div class="medical-grid">
                <div class="medical-card"><h4>Age (Yrs)</h4><input type="number" name="age" class="med-input" value="<?php echo htmlspecialchars($display_age); ?>"></div>
                <div class="medical-card"><h4>Blood Type</h4><input type="text" name="blood_type" class="med-input" value="<?php echo htmlspecialchars($blood); ?>" style="color: #FF5C60;"></div>
                <div class="medical-card"><h4>Height (cm)</h4><input type="text" name="height" class="med-input" value="<?php echo htmlspecialchars($ht); ?>"></div>
                <div class="medical-card"><h4>Weight (kg)</h4><input type="text" name="weight" class="med-input" value="<?php echo htmlspecialchars($wt); ?>"></div>
                <div class="medical-card"><h4>Allergies</h4><input type="text" name="allergies" class="med-input" value="<?php echo htmlspecialchars($alrg); ?>"></div>
            </div>

            <div class="settings-grid">
                <div class="settings-panel">
                    <h3 style="margin-bottom: 20px; font-size: 18px;">Personal Information</h3>
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($display_name); ?>" required></div>
                    <div class="form-grid">
                        <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?php echo htmlspecialchars($display_phone); ?>"></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($display_email); ?>" required></div>
                    </div>
                    <div class="form-group"><label>Address</label><input type="text" name="address" value="<?php echo htmlspecialchars($display_address); ?>"></div>
                    <button type="submit" class="save-btn">Save All Changes</button>
                </div>

                <div class="settings-panel">
                    <h3 style="margin-bottom: 20px; font-size: 18px;">Settings</h3>
                    <div class="setting-item"><div><h4 style="font-size: 14px;">Email Notifications</h4></div><label class="switch"><input type="checkbox" name="pref_email" <?php echo $p_email; ?>><span class="slider"></span></label></div>
                    <div class="setting-item"><div><h4 style="font-size: 14px;">WhatsApp / SMS</h4></div><label class="switch"><input type="checkbox" name="pref_sms" <?php echo $p_sms; ?>><span class="slider"></span></label></div>
                    <div class="setting-item"><div><h4 style="font-size: 14px;">Dark Mode</h4></div><label class="switch"><input type="checkbox" name="pref_dark" id="darkModeToggle" <?php echo $p_dark; ?>><span class="slider"></span></label></div>
                </div>
            </div>
        </form>
    </main>

    <script>
        function previewFile() {
            const preview = document.getElementById('previewImg');
            const file = document.querySelector('input[type=file]').files[0];
            const reader = new FileReader();
            reader.addEventListener("load", function () { preview.src = reader.result; }, false);
            if (file) { reader.readAsDataURL(file); }
        }

        const toggleBtn = document.getElementById('toggle-btn');
        const closeSidebarBtn = document.getElementById('close-sidebar-btn');
        const overlay = document.getElementById('overlay');
        const body = document.body;
        const darkModeToggle = document.getElementById('darkModeToggle');

        // Dark Mode Logic with AJAX for instant global update
        darkModeToggle.addEventListener('change', () => {
            const isDark = darkModeToggle.checked ? 1 : 0;
            if(isDark) body.classList.add('dark-mode');
            else body.classList.remove('dark-mode');

            const formData = new FormData();
            formData.append('pref_dark', isDark);
            fetch('update_theme.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => { if(!data.success) console.error("Theme sync failed"); });
        });

        // Sidebar Logic
        toggleBtn.addEventListener('click', () => { body.classList.add('toggled'); });
        closeSidebarBtn.addEventListener('click', () => { body.classList.remove('toggled'); });
        overlay.addEventListener('click', () => { body.classList.remove('toggled'); });
    </script>
</body>
</html>