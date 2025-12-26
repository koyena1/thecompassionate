-- SQL Update for Patient-Specific Documentation Feature
-- This allows admin to create individual instructions for specific patients

-- First, let's modify the documentation table to support multiple entries with patient_id
-- We'll keep the global instruction (id=1, patient_id=NULL) and add patient-specific ones

-- Add patient_id column to documentation table (if not exists)
ALTER TABLE documentation 
ADD COLUMN IF NOT EXISTS patient_id INT NULL AFTER id,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add foreign key constraint
ALTER TABLE documentation 
ADD CONSTRAINT fk_documentation_patient 
FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE;

-- Make sure the global instruction has NULL patient_id (for all patients)
UPDATE documentation SET patient_id = NULL WHERE id = 1;

-- Sample data structure explanation:
-- patient_id = NULL means it's a global instruction visible to all patients
-- patient_id = specific_id means it's for that specific patient only
-- The patient will see their specific instruction if it exists, otherwise the global one

-- Index for faster queries
CREATE INDEX idx_patient_id ON documentation(patient_id);
