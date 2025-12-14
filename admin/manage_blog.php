<?php
session_start();

// --- DATABASE CONNECTION ---
require_once '../config/db.php'; 

// --- DELETE FUNCTIONALITY ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get image path to delete file from folder
    $getImg = $conn->query("SELECT image FROM blogs WHERE id=$id");
    if ($row = $getImg->fetch_assoc()) {
        // We need to add "../" to delete the file because we are inside the admin folder
        $file_to_delete = "../" . $row['image']; 
        if (!empty($row['image']) && file_exists($file_to_delete)) {
            unlink($file_to_delete);
        }
    }
    
    $conn->query("DELETE FROM blogs WHERE id=$id");
    header("Location: manage_blog.php");
    exit();
}

// --- SAVE/UPDATE FUNCTIONALITY ---
$edit_mode = false;
$edit_data = ['id' => '', 'title' => '', 'content' => '', 'image' => ''];

if (isset($_POST['save_blog'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $blog_id = $_POST['blog_id'];
    
    // Default to the old image path
    $db_image_path = $_POST['current_image']; 

    // --- IMAGE UPLOAD LOGIC ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        
        // 1. Where to put the file physically (Relative to manage_blog.php)
        $upload_dir = "../images/"; 
        
        // 2. What to save in the Database (Relative to blog.php)
        $db_dir = "images/";

        $file_name = time() . "_" . basename($_FILES["image"]["name"]);
        
        $physical_file = $upload_dir . $file_name; 
        $db_save_path = $db_dir . $file_name;      

        // Move the file
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $physical_file)) {
            $db_image_path = $db_save_path; // Success: Update the variable to save to DB
        } else {
            echo "<script>alert('Failed to upload. Check folder permissions for ../images/');</script>";
        }
    }

    if (!empty($blog_id)) {
        // Update
        $sql = "UPDATE blogs SET title='$title', content='$content', image='$db_image_path' WHERE id=$blog_id";
    } else {
        // Insert
        $sql = "INSERT INTO blogs (title, content, image, created_at) VALUES ('$title', '$content', '$db_image_path', NOW())";
    }

    if ($conn->query($sql) === TRUE) {
        header("Location: manage_blog.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// --- EDIT DATA FETCH ---
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM blogs WHERE id=$id");
    $edit_data = $res->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Content | Clinical Portal</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* Clinical/Healing Color Palette */
            --bg-gradient-1: #f3f6f4;
            --bg-gradient-2: #e0e9e4;
            --primary-sage: #5D7B6F; /* Soothing Sage */
            --primary-dark: #354f46;
            --accent-teal: #A4C3B2;
            --text-dark: #2C3E50;
            --glass-white: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.6);
        }

        body {
            font-family: 'Lato', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-1) 0%, var(--bg-gradient-2) 100%);
            min-height: 100vh;
            color: var(--text-dark);
            padding-bottom: 50px;
        }

        /* Top Navigation/Header area */
        .top-bar {
            padding: 2rem 0;
            margin-bottom: 1rem;
        }

        h2, h3, h4 {
            font-family: 'Playfair Display', serif; /* Adds the professional/medical authority */
        }

        .header-title {
            color: var(--primary-dark);
            font-weight: 700;
        }

        /* Glassmorphism Cards */
        .glass-panel {
            background: var(--glass-white);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            box-shadow: 0 10px 40px rgba(93, 123, 111, 0.1); /* Soft sage shadow */
            overflow: hidden;
            height: 100%;
            transition: transform 0.3s ease;
        }

        .panel-header {
            background: rgba(93, 123, 111, 0.05); /* Very light sage */
            padding: 20px 25px;
            border-bottom: 1px solid rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .panel-title {
            margin: 0;
            color: var(--primary-sage);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .panel-body {
            padding: 25px;
        }

        /* Form Styling */
        .form-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #7f8c8d;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-control {
            background: #fdfdfd;
            border: 1px solid #e1e8e5;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: var(--primary-sage);
            box-shadow: 0 0 0 4px rgba(93, 123, 111, 0.1);
        }

        /* Buttons */
        .btn-sage {
            background-color: var(--primary-sage);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(93, 123, 111, 0.25);
        }

        .btn-sage:hover {
            background-color: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(93, 123, 111, 0.35);
        }

        .btn-outline-back {
            border: 1px solid var(--primary-sage);
            color: var(--primary-sage);
            border-radius: 50px;
            padding: 8px 25px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s;
            background: transparent;
        }

        .btn-outline-back:hover {
            background: var(--primary-sage);
            color: white;
        }

        /* Table Styling */
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px; /* Spacing between rows */
        }

        .custom-table thead th {
            border: none;
            color: #95a5a6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 0 15px 10px 15px;
        }

        .custom-table tbody tr {
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            transition: transform 0.2s;
        }

        .custom-table tbody tr:hover {
            transform: scale(1.01);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .custom-table td {
            padding: 15px;
            vertical-align: middle;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .custom-table td:first-child { border-left: 1px solid #f0f0f0; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .custom-table td:last-child { border-right: 1px solid #f0f0f0; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .blog-thumb {
            width: 50px;
            height: 50px;
            border-radius: 50%; /* Circular images look more friendly */
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .action-icon {
            color: #b2bec3;
            margin: 0 8px;
            font-size: 1.1rem;
            transition: color 0.2s;
        }

        .action-icon:hover { color: var(--primary-sage); }
        .action-icon.delete:hover { color: #e74c3c; }

        /* File Input Customization */
        input[type="file"]::file-selector-button {
            border: 1px solid #e1e8e5;
            padding: 8px 15px;
            border-radius: 6px;
            background-color: #f8f9fa;
            color: var(--primary-dark);
            margin-right: 15px;
            transition: all 0.2s;
        }
        input[type="file"]::file-selector-button:hover {
            background-color: #e2e6ea;
        }

    </style>
</head>
<body>

<div class="container">
    
    <div class="top-bar d-flex justify-content-between align-items-center">
        <div>
            <h2 class="header-title mb-1">Manage Articles</h2>
            <p class="text-muted mb-0" style="font-size: 0.95rem;">Content management for clinical insights</p>
        </div>
        <a href="dashboard.php" class="btn-outline-back">
            <i class="fas fa-chevron-left me-2"></i>Dashboard
        </a>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="glass-panel">
                <div class="panel-header">
                    <span class="panel-title">
                        <i class="fas <?php echo $edit_mode ? 'fa-pen-nib' : 'fa-plus'; ?> me-2"></i>
                        <?php echo $edit_mode ? 'Edit Article' : 'Compose New'; ?>
                    </span>
                </div>
                <div class="panel-body">
                    <form action="manage_blog.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="blog_id" value="<?php echo $edit_data['id']; ?>">
                        <input type="hidden" name="current_image" value="<?php echo $edit_data['image']; ?>">

                        <div class="mb-4">
                            <label class="form-label">Article Title</label>
                            <input type="text" name="title" class="form-control" placeholder="E.g., Understanding Anxiety..." required value="<?php echo htmlspecialchars($edit_data['title']); ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Article Content</label>
                            <textarea name="content" class="form-control" rows="8" placeholder="Type your content here..." required style="resize: none;"><?php echo htmlspecialchars($edit_data['content']); ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Cover Image</label>
                            <input type="file" name="image" class="form-control" style="padding: 8px;">
                            
                            <?php if(!empty($edit_data['image'])): ?>
                                <div class="mt-3 d-flex align-items-center p-2 rounded" style="background: rgba(93, 123, 111, 0.1);">
                                    <img src="../<?php echo $edit_data['image']; ?>" class="blog-thumb me-3">
                                    <small class="text-muted">Current Image Selected</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" name="save_blog" class="btn btn-sage w-100 mb-3">
                            <?php echo $edit_mode ? 'Save Changes' : 'Publish Article'; ?>
                        </button>
                        
                        <?php if($edit_mode): ?>
                            <a href="manage_blog.php" class="btn btn-light w-100 text-muted" style="border: 1px solid #eee;">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-panel">
                <div class="panel-header">
                    <span class="panel-title"><i class="fas fa-stream me-2"></i>Published Library</span>
                </div>
                <div class="panel-body p-0">
                    <div class="table-responsive p-3">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th width="10%">Image</th>
                                    <th width="70%">Title</th>
                                    <th width="20%" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $conn->query("SELECT * FROM blogs ORDER BY created_at DESC");
                                if ($result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()){
                                        echo "<tr>";
                                        
                                        // Image
                                        $imgSrc = !empty($row['image']) ? "../".$row['image'] : "https://via.placeholder.com/50?text=Doc";
                                        echo "<td><img src='$imgSrc' class='blog-thumb' alt='img'></td>";
                                        
                                        // Title
                                        echo "<td class='fw-bold text-dark'>".$row['title']."</td>";
                                        
                                        // Actions
                                        echo "<td class='text-end'>
                                                <a href='manage_blog.php?edit=".$row['id']."' class='action-icon' title='Edit'>
                                                    <i class='fas fa-pen'></i>
                                                </a>
                                                <a href='manage_blog.php?delete=".$row['id']."' class='action-icon delete' onclick='return confirm(\"Are you sure? This cannot be undone.\")' title='Delete'>
                                                    <i class='fas fa-trash-alt'></i>
                                                </a>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-center py-5 text-muted'>No articles found. Use the form to add your first post.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>