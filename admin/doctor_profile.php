<?php
// Connect to database
include('../config/db.php'); 

$admin_id = 1; // Primary doctor ID

// Update Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['full_name'];
    $spec = $_POST['specialization'];
    $hours = $_POST['clinic_hours'];
    $about = $_POST['about_doctor'];
    
    // Handle Image Upload
    $image_path = "";
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
        $new_file_name = "doc_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_file_name;
        
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $image_path = "uploads/" . $new_file_name;
            // Update image in DB
            $img_query = "UPDATE admin_users SET profile_image = ? WHERE admin_id = ?";
            $img_stmt = $conn->prepare($img_query);
            $img_stmt->bind_param("si", $image_path, $admin_id);
            $img_stmt->execute();
        }
    }

    // Update Text Data
    $update_query = "UPDATE admin_users SET full_name = ?, specialization = ?, clinic_hours = ?, about_doctor = ? WHERE admin_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $name, $spec, $hours, $about, $admin_id);
    $stmt->execute();
    
    $success = "Profile updated successfully!";
}

// Fetch current data
$query = "SELECT * FROM admin_users WHERE admin_id = $admin_id";
$result = $conn->query($query);
$doctor = $result->fetch_assoc();
?>

<div class="main-content" style="padding: 40px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #2D3436;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="margin: 0;">Manage Clinical Profile</h2>
            <p style="color: #636e72; margin-top: 5px;">Update the information patients see on their dashboard.</p>
        </div>
        <a href="dashboard.php" style="text-decoration: none; background: #7B3F00; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if(isset($success)) echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>$success</div>"; ?>

    <form method="POST" enctype="multipart/form-data" style="max-width: 700px; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        
        <div style="display: flex; gap: 30px; margin-bottom: 25px; align-items: center;">
            <div style="text-align: center;">
                <img src="../<?php echo !empty($doctor['profile_image']) ? $doctor['profile_image'] : 'assets/img/default-doc.png'; ?>" 
                     style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #7B3F00; margin-bottom: 10px;">
                <br>
                <label style="font-size: 12px; font-weight: bold; cursor: pointer; color: #1FB6FF;">
                    Change Photo
                    <input type="file" name="profile_image" style="display: none;">
                </label>
            </div>

            <div style="flex-grow: 1;">
                <div style="margin-bottom: 15px;">
                    <label style="font-weight: 600; font-size: 14px;">Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($doctor['full_name']); ?>" style="width:100%; padding:10px; border: 1px solid #ddd; border-radius: 8px; margin-top: 5px;">
                </div>
                <div>
                    <label style="font-weight: 600; font-size: 14px;">Specialization</label>
                    <input type="text" name="specialization" value="<?php echo htmlspecialchars($doctor['specialization']); ?>" style="width:100%; padding:10px; border: 1px solid #ddd; border-radius: 8px; margin-top: 5px;">
                </div>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="font-weight: 600; font-size: 14px;">Clinic Hours</label>
            <input type="text" name="clinic_hours" value="<?php echo htmlspecialchars($doctor['clinic_hours']); ?>" placeholder="e.g. Mon-Fri, 9AM - 5PM" style="width:100%; padding:10px; border: 1px solid #ddd; border-radius: 8px; margin-top: 5px;">
        </div>

        <div style="margin-bottom: 25px;">
            <label style="font-weight: 600; font-size: 14px;">About the Doctor (Biography)</label>
            <textarea name="about_doctor" rows="5" style="width:100%; padding:10px; border: 1px solid #ddd; border-radius: 8px; margin-top: 5px; resize: vertical;"><?php echo htmlspecialchars($doctor['about_doctor']); ?></textarea>
        </div>

        <button type="submit" style="background: #1FB6FF; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 700; width: 100%; transition: background 0.3s;">
            Save Professional Profile
        </button>
    </form>
</div>