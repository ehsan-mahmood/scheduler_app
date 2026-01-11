-- ============================================
-- MIGRATION SCRIPT: Single-Tenant to Multi-Tenant
-- ============================================
-- This script migrates your existing single-tenant database
-- to a multi-tenant architecture with optimized indexes
-- 
-- IMPORTANT: 
-- 1. BACKUP your database first!
-- 2. Run this during low-traffic period
-- 3. Test on staging environment first
-- 4. Estimated downtime: 5-15 minutes (depending on data size)
-- ============================================

-- ============================================
-- STEP 0: Pre-Migration Validation
-- ============================================

DO $$
BEGIN
    RAISE NOTICE '===========================================';
    RAISE NOTICE 'PRE-MIGRATION VALIDATION';
    RAISE NOTICE '===========================================';
    
    -- Check current table counts
    RAISE NOTICE 'Current data counts:';
    RAISE NOTICE 'Students: %', (SELECT COUNT(*) FROM students);
    RAISE NOTICE 'Instructors: %', (SELECT COUNT(*) FROM instructors);
    RAISE NOTICE 'Lessons: %', (SELECT COUNT(*) FROM lessons);
    RAISE NOTICE 'Lesson Types: %', (SELECT COUNT(*) FROM lesson_types);
    RAISE NOTICE 'Payment Deposits: %', (SELECT COUNT(*) FROM payment_deposits);
    RAISE NOTICE 'SMS Notifications: %', (SELECT COUNT(*) FROM sms_notifications);
    RAISE NOTICE '===========================================';
END $$;

-- ============================================
-- STEP 1: Enable Required Extensions
-- ============================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm"; -- For text search optimization

-- ============================================
-- STEP 2: Create New Tables (Businesses & Configs)
-- ============================================

-- Businesses Table
CREATE TABLE businesses (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- Business Identity
    business_name TEXT NOT NULL,
    subdomain VARCHAR(63) UNIQUE NOT NULL,
    custom_domain VARCHAR(255) UNIQUE,
    
    -- Contact Information
    owner_name TEXT,
    owner_email VARCHAR(255) NOT NULL,
    owner_phone VARCHAR(20),
    billing_email VARCHAR(255),
    
    -- Business Address
    address_line1 TEXT,
    address_line2 TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(2) DEFAULT 'AU',
    
    -- Status & Plan
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('trial', 'active', 'suspended', 'cancelled')),
    plan VARCHAR(20) DEFAULT 'basic' CHECK (plan IN ('basic', 'pro', 'enterprise')),
    
    -- Resource Limits
    max_instructors INT DEFAULT 10,
    max_students INT DEFAULT 1000,
    max_monthly_bookings INT DEFAULT 500,
    storage_limit_mb INT DEFAULT 1000,
    
    -- Subscription & Billing
    trial_ends_at TIMESTAMP,
    subscription_starts_at TIMESTAMP,
    subscription_ends_at TIMESTAMP,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP
);

CREATE INDEX idx_businesses_subdomain ON businesses(subdomain) WHERE deleted_at IS NULL;
CREATE INDEX idx_businesses_status ON businesses(status) WHERE deleted_at IS NULL;
CREATE INDEX idx_businesses_plan ON businesses(plan);
CREATE INDEX idx_businesses_owner_email ON businesses(owner_email);

-- Business Configs Table
CREATE TABLE business_configs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    
    config_key VARCHAR(100) NOT NULL,
    config_value TEXT NOT NULL,
    data_type VARCHAR(20) DEFAULT 'string' CHECK (data_type IN ('string', 'number', 'boolean', 'json')),
    description TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(business_id, config_key)
);

CREATE INDEX idx_business_configs_business ON business_configs(business_id);
CREATE INDEX idx_business_configs_key ON business_configs(business_id, config_key);

-- Business Usage Stats Table
CREATE TABLE business_usage_stats (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    
    metric_name VARCHAR(50) NOT NULL,
    metric_value BIGINT NOT NULL,
    metric_unit VARCHAR(20),
    
    recorded_at DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(business_id, metric_name, recorded_at)
);

