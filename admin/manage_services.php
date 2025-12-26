<?php
include 'auth_check.php'; // Admin authentication

// FIX 1: Ensure this path matches where we created the file earlier
include '../config/db.php'; 

// Check if admin is logged in (Uncomment when you have login system)
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: login.php");
//     exit();
// }

$message = "";

// FIX 2: Define physical path (for PHP to move file) vs DB path (for website to display)
$uploadDir = "../images/"; // Go up one folder to find 'images'

// --- HANDLE INSERT & UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // DELETE SERVICE
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        
        // Get image path to delete file
        $sqlGet = "SELECT image FROM services WHERE id = ?";
        $stmt = $conn->prepare($sqlGet);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            // FIX 3: Add "../" to finding the file for deletion
            $fileToDelete = "../" . $row['image']; 
            if (!empty($row['image']) && file_exists($fileToDelete)) {
                unlink($fileToDelete); // Delete file from server
            }
        }

        $sql = "DELETE FROM services WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Service deleted successfully!";
        } else {
            $message = "Error deleting service.";
        }
    }
    // ADD OR EDIT SERVICE
    else {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $icon = $_POST['icon'];
        $tab_id = "services-" . time(); 
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        
        // Image Upload Logic
        $imagePath = "";
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $fileName = basename($_FILES['image']['name']);
            // Create a unique name
            $uniqueName = time() . "_" . $fileName;
            
            // FIX 4: Separate the "Target Path" (where to save) from "DB Path" (what to save in DB)
            $targetFilePath = $uploadDir . $uniqueName; // ../images/123_pic.jpg
            $dbFilePath = "images/" . $uniqueName;      // images/123_pic.jpg
            
            // Move file to ../images/
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
                $imagePath = $dbFilePath; // Save "images/..." to database
            } else {
                $message = "Error uploading image. Check folder permissions.";
            }
        }

        if ($service_id > 0) {
            // Update Existing
            if ($imagePath != "") {
                $sql = "UPDATE services SET title=?, description=?, icon=?, image=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $title, $description, $icon, $imagePath, $service_id);
            } else {
                $sql = "UPDATE services SET title=?, description=?, icon=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $title, $description, $icon, $service_id);
            }
            $action = "updated";
        } else {
            // Insert New
            // Note: If image upload failed, we might be inserting an empty image path here. 
            // You can add a check if needed, but for now we allow it.
            $sql = "INSERT INTO services (tab_id, title, description, icon, image) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $tab_id, $title, $description, $icon, $imagePath);
            $action = "added";
        }

        if ($stmt->execute()) {
            $message = "Service $action successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

// --- FETCH ALL SERVICES ---
$services = $conn->query("SELECT * FROM services ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Services</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Manage Services (We Can Help You Section)</h4>
            <button class="btn btn-primary" data-toggle="modal" data-target="#serviceModal" onclick="resetForm()">
                <i class="fa fa-plus"></i> Add New Service
            </button>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-info"><?= $message; ?></div>
            <?php endif; ?>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Icon</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $services->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if($row['image']): ?>
                                <img src="../<?= $row['image']; ?>" style="width: 80px; height: 60px; object-fit: cover;">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><i class="fa <?= $row['icon']; ?> fa-2x text-warning"></i><br><small><?= $row['icon']; ?></small></td>
                        <td><?= htmlspecialchars($row['title']); ?></td>
                        <td><?= htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" 
                                onclick="editService(<?= $row['id']; ?>, '<?= addslashes($row['title']); ?>', '<?= addslashes($row['description']); ?>', '<?= $row['icon']; ?>')">
                                Edit
                            </button>
                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="serviceModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST" enctype="multipart/form-data">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Add Service</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="service_id" id="service_id">
            
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label>Icon Class (FontAwesome)</label>
                <input type="text" name="icon" id="icon" class="form-control" placeholder="e.g., fa-heart, fa-users, fa-home" value="fa-heart">
                <small class="text-muted">Use FontAwesome classes like <code>fa-heart</code>, <code>fa-briefcase</code>, <code>fa-user</code>.</small>
            </div>

            <div class="form-group">
                <label>Service Image</label>
                <input type="file" name="image" class="form-control-file">
                <small class="text-muted">Leave empty to keep existing image when editing.</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function editService(id, title, desc, icon) {
    document.getElementById('service_id').value = id;
    document.getElementById('title').value = title;
    document.getElementById('description').value = desc;
    document.getElementById('icon').value = icon;
    document.getElementById('modalTitle').innerText = "Edit Service";
    $('#serviceModal').modal('show');
}

function resetForm() {
    document.getElementById('service_id').value = "";
    document.getElementById('title').value = "";
    document.getElementById('description').value = "";
    document.getElementById('icon').value = "fa-heart";
    document.getElementById('modalTitle').innerText = "Add Service";
}
</script>

</body>
</html>