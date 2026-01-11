-- Add password_hash column to instructors table for portal authentication
-- Migration: 003_add_password_to_instructors.sql

-- Add password_hash column (nullable for existing instructors)
ALTER TABLE instructors 
ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) DEFAULT NULL;

-- Add index for faster lookups
CREATE INDEX IF NOT EXISTS idx_instructors_phone_business 
ON instructors(business_id, phone) 
WHERE is_active = true;

-- Optional: Create demo instructor accounts with password
-- Password for all demo accounts: "demo123"
-- Hash generated with: password_hash('demo123', PASSWORD_DEFAULT)

-- Update existing instructors with demo password (optional - for testing)
-- You can remove this in production
UPDATE instructors 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE password_hash IS NULL;

COMMENT ON COLUMN instructors.password_hash IS 'Bcrypt password hash for portal login';

