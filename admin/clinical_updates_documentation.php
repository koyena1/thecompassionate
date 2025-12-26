<?php
include 'auth_check.php'; // Admin authentication
// session_start();
include '../config/db.php'; 

$success_message = "";
$error_message = "";

// Handle Documentation Update (Admin Instructions)
if (isset($_POST['update_doc'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $patient_id = isset($_POST['patient_id']) && $_POST['patient_id'] !== '' ? intval($_POST['patient_id']) : 'NULL';
    
    // Check if instruction already exists for this patient
    if ($patient_id === 'NULL') {
        // Update global instruction
        $check = mysqli_query($conn, "SELECT id FROM documentation WHERE patient_id IS NULL LIMIT 1");
    } else {
        // Check for patient-specific instruction
        $check = mysqli_query($conn, "SELECT id FROM documentation WHERE patient_id = $patient_id LIMIT 1");
    }
    
    if (mysqli_num_rows($check) > 0) {
        // Update existing instruction
        $row = mysqli_fetch_assoc($check);
        $doc_id = $row['id'];
        if ($patient_id === 'NULL') {
            mysqli_query($conn, "UPDATE documentation SET title='$title', content='$content' WHERE id=$doc_id");
        } else {
            mysqli_query($conn, "UPDATE documentation SET title='$title', content='$content' WHERE id=$doc_id AND patient_id=$patient_id");
        }
        $success_message = "Instruction updated successfully!";
    } else {
        // Insert new patient-specific instruction
        if ($patient_id === 'NULL') {
            mysqli_query($conn, "INSERT INTO documentation (title, content, patient_id) VALUES ('$title', '$content', NULL)");
        } else {
            mysqli_query($conn, "INSERT INTO documentation (title, content, patient_id) VALUES ('$title', '$content', $patient_id)");
        }
        $success_message = "New instruction created successfully!";
    }
}

// Fetch all patients for dropdown
$patients_query = "SELECT patient_id, full_name FROM patients ORDER BY full_name ASC";
$patients_res = mysqli_query($conn, $patients_query);

// Fetch Admin Content (global or specific based on selection)
$selected_patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;
if ($selected_patient_id) {
    $res = mysqli_query($conn, "SELECT * FROM documentation WHERE patient_id = $selected_patient_id LIMIT 1");
    if (mysqli_num_rows($res) == 0) {
        // If no specific instruction exists, create empty template
        $doc = ['title' => '', 'content' => '', 'patient_id' => $selected_patient_id];
    } else {
        $doc = mysqli_fetch_assoc($res);
    }
} else {
    // Fetch global instruction
    $res = mysqli_query($conn, "SELECT * FROM documentation WHERE patient_id IS NULL LIMIT 1");
    $doc = mysqli_fetch_assoc($res) ?: ['title' => '', 'content' => '', 'patient_id' => null];
}

// Fetch All Patient-Specific Instructions with Patient Names
$instructions_query = "SELECT d.*, p.full_name 
                      FROM documentation d 
                      LEFT JOIN patients p ON d.patient_id = p.patient_id 
                      WHERE d.patient_id IS NOT NULL
                      ORDER BY d.updated_at DESC";
$instructions_res = mysqli_query($conn, $instructions_query);

// Fetch All Patient Uploads with Patient Names
$uploads_query = "SELECT u.*, p.full_name FROM patient_uploads u 
                  JOIN patients p ON u.patient_id = p.patient_id 
                  ORDER BY u.uploaded_at DESC";
$uploads_res = mysqli_query($conn, $uploads_query);

$admin_display_name = "Dr.Usri Sengupta"; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Updates & Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #3498db;
            --bg-light: #f5f7fb;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            color: #333;
            transition: background-color 0.3s, color 0.3s;
            min-height: 100vh;
        }

        /* --- DARK MODE STYLES --- */
        body.dark-mode {
            background-color: #121212;
            color: #e0e0e0;
        }
        body.dark-mode .navbar, 
        body.dark-mode .card {
            background-color: #1e1e1e;
            border-color: #333;
            color: #e0e0e0;
        }
        body.dark-mode .table {
            color: #e0e0e0;
            border-color: #444;
        }
        body.dark-mode .table-light {
            background-color: #2a2a2a;
            color: #fff;
        }
        body.dark-mode .text-muted {
            color: #bbb !important;
        }

        .navbar {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .btn-primary {
            background-color: var(--accent-color);
            border: none;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .admin-name { display: none; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg px-4 py-3">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fw-bold text-primary">
                <i class="fas fa-heartbeat text-danger me-2"></i>The Compassionate Space
            </h4>
            
            <div class="d-flex align-items-center gap-3">
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm px-3">
                    <i class="fas fa-arrow-left me-2"></i>Dashboard
                </a>
                
                <button onclick="toggleTheme()" class="btn btn-light btn-sm shadow-sm" title="Toggle Theme">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>

                <div class="d-flex align-items-center gap-2 border-start ps-3">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_display_name); ?>&background=random" class="rounded-circle" width="35" height="35">
                    <span class="fw-semibold admin-name small"><?php echo $admin_display_name; ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="fw-bold"><i class="fas fa-file-medical text-primary me-2"></i>Clinical Updates & Documentation</h3>
                <p class="text-muted">Manage global instructions and view patient-uploaded records.</p>
            </div>
        </div>

        <?php if($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header bg-dark text-white fw-bold py-3">
                        <i class="fas fa-edit me-2"></i>Edit Instructions
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" id="instructionForm">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Select Patient (Leave blank for Global)</label>
                                <select name="patient_id" class="form-select" id="patientSelect">
                                    <option value="">🌍 Global Instruction (All Patients)</option>
                                    <?php 
                                    mysqli_data_seek($patients_res, 0); // Reset pointer
                                    while($patient = mysqli_fetch_assoc($patients_res)): 
                                    ?>
                                        <option value="<?php echo $patient['patient_id']; ?>" 
                                            <?php echo (isset($doc['patient_id']) && $doc['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                                            👤 <?php echo htmlspecialchars($patient['full_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Instruction Title</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($doc['title'] ?? ''); ?>" placeholder="e.g., Blood Test Results" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold">Content / Patient Instructions</label>
                                <textarea name="content" class="form-control" rows="10" placeholder="Enter instructions for patients here..." required><?php echo htmlspecialchars($doc['content'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="update_doc" class="btn btn-primary w-100 py-2 fw-bold">
                                <i class="fas fa-save me-2"></i>Save Instruction
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <!-- Patient-Specific Instructions -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white fw-bold py-3">
                        <i class="fas fa-user-md me-2"></i>Patient-Specific Instructions
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Patient Name</th>
                                        <th>Instruction Title</th>
                                        <th>Last Updated</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($instructions_res) > 0): ?>
                                        <?php while($instr = mysqli_fetch_assoc($instructions_res)): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold"><?php echo htmlspecialchars($instr['full_name']); ?></td>
                                            <td class="text-primary fw-semibold"><?php echo htmlspecialchars($instr['title']); ?></td>
                                            <td class="text-muted small"><?php echo date('M d, Y | h:i A', strtotime($instr['updated_at'])); ?></td>
                                            <td class="text-center pe-4">
                                                <a href="?patient_id=<?php echo $instr['patient_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary px-3"
                                                   title="Edit this instruction">
                                                   <i class="fas fa-edit me-1"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <i class="fas fa-info-circle me-2"></i>No patient-specific instructions created yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Received Patient Documents -->
                <div class="card">
                    <div class="card-header bg-primary text-white fw-bold py-3">
                        <i class="fas fa-folder-open me-2"></i>Received Patient Documents
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Patient Name</th>
                                        <th>File Name</th>
                                        <th>Uploaded Date</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($uploads_res) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($uploads_res)): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                            <td class="small text-truncate" style="max-width: 200px;">
                                                <?php 
                                                    // Display instruction title if available, otherwise display filename
                                                    if (!empty($row['instruction_title'])) {
                                                        echo '<strong>' . htmlspecialchars($row['instruction_title']) . '</strong><br>';
                                                        echo '<small class="text-muted">(' . htmlspecialchars($row['file_name']) . ')</small>';
                                                    } else {
                                                        echo htmlspecialchars($row['file_name']);
                                                    }
                                                ?>
                                            </td>
                                            <td class="text-muted small"><?php echo date('M d, Y | h:i A', strtotime($row['uploaded_at'])); ?></td>
                                            <td class="text-center pe-4">
                                                <a href="../patient/<?php echo $row['file_path']; ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-outline-info px-3">
                                                   <i class="fas fa-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                No documents have been uploaded by patients yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Toggling Logic
        function toggleTheme() {
            const body = document.body;
            const icon = document.getElementById('themeIcon');
            body.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                icon.classList.replace('fa-moon', 'fa-sun');
                localStorage.setItem('theme', 'dark');
            } else {
                icon.classList.replace('fa-sun', 'fa-moon');
                localStorage.setItem('theme', 'light');
            }
        }

        // Apply saved theme on load
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
            document.getElementById('themeIcon').classList.replace('fa-moon', 'fa-sun');
        }
    </script>
</body>
</html>