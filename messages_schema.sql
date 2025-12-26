-- Create messages table for patient-admin communication
CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    sender_type ENUM('patient', 'admin') NOT NULL,
    message_text TEXT,
    file_path VARCHAR(255) NULL,
    file_name VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
);

CREATE INDEX idx_patient_messages ON messages(patient_id, created_at);
CREATE INDEX idx_unread_messages ON messages(is_read);
