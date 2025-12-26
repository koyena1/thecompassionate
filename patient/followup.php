<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user_id'];

// Get patient details
$patient_query = $conn->query("SELECT full_name, profile_image FROM patients WHERE patient_id = '$patient_id'");
$patient_data = $patient_query->fetch_assoc();

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message_text = trim($_POST['message_text']);
    $file_path = null;
    $file_name = null;
    
    // Handle file upload
    if (!empty($_FILES['document']['name'])) {
        $upload_dir = 'uploads/messages/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = basename($_FILES['document']['name']);
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = 'msg_' . $patient_id . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $new_file_name;
        
        move_uploaded_file($_FILES['document']['tmp_name'], $file_path);
    }
    
    // Insert message
    $stmt = $conn->prepare("INSERT INTO messages (patient_id, sender_type, message_text, file_path, file_name) VALUES (?, 'patient', ?, ?, ?)");
    $stmt->bind_param("isss", $patient_id, $message_text, $file_path, $file_name);
    $stmt->execute();
    
    header("Location: followup.php");
    exit();
}

// Mark admin messages as read
$conn->query("UPDATE messages SET is_read = 1 WHERE patient_id = '$patient_id' AND sender_type = 'admin'");

// Fetch all messages
$messages_query = $conn->query("SELECT * FROM messages WHERE patient_id = '$patient_id' ORDER BY created_at ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Follow-up Communication</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            padding: 20px;
        }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #7B3F00 0%, #4a2600 100%);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
        }
        
        .main-content {
            margin-left: 280px;
            flex: 1;
            max-width: 1000px;
        }
        
        .chat-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 40px);
        }
        
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .chat-header h1 {
            font-size: 24px;
            font-weight: 600;
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
        
        .message.patient {
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
        
        .message.patient .message-content {
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
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: inherit;
            font-size: 14px;
        }
        
        .message.admin .message-file {
            background: #f3f4f6;
            color: #374151;
        }
        
        .message-time {
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
        }
        
        .message.patient .message-time {
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
            transition: border-color 0.3s;
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
            transition: background 0.3s;
        }
        
        .file-input-label:hover {
            background: #e5e7eb;
        }
        
        .file-input {
            display: none;
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
            transition: transform 0.3s;
        }
        
        .send-button:hover {
            transform: translateY(-2px);
        }
        
        .selected-file {
            font-size: 13px;
            color: #6b7280;
            padding: 8px 12px;
            background: #f9fafb;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .message-content { max-width: 85%; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="chat-container">
            <div class="chat-header">
                <i class="fas fa-comments"></i>
                <div>
                    <h1>Follow-up Communication</h1>
                    <p style="font-size: 14px; opacity: 0.9;">Chat with your doctor</p>
                </div>
            </div>
            
            <div class="messages-area" id="messagesArea">
                <?php if ($messages_query && $messages_query->num_rows > 0): ?>
                    <?php while ($msg = $messages_query->fetch_assoc()): ?>
                        <div class="message <?php echo $msg['sender_type']; ?>">
                            <div class="message-avatar">
                                <?php if ($msg['sender_type'] === 'admin'): ?>
                                    <i class="fas fa-user-md"></i>
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="message-content">
                                    <?php if (!empty($msg['message_text'])): ?>
                                        <div class="message-text"><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($msg['file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($msg['file_path']); ?>" target="_blank" class="message-file">
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
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h3>No messages yet</h3>
                        <p>Start a conversation with your doctor</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="input-area">
                <form method="POST" enctype="multipart/form-data" class="input-form" id="messageForm">
                    <div class="input-row">
                        <input type="text" name="message_text" class="message-input" placeholder="Type your message..." id="messageInput">
                        
                        <label for="fileInput" class="file-input-label">
                            <i class="fas fa-paperclip"></i>
                            <span>Attach</span>
                        </label>
                        <input type="file" name="document" class="file-input" id="fileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        
                        <button type="submit" name="send_message" class="send-button">
                            <span>Send</span>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div id="selectedFile"></div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll to bottom
        const messagesArea = document.getElementById('messagesArea');
        if (messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }
        
        // Show selected file name
        const fileInput = document.getElementById('fileInput');
        const selectedFileDiv = document.getElementById('selectedFile');
        
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                selectedFileDiv.innerHTML = `<div class="selected-file"><i class="fas fa-file"></i> ${this.files[0].name}</div>`;
            } else {
                selectedFileDiv.innerHTML = '';
            }
        });
        
        // Prevent empty message submission
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            const messageInput = document.getElementById('messageInput');
            const fileInput = document.getElementById('fileInput');
            
            if (!messageInput.value.trim() && fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please enter a message or attach a file');
            }
        });
    </script>
</body>
</html>
