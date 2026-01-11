-- Add password_hash column to students table for portal login
-- Migration: 004_add_password_to_students.sql

-- Add password_hash column (nullable - existing students won't have passwords)
ALTER TABLE students 
ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) DEFAULT NULL;

-- Add index for faster lookups
CREATE INDEX IF NOT EXISTS idx_students_phone_business 
ON students(business_id, phone);

-- Add comment
COMMENT ON COLUMN students.password_hash IS 'Bcrypt password hash for portal login (optional - students can also use OTP)';

-- Note: Existing students will have NULL password_hash
-- They can still use OTP for booking, or set a password for portal access

