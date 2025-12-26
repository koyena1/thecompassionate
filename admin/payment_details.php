<?php
// Admin authentication
include 'auth_check.php';

include '../config/db.php';

// Get filter parameters
$search_patient = $_GET['search'] ?? '';
$payment_mode = $_GET['mode'] ?? 'all'; // all, test, prod

// Build query
$sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.payment_status, 
               a.payment_amount, a.payment_gateway, a.payment_date, a.payment_id, 
               a.transaction_id, a.invoice_number,
               p.full_name, p.email, p.phone_number
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.payment_status IN ('paid', 'completed')";

// Add search filter
if (!empty($search_patient)) {
    $search_patient = $conn->real_escape_string($search_patient);
    $sql .= " AND (p.full_name LIKE '%$search_patient%' OR p.email LIKE '%$search_patient%' OR p.phone_number LIKE '%$search_patient%')";
}

// Add mode filter (test vs prod)
if ($payment_mode === 'test') {
    $sql .= " AND (a.payment_gateway LIKE '%Test%' OR a.payment_id LIKE '%TEST%')";
} elseif ($payment_mode === 'prod') {
    $sql .= " AND (a.payment_gateway NOT LIKE '%Test%' AND (a.payment_id NOT LIKE '%TEST%' OR a.payment_id IS NULL))";
}

$sql .= " ORDER BY a.payment_date DESC";

$result = $conn->query($sql);

// Calculate totals
$total_amount = 0;
$total_test = 0;
$total_prod = 0;
$payment_count = 0;

if ($result && $result->num_rows > 0) {
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        $total_amount += floatval($row['payment_amount']);
        $payment_count++;
        
        // Check if test or prod
        if (strpos($row['payment_gateway'], 'Test') !== false || strpos($row['payment_id'], 'TEST') !== false) {
            $total_test++;
        } else {
            $total_prod++;
        }
    }
    $result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 30px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .back-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: transform 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .stat-card.total { border-left: 4px solid #00b69b; }
        .stat-card.test { border-left: 4px solid #ffa500; }
        .stat-card.prod { border-left: 4px solid #667eea; }
        .stat-card.count { border-left: 4px solid #1fb6ff; }
        
        .stat-card h3 {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .filters {
            background: white;
            padding: 20px 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-group label {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #667eea;
        }
        
        .filter-btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .filter-btn:hover {
            background: #5568d3;
        }
        
        .reset-btn {
            background: #6b7280;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f9fafb;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #4b5563;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge.test {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge.prod {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge.paid {
            background: #d4edda;
            color: #155724;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #d1d5db;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-money-bill-wave"></i> Payment Details</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card total">
                <h3>Total Revenue</h3>
                <div class="value">₹<?php echo number_format($total_amount, 2); ?></div>
            </div>
            <div class="stat-card test">
                <h3>Test Payments</h3>
                <div class="value"><?php echo $total_test; ?></div>
            </div>
            <div class="stat-card prod">
                <h3>Production Payments</h3>
                <div class="value"><?php echo $total_prod; ?></div>
            </div>
            <div class="stat-card count">
                <h3>Total Transactions</h3>
                <div class="value"><?php echo $payment_count; ?></div>
            </div>
        </div>
        
        <form method="GET" class="filters">
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Search Patient:</label>
                <input type="text" name="search" placeholder="Name, Email or Phone" value="<?php echo htmlspecialchars($search_patient); ?>" style="min-width: 250px;">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Mode:</label>
                <select name="mode">
                    <option value="all" <?php echo $payment_mode === 'all' ? 'selected' : ''; ?>>All Payments</option>
                    <option value="test" <?php echo $payment_mode === 'test' ? 'selected' : ''; ?>>Test Only</option>
                    <option value="prod" <?php echo $payment_mode === 'prod' ? 'selected' : ''; ?>>Production Only</option>
                </select>
            </div>
            <button type="submit" class="filter-btn"><i class="fas fa-check"></i> Apply</button>
            <a href="payment_details.php" class="reset-btn"><i class="fas fa-redo"></i> Reset</a>
        </form>
        
        <div class="table-container">
            <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Patient Name</th>
                        <th>Contact</th>
                        <th>Appointment Date</th>
                        <th>Amount</th>
                        <th>Gateway</th>
                        <th>Mode</th>
                        <th>Payment Date</th>
                        <th>Transaction ID</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($payment = $result->fetch_assoc()): 
                        $is_test = strpos($payment['payment_gateway'], 'Test') !== false || 
                                   strpos($payment['payment_id'], 'TEST') !== false;
                        $payment_date_formatted = $payment['payment_date'] ? date('d M Y, h:i A', strtotime($payment['payment_date'])) : 'N/A';
                        $transaction_id = $payment['payment_id'] ?? $payment['transaction_id'] ?? 'N/A';
                        if (strlen($transaction_id) > 20) {
                            $transaction_id = substr($transaction_id, 0, 20) . '...';
                        }
                    ?>
                    <tr>
                        <td><strong>#APT-<?php echo $payment['appointment_id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                        <td>
                            <div><?php echo htmlspecialchars($payment['email']); ?></div>
                            <div style="font-size: 12px; color: #6b7280;"><?php echo htmlspecialchars($payment['phone_number']); ?></div>
                        </td>
                        <td><?php echo date('d M Y', strtotime($payment['appointment_date'])); ?></td>
                        <td><strong>₹<?php echo number_format($payment['payment_amount'], 2); ?></strong></td>
                        <td><?php echo htmlspecialchars($payment['payment_gateway'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge <?php echo $is_test ? 'test' : 'prod'; ?>">
                                <?php echo $is_test ? 'TEST' : 'PRODUCTION'; ?>
                            </span>
                        </td>
                        <td><?php echo $payment_date_formatted; ?></td>
                        <td><code style="font-size: 11px;"><?php echo htmlspecialchars($transaction_id); ?></code></td>
                        <td><span class="badge paid"><i class="fas fa-check"></i> Paid</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <h3>No payment records found</h3>
                <p>Payments will appear here once transactions are completed</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
