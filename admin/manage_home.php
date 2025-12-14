<?php
session_start();

// --- DATABASE CONNECTION ---
require_once '../config/db.php'; 

$msg = "";
$edit_blog_data = null;
$edit_quote_data = null; // New variable for editing quotes
$upload_dir_system = "../images/"; 
$upload_dir_db = "images/";        

// --- PHP LOGIC ---

// 1. SERVICES LOGIC
if (isset($_POST['init_services'])) {
    $sql = "INSERT INTO services (tab_id, title, description, image) VALUES 
    ('services-1', 'Service 1 Title', 'Write description here...', 'images/rl.jpg'),
    ('services-2', 'Service 2 Title', 'Write description here...', 'images/cpl.jpg'),
    ('services-3', 'Service 3 Title', 'Write description here...', 'images/dp.jpg'),
    ('services-4', 'Service 4 Title', 'Write description here...', 'images/c.jpg'),
    ('services-5', 'Service 5 Title', 'Write description here...', 'images/b.jpg'),
    ('services-6', 'Service 6 Title', 'Write description here...', 'images/bs.jpg')";
    if ($conn->query($sql)) { $msg = "Service slots created!"; } else { $msg = "Error: " . $conn->error; }
}

if (isset($_POST['update_service'])) {
    $id = $_POST['service_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $desc = $conn->real_escape_string($_POST['description']);
    $conn->query("UPDATE services SET title='$title', description='$desc' WHERE id='$id'");
    $msg = "Service updated successfully!";
}

// 2. BLOG LOGIC
if (isset($_POST['add_blog'])) {
    $title = $conn->real_escape_string($_POST['blog_title']);
    $content = $conn->real_escape_string($_POST['blog_content']);
    $db_image_path = "images/default.jpg"; 
    $uploadOk = true;

    if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] == 0) {
        $filename = time() . "_" . basename($_FILES["blog_image"]["name"]); 
        $target_file_system = $upload_dir_system . $filename; 
        $target_file_db     = $upload_dir_db . $filename;      
        if (move_uploaded_file($_FILES["blog_image"]["tmp_name"], $target_file_system)) {
            $db_image_path = $target_file_db;
        } else { $uploadOk = false; $msg = "Upload Failed"; }
    }

    if ($uploadOk) {
        $sql = "INSERT INTO blogs (title, content, image) VALUES ('$title', '$content', '$db_image_path')";
        if ($conn->query($sql)) { $msg = "Blog added successfully!"; } else { $msg = "DB Error: " . $conn->error; }
    }
}

if (isset($_POST['update_blog'])) {
    $id = $_POST['blog_id'];
    $title = $conn->real_escape_string($_POST['blog_title']);
    $content = $conn->real_escape_string($_POST['blog_content']);
    $sql = "UPDATE blogs SET title='$title', content='$content' WHERE id='$id'"; 
    
    if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] == 0) {
        $filename = time() . "_" . basename($_FILES["blog_image"]["name"]);
        $target_file_system = $upload_dir_system . $filename;
        $target_file_db     = $upload_dir_db . $filename;
        if (move_uploaded_file($_FILES["blog_image"]["tmp_name"], $target_file_system)) {
            $sql = "UPDATE blogs SET title='$title', content='$content', image='$target_file_db' WHERE id='$id'";
        }
    }
    if ($conn->query($sql)) { $msg = "Blog updated successfully!"; } else { $msg = "DB Error"; }
}

if (isset($_GET['delete_blog'])) {
    $id = $_GET['delete_blog'];
    $conn->query("DELETE FROM blogs WHERE id='$id'");
    header("Location: manage_home.php"); exit();
}

if (isset($_GET['edit_blog'])) {
    $id = $_GET['edit_blog'];
    $result = $conn->query("SELECT * FROM blogs WHERE id='$id'");
    $edit_blog_data = $result->fetch_assoc();
}

