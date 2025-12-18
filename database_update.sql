-- Database Schema Update for Email Verification and Password Reset
-- Execute this SQL in phpMyAdmin or MySQL command line

-- First, check if database exists
CREATE DATABASE IF NOT EXISTS safe_space_db;
USE safe_space_db;

-- Update patients table to ensure all required columns exist
-- Note: This will only add columns if they don't exist

-- Add token column if it doesn't exist (for both verification and password reset)
ALTER TABLE patients 
ADD COLUMN IF NOT EXISTS token VARCHAR(255) NULL DEFAULT NULL AFTER password_hash;

-- Add is_verified column if it doesn't exist (0 = not verified, 1 = verified)
ALTER TABLE patients 
ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER token;

-- Add token_created_at column if it doesn't exist (for token expiry check)
ALTER TABLE patients 
ADD COLUMN IF NOT EXISTS token_created_at DATETIME NULL DEFAULT NULL AFTER is_verified;

-- Add index on token for faster lookups
ALTER TABLE patients 
ADD INDEX IF NOT EXISTS idx_token (token);

-- Add index on email for faster lookups
ALTER TABLE patients 
ADD INDEX IF NOT EXISTS idx_email (email);

-- Optional: View the current structure of patients table
DESCRIBE patients;

-- Optional: Sample query to check if columns exist
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM 
    INFORMATION_SCHEMA.COLUMNS 
WHERE 
    TABLE_SCHEMA = 'safe_space_db' 
    AND TABLE_NAME = 'patients'
    AND COLUMN_NAME IN ('token', 'is_verified', 'token_created_at');
