<?php
session_start();
include '../config/db.php'; 

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];
$message = "";

// --- FETCH DARK MODE PREFERENCE ---
if(!isset($_SESSION['pref_dark_mode'])) {
    $pref_res = $conn->query("SELECT pref_dark_mode FROM patients WHERE patient_id = '$patient_id'");
    $pref_row = $pref_res->fetch_assoc();
    $_SESSION['pref_dark_mode'] = $pref_row['pref_dark_mode'] ?? 0;
}
$dark_class = ($_SESSION['pref_dark_mode'] == 1) ? 'dark-mode' : '';

// Handle File Upload Logic (Functionality remains exactly as provided)
if (isset($_POST['upload_doc'])) {
    $target_dir = "uploads/patient_docs/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_name = basename($_FILES["file_to_upload"]["name"]);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_file_name = "doc_" . $patient_id . "_" . time() . "." . $file_type;
    $target_file = $target_dir . $new_file_name;

    $allowed_types = array("pdf", "jpg", "jpeg", "png");
    if (in_array($file_type, $allowed_types)) {
        if (move_uploaded_file($_FILES["file_to_upload"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO patient_uploads (patient_id, file_name, file_path, file_type) 
                    VALUES ('$patient_id', '$file_name', '$target_file', '$file_type')";
            mysqli_query($conn, $sql);
            $message = "<div class='alert alert-success border-0 shadow-sm animate__animated animate__fadeIn'>🎉 File uploaded successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger border-0 shadow-sm'>❌ Error uploading file.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning border-0 shadow-sm'>⚠️ Only PDF, JPG, JPEG, & PNG files are allowed.</div>";
    }
}

// Fetch Admin Clinical Updates
$admin_doc = mysqli_query($conn, "SELECT * FROM documentation WHERE id=1");
$doc = mysqli_fetch_assoc($admin_doc);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation | Patient Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        :root {
            --bg-color: #F8F9FD;
            --text-dark: #2D3436;
            --white: #FFFFFF;
            --shadow: 0 10px 30px rgba(0,0,0,0.04);
            --border-color: #edf2f7;
            --primary-brown: #7B3F00;
            --sidebar-width: 240px;
        }

        body.dark-mode {
            --bg-color: #0F172A;
            --text-dark: #F8FAFC;
            --white: #1E293B;
            --shadow: 0 10px 30px rgba(0,0,0,0.2);
            --border-color: #2D3A4F;
        }

        body { 
            background: var(--bg-color); 
            color: var(--text-dark);
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 40px; 
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* Top Header Area */
        .page-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            gap: 15px;
        }

        #toggle-btn {
            font-size: 20px;
            cursor: pointer;
            color: var(--text-dark);
            background: var(--white);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        /* Card Styling */
        .custom-card { 
            background: var(--white);
            color: var(--text-dark);
            border-radius: 24px; 
            border: 1px solid var(--border-color); 
            margin-bottom: 25px; 
            padding: 30px;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .custom-card:hover {
            transform: translateY(-5px);
        }

        .card-title {
            color: var(--primary-brown);
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title i {
            background: rgba(123, 63, 0, 0.1);
            padding: 10px;
            border-radius: 12px;
        }

        .content-text {
            line-height: 1.8;
            color: var(--text-dark);
            opacity: 0.9;
        }

        /* Upload Area Styling */
        .upload-zone {
            border: 2px dashed var(--border-color);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            background: rgba(0,0,0,0.01);
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            color: var(--text-dark);
        }

        .btn-upload {
            background: linear-gradient(135deg, #7B3F00 0%, #5D3000 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(123, 63, 0, 0.2);
            transition: all 0.3s ease;
        }

        .btn-upload:hover {
            transform: scale(1.05);
            background: linear-gradient(135deg, #5D3000 0%, #3D2000 100%);
        }

        @media (max-width: 768px) { 
            .main-content { margin-left: 0; padding: 20px; } 
            #toggle-btn { display: flex; }
        }
    </style>
</head>
<body class="<?php echo $dark_class; ?>">
    
    <?php include('sidebar.php'); ?>

    <div class="main-content">
        <div class="page-header">
            <div id="toggle-btn"><i class="fa-solid fa-bars"></i></div>
            <div>
                <h2 class="fw-bold mb-0">Documentation</h2>
                <p class="text-muted mb-0">Manage your clinical files and records</p>
            </div>
        </div>

        <?php echo $message; ?>
        
        <div class="custom-card animate__animated animate__fadeInUp">
            <div class="card-title">
                <i class="fa-solid fa-file-medical"></i> 
                <span><?php echo htmlspecialchars($doc['title'] ?? 'Clinical Guidance'); ?></span>
            </div>
            <div class="content-text">
                <?php echo nl2br(htmlspecialchars($doc['content'] ?? 'Important clinical instructions will appear here once updated by the administrator.')); ?>
            </div>
        </div>

        <div class="custom-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
            <div class="card-title">
                <i class="fa-solid fa-cloud-arrow-up"></i> 
                <span>Upload Here</span>
            </div>
            <p class="text-muted small mb-4">Please upload medical reports, prescriptions, or identity documents for clinic review.</p>
            
            <form action="" method="post" enctype="multipart/form-data">
                <div class="upload-zone">
                    <div class="mb-3">
                        <i class="fa-solid fa-file-circle-plus fa-3x mb-3" style="color: var(--border-color);"></i>
                        <input type="file" name="file_to_upload" class="form-control" required>
                    </div>
                    <p class="small text-secondary">Supported formats: PDF, JPG, PNG (Max 5MB)</p>
                </div>
                <div class="text-end">
                    <button class="btn btn-primary btn-upload" type="submit" name="upload_doc">
                        <i class="fa-solid fa-paper-plane me-2"></i> Confirm Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Sidebar Toggle Logic (Handles Laptop vs Mobile correctly)
        const toggleBtn = document.getElementById('toggle-btn');
        const body = document.body;

        if(toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                if (window.innerWidth > 768) {
                    body.classList.toggle('sidebar-off'); // Laptop view hide
                } else {
                    body.classList.toggle('mobile-sidebar-on'); // Mobile view show
                }
            });
        }
    </script>
</body>
</html>