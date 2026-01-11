-- ============================================
-- Driving School Database Creation Script
-- PostgreSQL Database Schema
-- ============================================
-- This script creates the database and all required tables
-- Compatible with both the design summary and PHP backend code
-- ============================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================
-- 1. STUDENTS TABLE
-- ============================================
CREATE TABLE students (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name TEXT,
    phone VARCHAR(20) UNIQUE NOT NULL,
    otp_verified BOOLEAN DEFAULT false,
    otp_code VARCHAR(10),
    otp_expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_students_phone ON students(phone);

-- ============================================
-- 2. INSTRUCTORS TABLE
-- ============================================
CREATE TABLE instructors (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name TEXT NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    max_hours_per_week INT DEFAULT 40,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_instructors_email ON instructors(email);
CREATE INDEX idx_instructors_active ON instructors(is_active);

-- ============================================
-- 3. LESSON TYPES TABLE
-- ============================================
CREATE TABLE lesson_types (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name TEXT NOT NULL,
    description TEXT,
    duration_minutes INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_lesson_types_active ON lesson_types(is_active);

-- ============================================
-- 4. LESSONS TABLE (Central scheduling table)
-- ============================================
CREATE TABLE lessons (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    lesson_type_id UUID REFERENCES lesson_types(id) ON DELETE RESTRICT,
    instructor_id UUID REFERENCES instructors(id) ON DELETE RESTRICT,
    student_id UUID REFERENCES students(id) ON DELETE RESTRICT,
    scheduled_at TIMESTAMP NOT NULL,
    status VARCHAR(20) DEFAULT 'pending_deposit' CHECK (status IN ('pending_deposit', 'confirmed', 'in_progress', 'completed', 'cancelled')),
    deposit_paid BOOLEAN DEFAULT false,
    lesson_otp VARCHAR(10),
    lesson_otp_expires_at TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Important indexes for performance (as per design summary)
CREATE INDEX idx_lessons_instructor_scheduled ON lessons(instructor_id, scheduled_at);
CREATE INDEX idx_lessons_student_scheduled ON lessons(student_id, scheduled_at);
CREATE INDEX idx_lessons_status ON lessons(status);
CREATE INDEX idx_lessons_scheduled_at ON lessons(scheduled_at);

-- ============================================
-- 5. PAYMENT DEPOSITS TABLE
-- ============================================
CREATE TABLE payment_deposits (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    lesson_id UUID REFERENCES lessons(id) ON DELETE CASCADE,
    amount DECIMAL(10, 2) NOT NULL,
    payid_reference TEXT,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'failed')),
    verified_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payment_deposits_lesson ON payment_deposits(lesson_id);
CREATE INDEX idx_payment_deposits_status ON payment_deposits(status);
CREATE INDEX idx_payment_deposits_payid_ref ON payment_deposits(payid_reference);

-- ============================================
-- 6. SMS NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE sms_notifications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    lesson_id UUID REFERENCES lessons(id) ON DELETE SET NULL,
    phone VARCHAR(20) NOT NULL,
    type VARCHAR(50) NOT NULL CHECK (type IN ('booking', 'deposit_confirmed', 'reschedule', 'cancel', 'lesson_otp', 'reminder')),
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'failed')),
    sent_at TIMESTAMP,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_sms_notifications_lesson ON sms_notifications(lesson_id);
CREATE INDEX idx_sms_notifications_status ON sms_notifications(status);
CREATE INDEX idx_sms_notifications_phone ON sms_notifications(phone);

-- ============================================
-- TRIGGER: Update updated_at timestamp
-- ============================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_students_updated_at BEFORE UPDATE ON students
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_instructors_updated_at BEFORE UPDATE ON instructors
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_lesson_types_updated_at BEFORE UPDATE ON lesson_types
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_lessons_updated_at BEFORE UPDATE ON lessons
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_payment_deposits_updated_at BEFORE UPDATE ON payment_deposits
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_sms_notifications_updated_at BEFORE UPDATE ON sms_notifications
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================
-- SCRIPT COMPLETE
-- ============================================
-- To run this script:
-- 1. Connect to PostgreSQL: psql -U postgres
-- 2. Create database: CREATE DATABASE driving_school;
-- 3. Connect to database: \c driving_school
-- 4. Run this script: \i create_database.sql
--
-- Or from command line:
-- psql -U postgres -d driving_school -f create_database.sql
-- ============================================