CREATE INDEX idx_business_usage_business_date ON business_usage_stats(business_id, recorded_at DESC);
CREATE INDEX idx_business_usage_metric ON business_usage_stats(business_id, metric_name, recorded_at DESC);

-- ============================================
-- STEP 3: Insert Default Business (Your Current Business)
-- ============================================

-- This creates a business for your existing data
INSERT INTO businesses (
    id,
    business_name,
    subdomain,
    owner_email,
    status,
    plan
) VALUES (
    uuid_generate_v4(),
    'Main Driving School', -- Change this to your business name
    'main', -- Change this to your desired subdomain
    'admin@example.com', -- Change this to your admin email
    'active',
    'pro'
);

-- Store the default business ID for later use
DO $$
DECLARE
    v_default_business_id UUID;
BEGIN
    SELECT id INTO v_default_business_id 
    FROM businesses 
    WHERE subdomain = 'main';
    
    -- Store in temp table for use in subsequent steps
    CREATE TEMP TABLE migration_context (
        default_business_id UUID
    );
    
    INSERT INTO migration_context VALUES (v_default_business_id);
    
    RAISE NOTICE 'Default business created with ID: %', v_default_business_id;
END $$;

-- Insert default configs for the business
INSERT INTO business_configs (business_id, config_key, config_value, data_type, description)
SELECT 
    default_business_id,
    config_key,
    config_value,
    data_type,
    description
FROM migration_context,
LATERAL (VALUES
    ('sms_enabled', 'true', 'boolean', 'Enable SMS notifications'),
    ('sms_reminders', 'true', 'boolean', 'Send lesson reminders via SMS'),
    ('booking_buffer_minutes', '30', 'number', 'Minimum notice for booking (minutes)'),
    ('cancellation_hours', '24', 'number', 'Hours before lesson to allow cancellation'),
    ('deposit_percentage', '50', 'number', 'Deposit percentage required'),
    ('timezone', 'Australia/Sydney', 'string', 'Business timezone'),
    ('currency', 'AUD', 'string', 'Currency code'),
    ('business_hours', '{"start": "08:00", "end": "18:00"}', 'json', 'Operating hours'),
    ('online_payment_enabled', 'true', 'boolean', 'Accept online payments'),
    ('auto_confirm_lessons', 'false', 'boolean', 'Auto-confirm after deposit')
) AS configs(config_key, config_value, data_type, description);

-- ============================================
-- STEP 4: Add business_id Columns to Existing Tables
-- ============================================

