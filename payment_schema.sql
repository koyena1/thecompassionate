-- Add payment-related columns to appointments table
USE safe_space_db;

ALTER TABLE appointments 
ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'pending' AFTER status,
ADD COLUMN IF NOT EXISTS payment_amount DECIMAL(10,2) DEFAULT 700.00 AFTER payment_status,
ADD COLUMN IF NOT EXISTS payment_id VARCHAR(255) NULL AFTER payment_amount,
ADD COLUMN IF NOT EXISTS payment_gateway VARCHAR(50) NULL AFTER payment_id,
ADD COLUMN IF NOT EXISTS payment_date DATETIME NULL AFTER payment_gateway,
ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(255) NULL AFTER payment_date,
ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(100) NULL AFTER transaction_id;

-- Add index for faster payment lookups
ALTER TABLE appointments 
ADD INDEX IF NOT EXISTS idx_payment_id (payment_id),
ADD INDEX IF NOT EXISTS idx_payment_status (payment_status);

-- View updated structure
DESCRIBE appointments;
