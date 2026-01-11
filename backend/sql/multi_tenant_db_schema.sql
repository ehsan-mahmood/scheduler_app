-- ============================================
-- Multi-Tenant Driving School Database
-- PostgreSQL Database Schema
-- ============================================
-- Multi-business platform with optimized indexing
-- Cost-optimized for AWS EC2/RDS deployment
-- ============================================

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm"; -- For text search optimization

-- ============================================
-- 1. BUSINESSES TABLE (Core Multi-Tenant Table)
-- ============================================
CREATE TABLE businesses (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- Business Identity
    business_name TEXT NOT NULL,
    subdomain VARCHAR(63) UNIQUE NOT NULL, -- e.g., 'acme-driving'
    custom_domain VARCHAR(255) UNIQUE, -- Optional custom domain
    
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
    
    -- Resource Limits (for cost control)
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
    deleted_at TIMESTAMP -- Soft delete support
);

-- Indexes for businesses
CREATE INDEX idx_businesses_subdomain ON businesses(subdomain) WHERE deleted_at IS NULL;
CREATE INDEX idx_businesses_status ON businesses(status) WHERE deleted_at IS NULL;
CREATE INDEX idx_businesses_plan ON businesses(plan);
CREATE INDEX idx_businesses_owner_email ON businesses(owner_email);

-- ============================================
-- 2. BUSINESS CONFIGS TABLE (Flexible Key-Value Store)
-- ============================================
CREATE TABLE business_configs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    
    config_key VARCHAR(100) NOT NULL, -- e.g., 'sms_enabled', 'booking_buffer_minutes'
    config_value TEXT NOT NULL, -- Store as JSON string for complex values
    
    data_type VARCHAR(20) DEFAULT 'string' CHECK (data_type IN ('string', 'number', 'boolean', 'json')),
    description TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(business_id, config_key) -- One config per business
);

CREATE INDEX idx_business_configs_business ON business_configs(business_id);
CREATE INDEX idx_business_configs_key ON business_configs(business_id, config_key);

-- Insert default configs for new businesses (via trigger)
CREATE OR REPLACE FUNCTION create_default_business_configs()
RETURNS TRIGGER AS $$
BEGIN
    -- Default configurations
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

-- ============================================
-- 3. STUDENTS TABLE (Multi-Tenant)
-- ============================================
CREATE TABLE students (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    
    name TEXT,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    
    -- OTP for verification
    otp_verified BOOLEAN DEFAULT false,
    otp_code VARCHAR(10),
    otp_expires_at TIMESTAMP,
    
    -- Additional fields
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Phone is unique within a business, not globally
    UNIQUE(business_id, phone)
);

-- Optimized indexes
CREATE INDEX idx_students_business ON students(business_id);
CREATE INDEX idx_students_business_phone ON students(business_id, phone);
CREATE INDEX idx_students_phone_lookup ON students(phone); -- For cross-business phone lookup if needed

-- ============================================
-- 4. INSTRUCTORS TABLE (Multi-Tenant)
-- ============================================
CREATE TABLE instructors (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    
    name TEXT NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    
    -- Availability
    max_hours_per_week INT DEFAULT 40,
    is_active BOOLEAN DEFAULT true,
    
    -- Additional fields
    bio TEXT,
    specialties TEXT[], -- Array of specialties
    hire_date DATE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Email unique within business
    UNIQUE(business_id, email)
);

-- Optimized indexes
CREATE INDEX idx_instructors_business ON instructors(business_id);
CREATE INDEX idx_instructors_business_active ON instructors(business_id, is_active) WHERE is_active = true;
CREATE INDEX idx_instructors_email_lookup ON instructors(email);

