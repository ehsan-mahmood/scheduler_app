-- ============================================
-- Configuration Tables for Generic Scheduler
-- Migration: 005_add_config_tables.sql
-- ============================================
-- Adds DB-driven configuration for:
-- - Business settings
-- - Notification channels
-- - Notification providers
-- - Notification events
-- ============================================

-- ============================================
-- 1. BUSINESS_SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS business_settings (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID REFERENCES businesses(id) ON DELETE CASCADE,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'string' CHECK (setting_type IN ('string', 'boolean', 'integer', 'json')),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(business_id, setting_key)
);

CREATE INDEX idx_business_settings_business ON business_settings(business_id);
CREATE INDEX idx_business_settings_key ON business_settings(setting_key);

-- Default settings (fail closed - OFF by default)
INSERT INTO business_settings (business_id, setting_key, setting_value, setting_type, description)
SELECT 
    id,
    'notifications_enabled',
    'false',
    'boolean',
    'Enable notifications (SMS/Email)'
FROM businesses
WHERE NOT EXISTS (
    SELECT 1 FROM business_settings 
    WHERE business_id = businesses.id AND setting_key = 'notifications_enabled'
)
ON CONFLICT DO NOTHING;

INSERT INTO business_settings (business_id, setting_key, setting_value, setting_type, description)
SELECT 
    id,
    'sms_enabled',
    'false',
    'boolean',
    'Enable SMS notifications'
FROM businesses
WHERE NOT EXISTS (
    SELECT 1 FROM business_settings 
    WHERE business_id = businesses.id AND setting_key = 'sms_enabled'
)
ON CONFLICT DO NOTHING;

INSERT INTO business_settings (business_id, setting_key, setting_value, setting_type, description)
SELECT 
    id,
    'email_enabled',
    'false',
    'boolean',
    'Enable Email notifications'
FROM businesses
WHERE NOT EXISTS (
    SELECT 1 FROM business_settings 
    WHERE business_id = businesses.id AND setting_key = 'email_enabled'
)
ON CONFLICT DO NOTHING;

INSERT INTO business_settings (business_id, setting_key, setting_value, setting_type, description)
SELECT 
    id,
    'payment_provider',
    'demo',
    'string',
    'Payment provider: demo, stripe, payid'
FROM businesses
WHERE NOT EXISTS (
    SELECT 1 FROM business_settings 
    WHERE business_id = businesses.id AND setting_key = 'payment_provider'
)
ON CONFLICT DO NOTHING;

-- ============================================
-- 2. NOTIFICATION_CHANNELS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS notification_channels (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID REFERENCES businesses(id) ON DELETE CASCADE,
    channel_type VARCHAR(20) NOT NULL CHECK (channel_type IN ('sms', 'email', 'push', 'whatsapp')),
    is_enabled BOOLEAN DEFAULT false,
    provider_name VARCHAR(50),
    config JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(business_id, channel_type)
);

CREATE INDEX idx_notification_channels_business ON notification_channels(business_id);
CREATE INDEX idx_notification_channels_type ON notification_channels(channel_type);

-- Default channels (disabled by default)
INSERT INTO notification_channels (business_id, channel_type, is_enabled, provider_name, config)
SELECT 
    id,
    'sms',
    false,
    'demo',
    '{}'::jsonb
FROM businesses
WHERE NOT EXISTS (
    SELECT 1 FROM notification_channels 
    WHERE business_id = businesses.id AND channel_type = 'sms'
)
ON CONFLICT DO NOTHING;

INSERT INTO notification_channels (business_id, channel_type, is_enabled, provider_name, config)
SELECT 
    id,
    'email',
    false,
    'demo',
    '{}'::jsonb
FROM businesses
WHERE NOT EXISTS (
    SELECT 1 FROM notification_channels 
    WHERE business_id = businesses.id AND channel_type = 'email'
)
ON CONFLICT DO NOTHING;

-- ============================================
-- 3. NOTIFICATION_PROVIDERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS notification_providers (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID REFERENCES businesses(id) ON DELETE CASCADE,
    provider_type VARCHAR(20) NOT NULL CHECK (provider_type IN ('sms', 'email')),
    provider_name VARCHAR(50) NOT NULL,
    is_active BOOLEAN DEFAULT false,
    credentials JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(business_id, provider_type, provider_name)
);

CREATE INDEX idx_notification_providers_business ON notification_providers(business_id);
CREATE INDEX idx_notification_providers_type ON notification_providers(provider_type);

-- Default demo providers (inactive by default)
INSERT INTO notification_providers (business_id, provider_type, provider_name, is_active, credentials)
SELECT 
    id,
    'sms',
    'demo',
    false,
    '{}'::jsonb
FROM businesses
WHERE NOT EXISTS (
    SELECT 1 FROM notification_providers 
    WHERE business_id = businesses.id AND provider_type = 'sms' AND provider_name = 'demo'
)
ON CONFLICT DO NOTHING;

INSERT INTO notification_providers (business_id, provider_type, provider_name, is_active, credentials)
SELECT 
    id,
    'email',
    'demo',
    false,
    '{}'::jsonb
FROM businesses
WHERE NOT EXISTS (
    SELECT 1 FROM notification_providers 
    WHERE business_id = businesses.id AND provider_type = 'email' AND provider_name = 'demo'
)
ON CONFLICT DO NOTHING;

-- ============================================
-- 4. NOTIFICATION_EVENTS TABLE (Logging)
-- ============================================
CREATE TABLE IF NOT EXISTS notification_events (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    business_id UUID REFERENCES businesses(id) ON DELETE CASCADE,
    event_type VARCHAR(50) NOT NULL,
    event_payload JSONB,
    channel_type VARCHAR(20),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'failed', 'skipped')),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notification_events_business ON notification_events(business_id);
CREATE INDEX idx_notification_events_type ON notification_events(event_type);
CREATE INDEX idx_notification_events_status ON notification_events(status);
CREATE INDEX idx_notification_events_created ON notification_events(created_at);

-- ============================================
-- TRIGGERS: Update updated_at timestamp
-- ============================================
CREATE TRIGGER update_business_settings_updated_at BEFORE UPDATE ON business_settings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_notification_channels_updated_at BEFORE UPDATE ON notification_channels
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_notification_providers_updated_at BEFORE UPDATE ON notification_providers
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================
-- SCRIPT COMPLETE
-- ============================================
-- This migration adds configuration tables for:
-- 1. Business settings (feature flags, preferences)
-- 2. Notification channels (SMS, Email, etc.)
-- 3. Notification providers (Twilio, SMTP, Demo, etc.)
-- 4. Notification events log (audit trail)
--
-- All settings default to OFF (fail closed)
-- ============================================