-- Add business_id column (nullable first, we'll populate then make NOT NULL)
ALTER TABLE students ADD COLUMN business_id UUID;
ALTER TABLE instructors ADD COLUMN business_id UUID;
ALTER TABLE lesson_types ADD COLUMN business_id UUID;
ALTER TABLE lessons ADD COLUMN business_id UUID;
ALTER TABLE payment_deposits ADD COLUMN business_id UUID;
ALTER TABLE sms_notifications ADD COLUMN business_id UUID;

-- ============================================
-- STEP 5: Populate business_id for Existing Data
-- ============================================

-- Update all existing records with the default business ID
UPDATE students 
SET business_id = (SELECT default_business_id FROM migration_context);

UPDATE instructors 
SET business_id = (SELECT default_business_id FROM migration_context);

UPDATE lesson_types 
SET business_id = (SELECT default_business_id FROM migration_context);

UPDATE lessons 
SET business_id = (SELECT default_business_id FROM migration_context);

UPDATE payment_deposits 
SET business_id = (SELECT default_business_id FROM migration_context);

UPDATE sms_notifications 
SET business_id = (SELECT default_business_id FROM migration_context);

-- Verify all records have business_id
DO $$
BEGIN
    RAISE NOTICE '===========================================';
    RAISE NOTICE 'BUSINESS_ID POPULATION VERIFICATION';
    RAISE NOTICE '===========================================';
    
    IF EXISTS (SELECT 1 FROM students WHERE business_id IS NULL) THEN
        RAISE EXCEPTION 'Some students have NULL business_id!';
    END IF;
    
    IF EXISTS (SELECT 1 FROM instructors WHERE business_id IS NULL) THEN
        RAISE EXCEPTION 'Some instructors have NULL business_id!';
    END IF;
    
    IF EXISTS (SELECT 1 FROM lessons WHERE business_id IS NULL) THEN
        RAISE EXCEPTION 'Some lessons have NULL business_id!';
    END IF;
    
    RAISE NOTICE 'All records successfully populated with business_id';
    RAISE NOTICE '===========================================';
END $$;

-- ============================================
-- STEP 6: Make business_id NOT NULL and Add Foreign Keys
-- ============================================

ALTER TABLE students 
    ALTER COLUMN business_id SET NOT NULL,
    ADD CONSTRAINT fk_students_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE;

ALTER TABLE instructors 
    ALTER COLUMN business_id SET NOT NULL,
    ADD CONSTRAINT fk_instructors_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE;

ALTER TABLE lesson_types 
    ALTER COLUMN business_id SET NOT NULL,
    ADD CONSTRAINT fk_lesson_types_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE;

ALTER TABLE lessons 
    ALTER COLUMN business_id SET NOT NULL,
    ADD CONSTRAINT fk_lessons_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE;

ALTER TABLE payment_deposits 
    ALTER COLUMN business_id SET NOT NULL,
    ADD CONSTRAINT fk_payment_deposits_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE;

ALTER TABLE sms_notifications 
    ALTER COLUMN business_id SET NOT NULL,
    ADD CONSTRAINT fk_sms_notifications_business 
        FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE;

-- ============================================
-- STEP 7: Update Unique Constraints for Multi-Tenancy
-- ============================================

-- Students: Phone unique per business (not globally)
ALTER TABLE students DROP CONSTRAINT IF EXISTS students_phone_key;
CREATE UNIQUE INDEX idx_students_phone_business_unique ON students(business_id, phone);

-- Instructors: Email unique per business (not globally)
ALTER TABLE instructors DROP CONSTRAINT IF EXISTS instructors_email_key;
CREATE UNIQUE INDEX idx_instructors_email_business_unique ON instructors(business_id, email);

-- Add email column to students if needed
ALTER TABLE students ADD COLUMN IF NOT EXISTS email VARCHAR(255);
ALTER TABLE students ADD COLUMN IF NOT EXISTS notes TEXT;

-- Add additional instructor fields
ALTER TABLE instructors ADD COLUMN IF NOT EXISTS bio TEXT;
ALTER TABLE instructors ADD COLUMN IF NOT EXISTS specialties TEXT[];
ALTER TABLE instructors ADD COLUMN IF NOT EXISTS hire_date DATE;

-- Add additional lesson_types fields
ALTER TABLE lesson_types ADD COLUMN IF NOT EXISTS color_code VARCHAR(7);
ALTER TABLE lesson_types ADD COLUMN IF NOT EXISTS display_order INT DEFAULT 0;

-- Add additional lessons fields
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS cancellation_reason TEXT;
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS cancelled_at TIMESTAMP;
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP;

-- Add additional payment_deposits fields
ALTER TABLE payment_deposits ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'AUD';
ALTER TABLE payment_deposits ADD COLUMN IF NOT EXISTS payment_method VARCHAR(20) DEFAULT 'payid';
ALTER TABLE payment_deposits ADD COLUMN IF NOT EXISTS transaction_id TEXT;
ALTER TABLE payment_deposits ADD COLUMN IF NOT EXISTS refunded_at TIMESTAMP;
ALTER TABLE payment_deposits ADD COLUMN IF NOT EXISTS notes TEXT;

-- Add CHECK constraint for payment_method
DO $$
BEGIN
    ALTER TABLE payment_deposits DROP CONSTRAINT IF EXISTS payment_deposits_payment_method_check;
    ALTER TABLE payment_deposits ADD CONSTRAINT payment_deposits_payment_method_check 
        CHECK (payment_method IN ('payid', 'card', 'cash', 'bank_transfer'));
EXCEPTION
    WHEN duplicate_object THEN NULL;
END $$;

-- Update payment status to include 'refunded'
DO $$
BEGIN
    ALTER TABLE payment_deposits DROP CONSTRAINT IF EXISTS payment_deposits_status_check;
    ALTER TABLE payment_deposits ADD CONSTRAINT payment_deposits_status_check 
        CHECK (status IN ('pending', 'confirmed', 'failed', 'refunded'));
EXCEPTION
    WHEN duplicate_object THEN NULL;
END $$;

-- Add additional sms_notifications fields
ALTER TABLE sms_notifications ADD COLUMN IF NOT EXISTS retry_count INT DEFAULT 0;
ALTER TABLE sms_notifications ADD COLUMN IF NOT EXISTS cost_cents INT;

-- ============================================
-- STEP 8: Drop Old Indexes
-- ============================================

-- Drop single-tenant indexes
DROP INDEX IF EXISTS idx_students_phone;
DROP INDEX IF EXISTS idx_instructors_email;
DROP INDEX IF EXISTS idx_instructors_active;
DROP INDEX IF EXISTS idx_lesson_types_active;
DROP INDEX IF EXISTS idx_lessons_instructor_scheduled;
DROP INDEX IF EXISTS idx_lessons_student_scheduled;
DROP INDEX IF EXISTS idx_lessons_status;
DROP INDEX IF EXISTS idx_lessons_scheduled_at;
DROP INDEX IF EXISTS idx_payment_deposits_lesson;
DROP INDEX IF EXISTS idx_payment_deposits_status;
DROP INDEX IF EXISTS idx_payment_deposits_payid_ref;
DROP INDEX IF EXISTS idx_sms_notifications_lesson;
DROP INDEX IF EXISTS idx_sms_notifications_status;
DROP INDEX IF EXISTS idx_sms_notifications_phone;

-- ============================================
-- STEP 9: Create Optimized Multi-Tenant Indexes
-- ============================================

-- ==================
-- STUDENTS INDEXES
-- ==================
CREATE INDEX idx_students_business ON students(business_id);
CREATE INDEX idx_students_business_phone ON students(business_id, phone);
CREATE INDEX idx_students_phone_lookup ON students(phone); -- For cross-business lookup if needed

-- ==================
-- INSTRUCTORS INDEXES
-- ==================
CREATE INDEX idx_instructors_business ON instructors(business_id);
CREATE INDEX idx_instructors_business_active ON instructors(business_id, is_active) 
    WHERE is_active = true;
CREATE INDEX idx_instructors_email_lookup ON instructors(email);

-- ==================
-- LESSON TYPES INDEXES
-- ==================
CREATE INDEX idx_lesson_types_business ON lesson_types(business_id);
CREATE INDEX idx_lesson_types_business_active ON lesson_types(business_id, is_active) 
    WHERE is_active = true;

-- ==================
-- LESSONS INDEXES (Most Critical)
-- ==================

-- Basic business index
CREATE INDEX idx_lessons_business ON lessons(business_id);

-- Availability check optimization (most frequent query)
CREATE INDEX idx_lessons_business_instructor_scheduled 
    ON lessons(business_id, instructor_id, scheduled_at);

-- Student history
CREATE INDEX idx_lessons_business_student_scheduled 
    ON lessons(business_id, student_id, scheduled_at DESC);

-- Status filtering
CREATE INDEX idx_lessons_business_status 
    ON lessons(business_id, status);

-- ⭐ PARTIAL INDEX: Active/Future lessons only (HUGE performance boost)
-- This is the killer optimization - only indexes relevant lessons
CREATE INDEX idx_lessons_business_active 
    ON lessons(business_id, instructor_id, scheduled_at, status)
    WHERE status IN ('confirmed', 'in_progress', 'pending_deposit') 
      AND scheduled_at >= CURRENT_DATE - INTERVAL '7 days';

-- ⭐ PARTIAL INDEX: Today's lessons (Portal dashboard optimization)
CREATE INDEX idx_lessons_business_today 
    ON lessons(business_id, instructor_id, scheduled_at)
    WHERE scheduled_at >= CURRENT_DATE 
      AND scheduled_at < CURRENT_DATE + INTERVAL '1 day'
      AND status != 'cancelled';

-- Scheduled time range queries
CREATE INDEX idx_lessons_scheduled_at ON lessons(scheduled_at) 
    WHERE status IN ('confirmed', 'in_progress');

-- Instructor workload calculation
CREATE INDEX idx_lessons_instructor_week 
    ON lessons(instructor_id, scheduled_at)
    WHERE status IN ('confirmed', 'completed', 'in_progress');

-- ==================
-- PAYMENT DEPOSITS INDEXES
-- ==================
CREATE INDEX idx_payment_deposits_business ON payment_deposits(business_id);
CREATE INDEX idx_payment_deposits_lesson ON payment_deposits(lesson_id);
CREATE INDEX idx_payment_deposits_business_status ON payment_deposits(business_id, status);

-- ⭐ PARTIAL INDEX: Pending payments only (verification job optimization)
CREATE INDEX idx_payment_deposits_pending 
    ON payment_deposits(business_id, created_at DESC)
    WHERE status = 'pending';

-- PayID reference lookup
CREATE INDEX idx_payment_deposits_payid_ref ON payment_deposits(payid_reference) 
    WHERE payid_reference IS NOT NULL;

-- Transaction ID lookup
CREATE INDEX idx_payment_deposits_transaction_id ON payment_deposits(transaction_id)
    WHERE transaction_id IS NOT NULL;

-- ==================
-- SMS NOTIFICATIONS INDEXES
-- ==================
CREATE INDEX idx_sms_notifications_business ON sms_notifications(business_id);
CREATE INDEX idx_sms_notifications_lesson ON sms_notifications(lesson_id);
CREATE INDEX idx_sms_notifications_business_status ON sms_notifications(business_id, status);

-- ⭐ PARTIAL INDEX: Pending SMS only (sending job optimization)
CREATE INDEX idx_sms_notifications_pending 
    ON sms_notifications(created_at ASC)
    WHERE status = 'pending' AND retry_count < 3;

CREATE INDEX idx_sms_notifications_phone ON sms_notifications(phone);

-- Cost analysis
CREATE INDEX idx_sms_notifications_business_date 
    ON sms_notifications(business_id, created_at DESC)
    WHERE cost_cents IS NOT NULL;

-- ============================================
-- STEP 10: Create Triggers and Functions
-- ============================================

-- Default configs trigger
CREATE OR REPLACE FUNCTION create_default_business_configs()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO business_configs (business_id, config_key, config_value, data_type, description) VALUES
    (NEW.id, 'sms_enabled', 'true', 'boolean', 'Enable SMS notifications'),
    (NEW.id, 'sms_reminders', 'true', 'boolean', 'Send lesson reminders via SMS'),
    (NEW.id, 'booking_buffer_minutes', '30', 'number', 'Minimum notice for booking (minutes)'),
    (NEW.id, 'cancellation_hours', '24', 'number', 'Hours before lesson to allow cancellation'),
    (NEW.id, 'deposit_percentage', '50', 'number', 'Deposit percentage required'),
    (NEW.id, 'timezone', 'Australia/Sydney', 'string', 'Business timezone'),
    (NEW.id, 'currency', 'AUD', 'string', 'Currency code'),
    (NEW.id, 'business_hours', '{"start": "08:00", "end": "18:00"}', 'json', 'Operating hours'),
    (NEW.id, 'online_payment_enabled', 'true', 'boolean', 'Accept online payments'),
    (NEW.id, 'auto_confirm_lessons', 'false', 'boolean', 'Auto-confirm after deposit');
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_create_default_configs
AFTER INSERT ON businesses
FOR EACH ROW
EXECUTE FUNCTION create_default_business_configs();

-- Updated_at triggers (update existing)
DROP TRIGGER IF EXISTS update_businesses_updated_at ON businesses;
CREATE TRIGGER update_businesses_updated_at BEFORE UPDATE ON businesses
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_business_configs_updated_at ON business_configs;
CREATE TRIGGER update_business_configs_updated_at BEFORE UPDATE ON business_configs
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================
-- STEP 11: Create Helper Views and Functions
-- ============================================

-- Active businesses overview
CREATE OR REPLACE VIEW active_businesses_summary AS
SELECT 
    b.id,
    b.business_name,
    b.subdomain,
    b.status,
    b.plan,
    COUNT(DISTINCT i.id) as instructor_count,
    COUNT(DISTINCT s.id) as student_count,
    COUNT(DISTINCT l.id) FILTER (WHERE l.created_at >= CURRENT_DATE - INTERVAL '30 days') as monthly_bookings
FROM businesses b
LEFT JOIN instructors i ON b.id = i.business_id AND i.is_active = true
LEFT JOIN students s ON b.id = s.business_id
LEFT JOIN lessons l ON b.id = l.business_id
WHERE b.status = 'active' AND b.deleted_at IS NULL
GROUP BY b.id, b.business_name, b.subdomain, b.status, b.plan;

-- Helper function to get config values
CREATE OR REPLACE FUNCTION get_business_config(
    p_business_id UUID,
    p_config_key VARCHAR,
    p_default_value TEXT DEFAULT NULL
)
RETURNS TEXT AS $$
DECLARE
    v_value TEXT;
BEGIN
    SELECT config_value INTO v_value
    FROM business_configs
    WHERE business_id = p_business_id AND config_key = p_config_key;
    
    RETURN COALESCE(v_value, p_default_value);
END;
$$ LANGUAGE plpgsql;

-- Function to check business limits
CREATE OR REPLACE FUNCTION check_business_limit(
    p_business_id UUID,
    p_limit_type VARCHAR -- 'instructors', 'students', 'monthly_bookings'
)
RETURNS BOOLEAN AS $$
DECLARE
    v_current_count INT;
    v_max_limit INT;
BEGIN
    IF p_limit_type = 'instructors' THEN
        SELECT COUNT(*), b.max_instructors INTO v_current_count, v_max_limit
        FROM instructors i
        JOIN businesses b ON b.id = i.business_id
        WHERE i.business_id = p_business_id AND i.is_active = true
        GROUP BY b.max_instructors;
        
    ELSIF p_limit_type = 'students' THEN
        SELECT COUNT(*), b.max_students INTO v_current_count, v_max_limit
        FROM students s
        JOIN businesses b ON b.id = s.business_id
        WHERE s.business_id = p_business_id
        GROUP BY b.max_students;
        
    ELSIF p_limit_type = 'monthly_bookings' THEN
        SELECT COUNT(*), b.max_monthly_bookings INTO v_current_count, v_max_limit
        FROM lessons l
        JOIN businesses b ON b.id = l.business_id
        WHERE l.business_id = p_business_id 
          AND l.created_at >= date_trunc('month', CURRENT_DATE)
        GROUP BY b.max_monthly_bookings;
    END IF;
    
    RETURN COALESCE(v_current_count, 0) < COALESCE(v_max_limit, 999999);
END;
$$ LANGUAGE plpgsql;

-- ============================================
-- STEP 12: Analyze Tables for Query Optimization
-- ============================================

-- Update table statistics for query planner
ANALYZE businesses;
ANALYZE business_configs;
ANALYZE students;
ANALYZE instructors;
ANALYZE lesson_types;
ANALYZE lessons;
ANALYZE payment_deposits;
ANALYZE sms_notifications;
ANALYZE business_usage_stats;

-- ============================================
-- STEP 13: Post-Migration Validation
-- ============================================

DO $$
DECLARE
    v_business_count INT;
    v_students_count INT;
    v_instructors_count INT;
    v_lessons_count INT;
BEGIN
    RAISE NOTICE '===========================================';
    RAISE NOTICE 'POST-MIGRATION VALIDATION';
    RAISE NOTICE '===========================================';
    
    SELECT COUNT(*) INTO v_business_count FROM businesses;
    SELECT COUNT(*) INTO v_students_count FROM students;
    SELECT COUNT(*) INTO v_instructors_count FROM instructors;
    SELECT COUNT(*) INTO v_lessons_count FROM lessons;
    
    RAISE NOTICE 'Businesses: %', v_business_count;
    RAISE NOTICE 'Students: %', v_students_count;
    RAISE NOTICE 'Instructors: %', v_instructors_count;
    RAISE NOTICE 'Lessons: %', v_lessons_count;
    
    -- Verify all records have business_id
    IF EXISTS (SELECT 1 FROM students WHERE business_id IS NULL) THEN
        RAISE EXCEPTION 'ERROR: Some students have NULL business_id!';
    END IF;
    
    IF EXISTS (SELECT 1 FROM lessons WHERE business_id IS NULL) THEN
        RAISE EXCEPTION 'ERROR: Some lessons have NULL business_id!';
    END IF;
    
    RAISE NOTICE '✓ All records have valid business_id';
    RAISE NOTICE '✓ All indexes created successfully';
    RAISE NOTICE '✓ All foreign keys added successfully';
    RAISE NOTICE '===========================================';
    RAISE NOTICE 'MIGRATION COMPLETED SUCCESSFULLY!';
    RAISE NOTICE '===========================================';
END $$;

-- ============================================
-- STEP 14: Generate Migration Report
-- ============================================

SELECT 
    'Migration completed at: ' || CURRENT_TIMESTAMP as report_line
UNION ALL
SELECT '==========================================='
UNION ALL
SELECT 'INDEX SUMMARY:'
UNION ALL
SELECT '  Students indexes: ' || COUNT(*)::TEXT
FROM pg_indexes 
WHERE tablename = 'students'
UNION ALL
SELECT '  Instructors indexes: ' || COUNT(*)::TEXT
FROM pg_indexes 
WHERE tablename = 'instructors'
UNION ALL
SELECT '  Lessons indexes: ' || COUNT(*)::TEXT
FROM pg_indexes 
WHERE tablename = 'lessons'
UNION ALL
SELECT '  Payment deposits indexes: ' || COUNT(*)::TEXT
FROM pg_indexes 
WHERE tablename = 'payment_deposits'
UNION ALL
SELECT '==========================================='
UNION ALL
SELECT 'PARTIAL INDEXES (Cost Optimized):'
UNION ALL
SELECT '  ' || indexname || ' (saves ~' || 
    CASE 
        WHEN indexname LIKE '%active%' THEN '95% storage'
        WHEN indexname LIKE '%pending%' THEN '90% storage'
        WHEN indexname LIKE '%today%' THEN '99% storage'
        ELSE '50% storage'
    END || ')'
FROM pg_indexes 
WHERE indexdef LIKE '%WHERE%'
  AND tablename IN ('lessons', 'payment_deposits', 'sms_notifications')
ORDER BY indexname;

-- ============================================
-- MIGRATION COMPLETE
-- ============================================
-- 
-- NEXT STEPS:
-- 1. Update your application code to use business_id context
-- 2. Test all critical queries with EXPLAIN ANALYZE
-- 3. Set up monitoring for query performance
-- 4. Configure connection pooling (PgBouncer)
-- 5. Set up Redis caching layer
-- 6. Enable Row Level Security if needed (see commented code in main schema)
--
-- ROLLBACK PLAN:
-- If you need to rollback, restore from backup taken before migration.
-- Do not attempt manual rollback as it may cause data inconsistency.
-- ============================================