-- ============================================
-- 5. LESSON TYPES TABLE (Multi-Tenant)
-- ============================================
CREATE TABLE lesson_types (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    
    name TEXT NOT NULL,
    description TEXT,
    duration_minutes INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    
    -- Additional fields
    color_code VARCHAR(7), -- Hex color for UI
    is_active BOOLEAN DEFAULT true,
    display_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optimized indexes
CREATE INDEX idx_lesson_types_business ON lesson_types(business_id);
CREATE INDEX idx_lesson_types_business_active ON lesson_types(business_id, is_active) WHERE is_active = true;

-- ============================================
-- 6. LESSONS TABLE (Central Scheduling - Multi-Tenant)
-- ============================================
CREATE TABLE lessons (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    
    lesson_type_id UUID NOT NULL REFERENCES lesson_types(id) ON DELETE RESTRICT,
    instructor_id UUID NOT NULL REFERENCES instructors(id) ON DELETE RESTRICT,
    student_id UUID NOT NULL REFERENCES students(id) ON DELETE RESTRICT,
    
    scheduled_at TIMESTAMP NOT NULL,
    status VARCHAR(20) DEFAULT 'pending_deposit' CHECK (status IN ('pending_deposit', 'confirmed', 'in_progress', 'completed', 'cancelled')),
    
    -- Payment tracking
    deposit_paid BOOLEAN DEFAULT false,
    
    -- Lesson OTP (for starting lesson)
    lesson_otp VARCHAR(10),
    lesson_otp_expires_at TIMESTAMP,
    
    -- Additional fields
    notes TEXT,
    cancellation_reason TEXT,
    cancelled_at TIMESTAMP,
    completed_at TIMESTAMP,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- CRITICAL: Multi-Tenant Optimized Indexes for Lessons
-- ============================================

-- Primary business-scoped indexes
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

-- ⭐ PARTIAL INDEX: Active/Future lessons only (HUGE cost saver)
-- This index is smaller and faster because it only includes relevant lessons
CREATE INDEX idx_lessons_business_active 
ON lessons(business_id, instructor_id, scheduled_at, status)
WHERE status IN ('confirmed', 'in_progress', 'pending_deposit') 
  AND scheduled_at >= CURRENT_DATE - INTERVAL '7 days';

-- Portal dashboard optimization (today's lessons)
CREATE INDEX idx_lessons_business_today 
ON lessons(business_id, instructor_id, scheduled_at)
WHERE scheduled_at >= CURRENT_DATE 
  AND scheduled_at < CURRENT_DATE + INTERVAL '1 day'
  AND status != 'cancelled';

-- Scheduled time range queries
CREATE INDEX idx_lessons_scheduled_at ON lessons(scheduled_at) 
WHERE status IN ('confirmed', 'in_progress');

-- ============================================
-- 7. PAYMENT DEPOSITS TABLE (Multi-Tenant)
-- ============================================
CREATE TABLE payment_deposits (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    lesson_id UUID NOT NULL REFERENCES lessons(id) ON DELETE CASCADE,
    
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'AUD',
    
    -- Payment method
    payment_method VARCHAR(20) DEFAULT 'payid' CHECK (payment_method IN ('payid', 'card', 'cash', 'bank_transfer')),
    payid_reference TEXT,
    transaction_id TEXT,
    
    -- Status
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'failed', 'refunded')),
    verified_at TIMESTAMP,
    refunded_at TIMESTAMP,
    
    -- Additional
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optimized indexes
CREATE INDEX idx_payment_deposits_business ON payment_deposits(business_id);
CREATE INDEX idx_payment_deposits_lesson ON payment_deposits(lesson_id);
CREATE INDEX idx_payment_deposits_business_status ON payment_deposits(business_id, status);

-- ⭐ PARTIAL INDEX: Pending payments only (for verification job)
CREATE INDEX idx_payment_deposits_pending 
ON payment_deposits(business_id, created_at DESC)
WHERE status = 'pending';

CREATE INDEX idx_payment_deposits_payid_ref ON payment_deposits(payid_reference) 
WHERE payid_reference IS NOT NULL;

-- ============================================
-- 8. SMS NOTIFICATIONS TABLE (Multi-Tenant)
-- ============================================
CREATE TABLE sms_notifications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    lesson_id UUID REFERENCES lessons(id) ON DELETE SET NULL,
    
    phone VARCHAR(20) NOT NULL,
    type VARCHAR(50) NOT NULL CHECK (type IN ('booking', 'deposit_confirmed', 'reschedule', 'cancel', 'lesson_otp', 'reminder')),
    message TEXT NOT NULL,
    
    -- Status
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'failed')),
    sent_at TIMESTAMP,
    
    -- Error handling
    error_message TEXT,
    retry_count INT DEFAULT 0,
    
    -- Cost tracking
    cost_cents INT, -- Store cost in cents
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optimized indexes
CREATE INDEX idx_sms_notifications_business ON sms_notifications(business_id);
CREATE INDEX idx_sms_notifications_lesson ON sms_notifications(lesson_id);
CREATE INDEX idx_sms_notifications_business_status ON sms_notifications(business_id, status);

