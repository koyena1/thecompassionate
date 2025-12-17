<?php
// FILE: admin/manage_healthtimeline.php
include '../config/db.php';
session_start();

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // header("Location: ../login.php"); 
    // exit();
}

// 1. Get Patient ID
if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    echo "<script>
            alert('Please select a patient from the list.');
            window.location.href = 'patient list.php'; 
          </script>";
    exit();
}
$patient_id = intval($_GET['patient_id']);

// --- NEW: HANDLE DELETE ACTION ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $del_stmt = $conn->prepare("DELETE FROM medical_records WHERE record_id = ? AND patient_id = ?");
    $del_stmt->bind_param("ii", $delete_id, $patient_id);
    if ($del_stmt->execute()) {
        header("Location: manage_healthtimeline.php?patient_id=$patient_id&msg=deleted");
        exit();
    }
}

// 2. Handle Form Submission (Add New Update)
$msg = "";
// Check for redirect message
if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Record deleted successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_update'])) {
    $title = $_POST['title']; 
    $note = $_POST['note'];   
    
    $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, appointment_id, diagnosis, internal_doctor_notes) VALUES (?, NULL, ?, ?)");
    $stmt->bind_param("iss", $patient_id, $title, $note);
    
    if ($stmt->execute()) {
        $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='fas fa-check-circle me-2'></i> Timeline updated successfully!
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <i class='fas fa-exclamation-circle me-2'></i> Error: " . $conn->error . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
}

// 3. Fetch Patient Details
$p_sql = "SELECT full_name, email FROM patients WHERE patient_id = $patient_id";
$p_res = $conn->query($p_sql);
$patient = $p_res->fetch_assoc();

// 4. Fetch Existing Timeline History
$hist_sql = "SELECT * FROM medical_records WHERE patient_id = $patient_id ORDER BY created_at DESC";
$history = $conn->query($hist_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Timeline | <?php echo htmlspecialchars($patient['full_name']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #7f8c8d;
            --bg-light: #f4f6f9;
            --card-shadow: 0 10px 20px rgba(0,0,0,0.05);
            --timeline-color: #e9ecef;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: #333;
        }

        .header-section {
            background: #fff;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 30px;
        }

        .custom-card {
            background: #fff;
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            padding: 20px 25px;
            border-bottom: none;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            background-color: #fcfcfc;
        }

        .btn-primary-custom {
            background-color: var(--primary);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .timeline-container {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-line {
            position: absolute;
            left: 10px;
            top: 15px;
            bottom: 0;
            width: 2px;
            background: var(--timeline-color);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 35px;
        }

        .timeline-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid var(--primary);
            position: absolute;
            left: -26px;
            top: 5px;
            z-index: 2;
        }
        
        .timeline-content {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            border-left: 4px solid var(--primary);
        }

        .timeline-date {
            font-size: 0.85rem;
            color: var(--secondary);
        }

        .timeline-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .patient-badge {
            background: #e1f0fa;
            color: var(--primary-dark);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        /* NEW: Action Dropdown Styles */
        .action-btn {
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
        }
        .action-btn:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>

    <div class="header-section">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <a href="patientinfooverview.php?id=<?php echo $patient_id; ?>" class="btn btn-outline-secondary me-3 border-0 bg-light rounded-circle" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="mb-0 fw-bold">Manage Timeline</h4>
                    <p class="mb-0 text-muted small">Update records for <span class="patient-badge ms-1"><?php echo htmlspecialchars($patient['full_name']); ?></span></p>
                </div>
            </div>
            <div class="text-end d-none d-md-block">
                <small class="text-muted d-block">Patient ID</small>
                <strong>#<?php echo str_pad($patient_id, 4, '0', STR_PAD_LEFT); ?></strong>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4">
            
            <div class="col-lg-5">
                <div class="custom-card sticky-top" style="top: 20px; z-index: 1;">
                    <div class="card-header-custom">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Add New Record</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php echo $msg; ?>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Title / Diagnosis</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-stethoscope"></i></span>
                                    <input type="text" name="title" class="form-control border-start-0 ps-0" placeholder="e.g. Viral Fever, Routine Checkup" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Doctor's Note</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted align-items-start pt-3"><i class="fas fa-align-left"></i></span>
                                    <textarea name="note" class="form-control border-start-0 ps-0" rows="5" placeholder="Enter detailed health observation, medication advice, or next steps..." required></textarea>
                                </div>
                            </div>

                            <button type="submit" name="add_update" class="btn btn-primary-custom w-100 text-white">
                                <i class="fas fa-paper-plane me-2"></i> Post Update
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <h5 class="mb-4 fw-bold text-secondary ps-2">Patient History</h5>
                
                <div class="timeline-container">
                    <div class="timeline-line"></div>
                    
                    <?php if($history->num_rows > 0): ?>
                        <?php while($row = $history->fetch_assoc()): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="timeline-title mb-0 d-block"><?php echo htmlspecialchars($row['diagnosis']); ?></span>
                                            <span class="timeline-date">
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?> 
                                                <small class="ms-1" style="text-transform:none; opacity:0.7;"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                                            </span>
                                        </div>
                                        
                                        <div class="dropdown">
                                            <i class="fas fa-ellipsis-v action-btn p-2" data-bs-toggle="dropdown"></i>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                <li><a class="dropdown-item small" href="edit_record.php?id=<?php echo $row['record_id']; ?>"><i class="fas fa-edit me-2 text-primary"></i> Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item small text-danger" href="javascript:void(0)" onclick="confirmDelete(<?php echo $row['record_id']; ?>, <?php echo $patient_id; ?>)"><i class="fas fa-trash-alt me-2"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <p class="timeline-note mb-0">
                                        <?php echo nl2br(htmlspecialchars($row['internal_doctor_notes'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486831.png" width="100" alt="No data" style="opacity: 0.5;">
                            <p class="text-muted mt-3">No history records found for this patient.</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(recordId, patientId) {
            if(confirm("Are you sure you want to delete this record? This action cannot be undone.")) {
                window.location.href = `manage_healthtimeline.php?patient_id=${patientId}&delete_id=${recordId}`;
            }
        }
    </script>
</body>
</html>