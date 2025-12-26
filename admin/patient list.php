<?php
// FILE: admin/patient_list.php
include 'auth_check.php'; // Admin authentication
include '../config/db.php'; // Adjust path if needed

// Fetch all patients
$sql = "SELECT * FROM patients ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Directory Overview</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- CSS VARIABLES & RESET --- */
        :root {
            --primary: #0284c7;       /* Medical Blue */
            --primary-dark: #0369a1;
            --bg-body: #f8fafc;
            --bg-white: #ffffff;
            --text-main: #334155;
            --text-light: #64748b;
            --border: #e2e8f0;
            --radius: 8px;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            
            /* Status Colors */
            --critical: #ef4444;
            --stable: #22c55e;
            --watch: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); padding: 40px 20px; }

        /* --- CONTAINER --- */
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* --- HEADER & SEARCH --- */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-title h1 { font-size: 1.8rem; color: var(--text-main); }
        .page-title p { color: var(--text-light); margin-top: 5px; }

        .search-box {
            position: relative;
            width: 350px;
        }
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            outline: none;
            transition: 0.2s;
        }
        .search-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
        .search-box i {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: var(--text-light);
        }

        /* --- PATIENT LIST (Card Style) --- */
        .patient-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .patient-card {
            background: var(--bg-white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border);
            transition: transform 0.2s;
        }

        /* The Visible Row */
        .card-summary {
            display: grid;
            grid-template-columns: 80px 2fr 1fr 1fr 1fr 150px;
            align-items: center;
            padding: 20px;
            cursor: default; /* Changed from pointer since we removed toggle */
        }
        .card-summary:hover { background-color: #f1f5f9; }

        /* Columns styling */
        .col-avatar {
            width: 50px; height: 50px;
            background: #e0f2fe; color: var(--primary);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: bold;
            text-transform: uppercase;
        }
        .col-name h3 { font-size: 1.1rem; margin-bottom: 2px; }
        .col-name span { font-size: 0.85rem; color: var(--text-light); }
        
        .col-info { font-size: 0.95rem; }
        .col-info strong { display: block; font-size: 0.75rem; color: var(--text-light); text-transform: uppercase; margin-bottom: 2px; }

        /* Status Badges */
        .badge {
            padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-align: center; width: fit-content;
        }
        .badge.Stable { background: #dcfce7; color: #166534; }
        .badge.Critical { background: #fee2e2; color: #991b1b; }
        .badge.Observation { background: #fef3c7; color: #92400e; }

        /* Action Button */
        .btn-view {
            display: inline-block;
            text-decoration: none;
            text-align: center;
            background: transparent; border: 1px solid var(--border);
            padding: 8px 16px; border-radius: 6px; cursor: pointer;
            color: var(--primary); font-weight: 600;
            transition: 0.2s;
        }
        .btn-view:hover { background: var(--primary); color: white; border-color: var(--primary); }

        /* Responsive */
        @media (max-width: 768px) {
            .card-summary { grid-template-columns: 1fr 1fr; gap: 15px; }
            .col-avatar, .col-info { display: none; } /* Hide some columns on mobile */
            .col-name { grid-column: 1 / -1; }
        }
    </style>
</head>
<body>

    <div class="container">
        <header class="page-header">
            <div class="page-title">
                <h1>Patient Directory</h1>
                <p>Real-time overview of admitted patients</p>
            </div>
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search by name, ID..." onkeyup="filterPatients()">
            </div>
        </header>

        <div class="patient-list" id="patientListContainer">
            
            <?php 
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $p_id = $row['patient_id'];
                    $name = $row['full_name'];
                    $email = $row['email'];
                    $age = $row['age'] ?? 'N/A';
                    $condition = $row['current_condition_tag'] ?? 'Stable';
                    
                    // Generate Initials
                    $initials = strtoupper(substr($name, 0, 1));
                    if (strpos($name, ' ') !== false) {
                        $parts = explode(' ', $name);
                        $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
                    }

                    // CSS class for badge based on text
                    $badgeClass = $condition; 
                    ?>

                    <div class="patient-card">
                        <div class="card-summary">
                            <div class="col-avatar"><?php echo $initials; ?></div>
                            <div class="col-name">
                                <h3><?php echo htmlspecialchars($name); ?></h3>
                                <span>ID: #<?php echo $p_id; ?> • <?php echo $age; ?> Yrs • <?php echo htmlspecialchars($email); ?></span>
                            </div>
                            <div class="col-info">
                                <strong>Blood Type</strong>
                                <?php echo htmlspecialchars($row['blood_type'] ?? 'Unknown'); ?>
                            </div>
                            <div class="col-info">
                                <strong>Condition</strong>
                                <?php echo htmlspecialchars($condition); ?>
                            </div>
                            <div class="col-info">
                                <strong>Status</strong>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $condition; ?></span>
                            </div>
                            <div class="col-action">
                                <a href="patientinfooverview.php?id=<?php echo $p_id; ?>" class="btn-view">
                                    View Details <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                <?php 
                }
            } else {
                echo "<p style='text-align:center; color:#666;'>No patients found in database.</p>";
            }
            ?>

        </div>
    </div>

    <script>
        // Search Functionality
        function filterPatients() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const container = document.getElementById('patientListContainer');
            const cards = container.getElementsByClassName('patient-card');

            for (let i = 0; i < cards.length; i++) {
                const summary = cards[i].querySelector('.card-summary');
                const txtValue = summary.textContent || summary.innerText;

                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    cards[i].style.display = "";
                } else {
                    cards[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>