-- ⭐ PARTIAL INDEX: Pending SMS only (for sending job)
CREATE INDEX idx_sms_notifications_pending 
ON sms_notifications(created_at ASC)
WHERE status = 'pending' AND retry_count < 3;

CREATE INDEX idx_sms_notifications_phone ON sms_notifications(phone);

-- ============================================
-- 9. BUSINESS USAGE STATS (Cost Monitoring)
-- ============================================
CREATE TABLE business_usage_stats (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    
    metric_name VARCHAR(50) NOT NULL, -- 'daily_bookings', 'monthly_bookings', 'sms_sent', 'storage_used'
    metric_value BIGINT NOT NULL,
    metric_unit VARCHAR(20), -- 'count', 'bytes', 'minutes'
    
    recorded_at DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(business_id, metric_name, recorded_at)
);

CREATE INDEX idx_business_usage_business_date ON business_usage_stats(business_id, recorded_at DESC);
CREATE INDEX idx_business_usage_metric ON business_usage_stats(business_id, metric_name, recorded_at DESC);

-- ============================================
-- TRIGGERS: Update updated_at timestamp
-- ============================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply to all tables
CREATE TRIGGER update_businesses_updated_at BEFORE UPDATE ON businesses
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_business_configs_updated_at BEFORE UPDATE ON business_configs
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

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
-- ROW LEVEL SECURITY (Optional but Recommended)
-- ============================================
-- Uncomment to enable database-level tenant isolation

-- ALTER TABLE students ENABLE ROW LEVEL SECURITY;
-- CREATE POLICY students_isolation_policy ON students
--     USING (business_id = current_setting('app.current_business_id')::uuid);

-- ALTER TABLE instructors ENABLE ROW LEVEL SECURITY;
-- CREATE POLICY instructors_isolation_policy ON instructors
--     USING (business_id = current_setting('app.current_business_id')::uuid);

-- ALTER TABLE lessons ENABLE ROW LEVEL SECURITY;
-- CREATE POLICY lessons_isolation_policy ON lessons
--     USING (business_id = current_setting('app.current_business_id')::uuid);

-- ALTER TABLE lesson_types ENABLE ROW LEVEL SECURITY;
-- CREATE POLICY lesson_types_isolation_policy ON lesson_types
--     USING (business_id = current_setting('app.current_business_id')::uuid);

-- ALTER TABLE payment_deposits ENABLE ROW LEVEL SECURITY;
-- CREATE POLICY payment_deposits_isolation_policy ON payment_deposits
--     USING (business_id = current_setting('app.current_business_id')::uuid);

-- ALTER TABLE sms_notifications ENABLE ROW LEVEL SECURITY;
-- CREATE POLICY sms_notifications_isolation_policy ON sms_notifications
--     USING (business_id = current_setting('app.current_business_id')::uuid);

-- ============================================
-- HELPER VIEWS (Optional - for easier queries)
-- ============================================

-- Active businesses overview
CREATE VIEW active_businesses_summary AS
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

-- ============================================
-- UTILITY FUNCTIONS
-- ============================================

-- Function to get business config value with default
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

-- ============================================
-- SAMPLE DATA (Optional - for testing)
-- ============================================

-- Insert a test business
-- INSERT INTO businesses (business_name, subdomain, owner_email, status)
-- VALUES ('Demo Driving School', 'demo', 'owner@demo.com', 'active');

-- ============================================
-- SCRIPT COMPLETE
-- ============================================
-- To run this script:
-- 1. Connect to PostgreSQL: psql -U postgres
-- 2. Create database: CREATE DATABASE driving_school_platform;
-- 3. Connect to database: \c driving_school_platform
-- 4. Run this script: \i create_database_multitenant.sql
--
-- Or from command line:
-- psql -U postgres -d driving_school_platform -f create_database_multitenant.sql
--
-- NEXT STEPS:
-- 1. Set up connection pooling (PgBouncer)
-- 2. Configure application middleware for business context
-- 3. Set up caching layer (Redis)
-- 4. Create monitoring dashboards
-- ============================================