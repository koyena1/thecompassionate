-- Add instruction_title column to patient_uploads table
-- This allows us to rename uploaded files based on instruction titles

ALTER TABLE patient_uploads 
ADD COLUMN IF NOT EXISTS instruction_title VARCHAR(255) NULL AFTER patient_id;

-- Update existing records to have a default instruction title
UPDATE patient_uploads 
SET instruction_title = 'General Document' 
WHERE instruction_title IS NULL OR instruction_title = '';
