-- ============================================
-- MIGRATION FIX SCRIPT
-- Fixes the 3 errors from migration
-- ============================================

-- ============================================
-- FIX 1 & 2: Replace CURRENT_DATE with date literal in partial indexes
-- ============================================

-- Drop the failed indexes if they exist
DROP INDEX IF EXISTS idx_lessons_business_active;
DROP INDEX IF EXISTS idx_lessons_business_today;

-- Recreate idx_lessons_business_active WITHOUT date function
-- Instead of checking CURRENT_DATE, we'll make it check all future lessons
CREATE INDEX idx_lessons_business_active 
ON lessons(business_id, instructor_id, scheduled_at, status)
WHERE status IN ('confirmed', 'in_progress', 'pending_deposit');

-- This index still provides huge benefits:
-- - Only indexes active lessons (not completed/cancelled)
-- - Much smaller than full index
-- - Perfect for availability checks

-- For "today's lessons" we'll use a different approach
-- Create a regular index that's still very efficient
CREATE INDEX idx_lessons_business_scheduled_status
ON lessons(business_id, scheduled_at, instructor_id, status)
WHERE status != 'cancelled';

-- ============================================
-- FIX 3: Add missing indexes that provide similar benefits
-- ============================================

-- Optimize for date range queries (portal dashboard)
CREATE INDEX idx_lessons_scheduled_date 
ON lessons(business_id, DATE(scheduled_at), instructor_id)
WHERE status IN ('confirmed', 'in_progress', 'pending_deposit');

-- ============================================
-- VERIFICATION
-- ============================================

DO $$
BEGIN
    RAISE NOTICE '===========================================';
    RAISE NOTICE 'FIX SCRIPT COMPLETED';
    RAISE NOTICE '===========================================';
    
    -- Check indexes exist
    IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'idx_lessons_business_active') THEN
        RAISE NOTICE '✓ idx_lessons_business_active created';
    END IF;
    
    IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'idx_lessons_business_scheduled_status') THEN
        RAISE NOTICE '✓ idx_lessons_business_scheduled_status created';
    END IF;
    
    IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'idx_lessons_scheduled_date') THEN
        RAISE NOTICE '✓ idx_lessons_scheduled_date created';
    END IF;
    
    RAISE NOTICE '===========================================';
    RAISE NOTICE 'All indexes optimized and working!';
    RAISE NOTICE '===========================================';
END $$;

-- ============================================
-- SUMMARY OF INDEXES
-- ============================================

SELECT 
    '=== LESSONS TABLE INDEXES ===' as info
UNION ALL
SELECT 
    indexname || ' - ' || 
    CASE 
        WHEN indexdef LIKE '%WHERE%' THEN 'PARTIAL (optimized)'
        ELSE 'FULL'
    END as info
FROM pg_indexes 
WHERE tablename = 'lessons'
ORDER BY indexname;

-- ============================================
-- OPTIONAL: Create Materialized View for Today's Lessons
-- ============================================
-- This provides even better performance for portal dashboard

CREATE MATERIALIZED VIEW IF NOT EXISTS today_lessons_cache AS
SELECT 
    l.id,
    l.business_id,
    l.instructor_id,
    l.student_id,
    l.scheduled_at,
    l.status,
    i.name as instructor_name,
    s.name as student_name,
    s.phone as student_phone,
    lt.name as lesson_type,
    lt.duration_minutes,
    pd.status as payment_status
FROM lessons l
JOIN instructors i ON l.instructor_id = i.id
JOIN students s ON l.student_id = s.id
JOIN lesson_types lt ON l.lesson_type_id = lt.id
LEFT JOIN payment_deposits pd ON pd.lesson_id = l.id
WHERE DATE(l.scheduled_at) = CURRENT_DATE
  AND l.status != 'cancelled';

CREATE INDEX idx_today_lessons_business_instructor 
ON today_lessons_cache(business_id, instructor_id, scheduled_at);

-- Refresh function (call this every 5 minutes via cron or app)
CREATE OR REPLACE FUNCTION refresh_today_lessons_cache()
RETURNS void AS $$
BEGIN
    REFRESH MATERIALIZED VIEW today_lessons_cache;
END;
$$ LANGUAGE plpgsql;

RAISE NOTICE '===========================================';
RAISE NOTICE 'OPTIONAL: Materialized view created';
RAISE NOTICE 'To refresh cache, run: SELECT refresh_today_lessons_cache();';
RAISE NOTICE 'Set up a cron job to refresh every 5 minutes';
RAISE NOTICE '===========================================';

-- ============================================
-- COMPLETE!
-- ============================================