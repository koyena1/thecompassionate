<?php
include 'auth_check.php';
include '../config/db.php';

// Get selected patient
$selected_patient_id = $_GET['patient_id'] ?? null;

// Handle message submission from admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $patient_id = $_POST['patient_id'];
    $message_text = trim($_POST['message_text']);
    $file_path = null;
    $file_name = null;
    
    // Handle file upload
    if (!empty($_FILES['document']['name'])) {
        $upload_dir = '../patient/uploads/messages/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = basename($_FILES['document']['name']);
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = 'admin_' . $patient_id . '_' . time() . '.' . $file_extension;
        $file_path = 'uploads/messages/' . $new_file_name;
        
        move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . $new_file_name);
    }
    
    // Insert message
    $stmt = $conn->prepare("INSERT INTO messages (patient_id, sender_type, message_text, file_path, file_name) VALUES (?, 'admin', ?, ?, ?)");
    $stmt->bind_param("isss", $patient_id, $message_text, $file_path, $file_name);
    $stmt->execute();
    
    header("Location: followup_communication.php?patient_id=" . $patient_id);
    exit();
}

// Get all patients with message counts
$patients_query = $conn->query("
    SELECT p.*, 
           COUNT(DISTINCT m.message_id) as message_count,
           SUM(CASE WHEN m.sender_type = 'patient' AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count,
           MAX(m.created_at) as last_message_time
    FROM patients p
    LEFT JOIN messages m ON p.patient_id = m.patient_id
    GROUP BY p.patient_id
    HAVING message_count > 0 OR p.patient_id IN (SELECT patient_id FROM appointments)
    ORDER BY last_message_time DESC
");

// If a patient is selected, get their messages
$messages = [];
$patient_info = null;
if ($selected_patient_id) {
    // Mark patient messages as read
    $conn->query("UPDATE messages SET is_read = 1 WHERE patient_id = '$selected_patient_id' AND sender_type = 'patient'");
    
    // Get patient info
    $patient_query = $conn->query("SELECT * FROM patients WHERE patient_id = '$selected_patient_id'");
    $patient_info = $patient_query->fetch_assoc();
    
    // Get messages
    $messages_query = $conn->query("SELECT * FROM messages WHERE patient_id = '$selected_patient_id' ORDER BY created_at ASC");
    while ($msg = $messages_query->fetch_assoc()) {
        $messages[] = $msg;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Follow-up Communication - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        .sidebar {
            width: 300px;
            background: white;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 12px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-3px);
        }
        
        .patients-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .patient-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .patient-item:hover {
            background: #f9fafb;
        }
        
        .patient-item.active {
            background: #ede9fe;
            border-left: 4px solid #667eea;
        }
        
        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .patient-name {
            font-weight: 600;
            font-size: 15px;
            color: #1f2937;
        }
        
        .patient-info {
            font-size: 13px;
            color: #6b7280;
        }
        
        .unread-badge {
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }
        
        .chat-header {
            padding: 20px 30px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 20px;
        }
        
        .chat-header-info h3 {
            font-size: 18px;
            color: #1f2937;
        }
        
        .chat-header-info p {
            font-size: 13px;
            color: #6b7280;
        }
        
        .messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
            background: #f9fafb;
        }
        
        .message {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
        }
        
        .message.admin {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .message-content {
            max-width: 70%;
            background: white;
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .message.admin .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .message-text {
            font-size: 15px;
            line-height: 1.6;
            word-wrap: break-word;
        }
        
        .message-file {
            margin-top: 10px;
            padding: 10px 15px;
            background: #f3f4f6;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #374151;
            font-size: 14px;
        }
        
        .message.admin .message-file {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .message-time {
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
        }
        
        .message.admin .message-time {
            color: rgba(255,255,255,0.8);
        }
        
        .input-area {
            padding: 20px 30px;
            background: white;
            border-top: 1px solid #e5e7eb;
        }
        
        .input-form {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }
        
        .input-row {
            display: flex;
            gap: 12px;
        }
        
        .message-input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            outline: none;
        }
        
        .message-input:focus {
            border-color: #667eea;
        }
        
        .file-input-label {
            padding: 15px 20px;
            background: #f3f4f6;
            border-radius: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #374151;
        }
        
        .send-button {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .empty-state {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #d1d5db;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Patient Messages</h2>
            <p>Follow-up Communication</p>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
        
        <div class="patients-list">
            <?php while ($patient = $patients_query->fetch_assoc()): ?>
                <a href="?patient_id=<?php echo $patient['patient_id']; ?>" 
                   class="patient-item <?php echo ($selected_patient_id == $patient['patient_id']) ? 'active' : ''; ?>">
                    <div class="patient-header">
                        <span class="patient-name"><?php echo htmlspecialchars($patient['full_name']); ?></span>
                        <?php if ($patient['unread_count'] > 0): ?>
                            <span class="unread-badge"><?php echo $patient['unread_count']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="patient-info">
                        <?php echo htmlspecialchars($patient['email']); ?>
                        <?php if ($patient['last_message_time']): ?>
                            • <?php echo date('M d', strtotime($patient['last_message_time'])); ?>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
    
    <div class="chat-area">
        <?php if ($patient_info): ?>
            <div class="chat-header">
                <div class="patient-avatar">
                    <?php echo strtoupper(substr($patient_info['full_name'], 0, 1)); ?>
                </div>
                <div class="chat-header-info">
                    <h3><?php echo htmlspecialchars($patient_info['full_name']); ?></h3>
                    <p><?php echo htmlspecialchars($patient_info['email']); ?> • <?php echo htmlspecialchars($patient_info['phone_number']); ?></p>
                </div>
            </div>
            
            <div class="messages-area" id="messagesArea">
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender_type']; ?>">
                        <div class="message-avatar">
                            <?php if ($msg['sender_type'] === 'admin'): ?>
                                <i class="fas fa-user-md"></i>
                            <?php else: ?>
                                <?php echo strtoupper(substr($patient_info['full_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="message-content">
                                <?php if (!empty($msg['message_text'])): ?>
                                    <div class="message-text"><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></div>
                                <?php endif; ?>
                                
                                <?php if (!empty($msg['file_path'])): ?>
                                    <a href="../patient/<?php echo htmlspecialchars($msg['file_path']); ?>" target="_blank" class="message-file">
                                        <i class="fas fa-file"></i>
                                        <span><?php echo htmlspecialchars($msg['file_name']); ?></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="message-time">
                                <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="input-area">
                <form method="POST" enctype="multipart/form-data" class="input-form">
                    <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                    <div class="input-row">
                        <input type="text" name="message_text" class="message-input" placeholder="Type your message...">
                        
                        <label for="fileInput" class="file-input-label">
                            <i class="fas fa-paperclip"></i>
                            <span>Attach</span>
                        </label>
                        <input type="file" name="document" style="display:none;" id="fileInput">
                        
                        <button type="submit" name="send_message" class="send-button">
                            <span>Send</span>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div>
                    <i class="fas fa-comments"></i>
                    <h3>Select a patient to start messaging</h3>
                    <p>Choose a patient from the sidebar to view their messages</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        const messagesArea = document.getElementById('messagesArea');
        if (messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }
    </script>
</body>
</html>