// 3. MOTIVATIONAL QUOTES LOGIC (NEW)
if (isset($_POST['add_quote'])) {
    $quote = $conn->real_escape_string($_POST['quote_content']);
    if(!empty($quote)){
        $conn->query("INSERT INTO motivational_quotes (quote_content) VALUES ('$quote')");
        $msg = "Quote added successfully!";
    }
}

if (isset($_POST['update_quote'])) {
    $id = $_POST['quote_id'];
    $quote = $conn->real_escape_string($_POST['quote_content']);
    $conn->query("UPDATE motivational_quotes SET quote_content='$quote' WHERE id='$id'");
    $msg = "Quote updated successfully!";
}

if (isset($_GET['delete_quote'])) {
    $id = $_GET['delete_quote'];
    $conn->query("DELETE FROM motivational_quotes WHERE id='$id'");
    header("Location: manage_home.php"); exit();
}

if (isset($_GET['edit_quote'])) {
    $id = $_GET['edit_quote'];
    $result = $conn->query("SELECT * FROM motivational_quotes WHERE id='$id'");
    $edit_quote_data = $result->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Home Content</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <style> 
        /* --- GLOBAL STYLES --- */
        body {
            /* DEEP MIDNIGHT OCEAN GRADIENT (Blue/Black) */
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            color: #fff;
            padding: 40px 0;
        }

        h2, h4, h5 {
            font-weight: 700;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        /* --- DARK GLASS CARD --- */
        .glass-card {
            background: rgba(255, 255, 255, 0.08); 
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-top: 1px solid rgba(255, 255, 255, 0.2); 
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .glass-header {
            background: rgba(0, 0, 0, 0.2); 
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 20px 25px;
        }

        .glass-body {
            padding: 30px;
        }

        /* --- GLOSSY INPUTS --- */
        .form-control, .form-control-file {
            background: rgba(0, 0, 0, 0.25) !important; 
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            background: rgba(0, 0, 0, 0.4) !important;
            box-shadow: 0 0 15px rgba(255, 165, 0, 0.3); 
            border-color: rgba(255, 165, 0, 0.5) !important;
        }

        label {
            color: #ffcc80; /* Soft Gold */
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        /* --- GLOSSY BUTTONS --- */
        .btn-glossy-gold {
            background: linear-gradient(to right, #FF8008, #FFC837);
            border: none;
            color: #000; 
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 128, 8, 0.4); 
        }

        .btn-glossy-cyan {
            background: linear-gradient(to right, #1CB5E0, #000851);
            border: none;
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(28, 181, 224, 0.4);
        }

        .btn-glossy-red {
            background: linear-gradient(to right, #cb2d3e, #ef473a);
            border: none;
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(203, 45, 62, 0.4);
        }

        .btn:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(255,255,255,0.2);
            filter: brightness(1.1);
        }

        /* --- TABLE STYLING --- */
        .table { color: #e0e0e0; }
        .table thead th {
            border-top: none;
            border-bottom: 2px solid rgba(255, 165, 0, 0.5); 
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
            color: #ffcc80; 
        }
        .table td {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(255, 165, 0, 0.05); 
        }

        .img-preview {
            width: 60px; height: 60px; object-fit: cover; 
            border-radius: 10px; border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .service-row {
            background: rgba(0,0,0,0.2);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s;
        }
        .service-row:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255, 200, 55, 0.3); 
        }
    </style>
</head>
<body>
<div class="container">
    <div class="text-center mb-5">
        <h2 class="display-4 font-weight-bold text-white">Dashboard</h2>
        <p class="lead" style="color: #ffcc80;">Manage your content in style</p>
    </div>
    
    <?php if($msg): ?>
        <div class="alert alert-light alert-dismissible fade show shadow-lg" style="background: rgba(255, 200, 55, 0.9); border:none; color: #333; font-weight: 600;">
            <i class="fas fa-check-circle mr-2"></i> <?php echo $msg; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="glass-card">
        <div class="glass-header text-white">
            <h4 class="mb-0"><i class="fas fa-magic mr-2" style="color: #ffcc80;"></i> Edit Services</h4>
        </div>
        <div class="glass-body">
            
            <?php
            $check = $conn->query("SELECT COUNT(*) as count FROM services");
            $row_count = $check->fetch_assoc()['count'];

            if ($row_count == 0): 
            ?>
                <div class="text-center py-5">
                    <h5 class="mb-3 text-white-50">No services found yet.</h5>
                    <form method="POST">
                        <button type="submit" name="init_services" class="btn btn-glossy-gold btn-lg px-5 rounded-pill">
                            <i class="fas fa-bolt mr-2"></i> Initialize Slots
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="d-none d-md-flex row mb-2 font-weight-bold text-uppercase text-center" style="color: #ffcc80;">
                    <div class="col-md-3">Service Title</div>
                    <div class="col-md-7">Description</div>
                    <div class="col-md-2">Action</div>
                </div>

                <?php
                $services = $conn->query("SELECT * FROM services");
                while($row = $services->fetch_assoc()):
                ?>
                <form method="POST" class="service-row">
                    <input type="hidden" name="service_id" value="<?php echo $row['id']; ?>">
                    <div class="row align-items-center">
                        <div class="col-md-3 mb-2 mb-md-0">
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($row['title']); ?>" required placeholder="Title">
                        </div>
                        <div class="col-md-7 mb-2 mb-md-0">
                            <textarea name="description" class="form-control" rows="2" required placeholder="Description"><?php echo htmlspecialchars($row['description']); ?></textarea>
                        </div>
                        <div class="col-md-2 text-center">
                            <button type="submit" name="update_service" class="btn btn-glossy-gold btn-sm btn-block py-2 rounded-pill">
                                <i class="fas fa-save mr-1"></i> Save
                            </button>
                        </div>
                    </div>
                </form>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="glass-card">
        <div class="glass-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-newspaper mr-2" style="color: #ffcc80;"></i> Manage Blogs</h4>
            <?php if($edit_blog_data): ?>
                <a href="manage_home.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fas fa-times mr-1"></i> Cancel
                </a>
            <?php endif; ?>
        </div>
        <div class="glass-body">
            
            <div style="background: rgba(255,255,255,0.03); padding: 25px; border-radius: 20px; margin-bottom: 40px; border: 1px solid rgba(255,255,255,0.1);">
                <h5 class="mb-4 border-bottom border-secondary pb-2" style="display:inline-block; color: #fff;">
                    <?php echo $edit_blog_data ? 'Edit Blog Post' : 'Create New Post'; ?>
                </h5>
                <form method="POST" enctype="multipart/form-data">
                    <?php if($edit_blog_data): ?>
                        <input type="hidden" name="blog_id" value="<?php echo $edit_blog_data['id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Blog Title</label>
                            <input type="text" name="blog_title" class="form-control" placeholder="Enter title..." 
                                   value="<?php echo $edit_blog_data ? htmlspecialchars($edit_blog_data['title']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Cover Image</label>
                            <div class="custom-file">
                                <input type="file" name="blog_image" class="form-control-file" style="padding: 5px;" <?php echo $edit_blog_data ? '' : 'required'; ?>>
                            </div>
                            <?php if($edit_blog_data): ?>
                                <small class="text-white mt-1 d-block"><i class="fas fa-image"></i> Current: <?php echo basename($edit_blog_data['image']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Content</label>
                            <textarea name="blog_content" class="form-control" rows="3" placeholder="Write something amazing..." required><?php echo $edit_blog_data ? htmlspecialchars($edit_blog_data['content']) : ''; ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <?php if($edit_blog_data): ?>
                                <button type="submit" name="update_blog" class="btn btn-glossy-gold px-4 rounded-pill">
                                    <i class="fas fa-sync-alt mr-2"></i> Update Blog
                                </button>
                            <?php else: ?>
                                <button type="submit" name="add_blog" class="btn btn-glossy-cyan px-4 rounded-pill">
                                    <i class="fas fa-plus mr-2"></i> Publish Blog
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <h5 class="mb-3 text-white"><i class="fas fa-list-ul mr-2"></i> Your Posts</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="100">Image</th>
                            <th>Title</th>
                            <th>Preview</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $blogs = $conn->query("SELECT * FROM blogs ORDER BY id DESC");
                        if($blogs->num_rows > 0):
                            while($b = $blogs->fetch_assoc()):
                                $display_img = "../" . $b['image'];
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo $display_img; ?>" class="img-preview" alt="Blog Img">
                            </td>
                            <td class="font-weight-bold" style="font-size: 1.1rem; color: #fff;"><?php echo htmlspecialchars($b['title']); ?></td>
                            <td style="color: #bbb;"><?php echo substr(htmlspecialchars($b['content']), 0, 50); ?>...</td>
                            <td class="text-right">
                                <a href="manage_home.php?edit_blog=<?php echo $b['id']; ?>" class="btn btn-sm btn-glossy-gold shadow-sm mr-1" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="manage_home.php?delete_blog=<?php echo $b['id']; ?>" class="btn btn-sm btn-glossy-red shadow-sm" onclick="return confirm('Are you sure you want to delete this blog?');" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                            echo "<tr><td colspan='4' class='text-center py-4 text-white-50'>No posts found.</td></tr>";
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="glass-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-quote-left mr-2" style="color: #ffcc80;"></i> The Motivation Corner</h4>
            <?php if($edit_quote_data): ?>
                <a href="manage_home.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fas fa-times mr-1"></i> Cancel
                </a>
            <?php endif; ?>
        </div>
        <div class="glass-body">
            
            <div style="background: rgba(255,255,255,0.03); padding: 25px; border-radius: 20px; margin-bottom: 40px; border: 1px solid rgba(255,255,255,0.1);">
                <h5 class="mb-3 border-bottom border-secondary pb-2" style="display:inline-block; color: #fff;">
                    <?php echo $edit_quote_data ? 'Edit Motivation Quote' : 'Add New Quote'; ?>
                </h5>
                <form method="POST">
                    <?php if($edit_quote_data): ?>
                        <input type="hidden" name="quote_id" value="<?php echo $edit_quote_data['id']; ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Quote Content</label>
                        <textarea name="quote_content" class="form-control" rows="3" placeholder="Enter a motivational quote here..." required><?php echo $edit_quote_data ? htmlspecialchars($edit_quote_data['quote_content']) : ''; ?></textarea>
                    </div>
                    <?php if($edit_quote_data): ?>
                        <button type="submit" name="update_quote" class="btn btn-glossy-gold px-4 rounded-pill">
                            <i class="fas fa-sync-alt mr-2"></i> Update Quote
                        </button>
                    <?php else: ?>
                        <button type="submit" name="add_quote" class="btn btn-glossy-cyan px-4 rounded-pill">
                            <i class="fas fa-plus mr-2"></i> Add Quote
                        </button>
                    <?php endif; ?>
                </form>
            </div>

            <h5 class="mb-3 text-white"><i class="fas fa-list-ul mr-2"></i> Active Quotes</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Quote</th>
                            <th class="text-right" width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $quotes = $conn->query("SELECT * FROM motivational_quotes ORDER BY id DESC");
                        if($quotes->num_rows > 0):
                            while($q = $quotes->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="font-italic text-white">"<?php echo htmlspecialchars($q['quote_content']); ?>"</td>
                            <td class="text-right">
                                <a href="manage_home.php?edit_quote=<?php echo $q['id']; ?>" class="btn btn-sm btn-glossy-gold shadow-sm mr-1">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="manage_home.php?delete_quote=<?php echo $q['id']; ?>" class="btn btn-sm btn-glossy-red shadow-sm" onclick="return confirm('Delete this quote?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                            echo "<tr><td colspan='2' class='text-center py-4 text-white-50'>No quotes added yet.</td></tr>";
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
</body>
</html>