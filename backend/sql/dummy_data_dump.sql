-- ============================================
-- DUMMY DATA FOR TESTING
-- Multi-Tenant Driving School Database
-- ============================================
-- Run this after running the migration script
-- This creates 3 test businesses with complete data
-- ============================================

-- ============================================
-- 1. CREATE TEST BUSINESSES
-- ============================================

-- Business 1: Main Driving School (Already exists from migration)
UPDATE businesses 
SET business_name = 'Sydney Driving School',
    owner_name = 'John Smith',
    owner_email = 'john@sydneydriving.com',
    owner_phone = '+61412345678',
    address_line1 = '123 George Street',
    city = 'Sydney',
    state = 'NSW',
    postal_code = '2000'
WHERE subdomain = 'main';

-- Business 2: Acme Driving
INSERT INTO businesses (
    business_name, subdomain, owner_name, owner_email, owner_phone,
    address_line1, city, state, postal_code,
    status, plan, max_instructors, max_students, max_monthly_bookings
) VALUES (
    'Acme Driving Academy',
    'acme-driving',
    'Sarah Johnson',
    'sarah@acmedriving.com',
    '+61423456789',
    '456 Pitt Street',
    'Sydney',
    'NSW',
    '2000',
    'active',
    'pro',
    20,
    2000,
    1000
);

-- Business 3: City Driving School
INSERT INTO businesses (
    business_name, subdomain, owner_name, owner_email, owner_phone,
    address_line1, city, state, postal_code,
    status, plan, max_instructors, max_students, max_monthly_bookings
) VALUES (
    'City Driving School',
    'city-school',
    'Michael Brown',
    'michael@citydriving.com',
    '+61434567890',
    '789 Market Street',
    'Melbourne',
    'VIC',
    '3000',
    'active',
    'basic',
    10,
    1000,
    500
);

-- ============================================
-- 2. CREATE LESSON TYPES FOR EACH BUSINESS
-- ============================================

-- Sydney Driving School (main) - Lesson Types
INSERT INTO lesson_types (business_id, name, description, duration_minutes, price, color_code, display_order, is_active)
SELECT 
    b.id,
    'Standard Lesson',
    'Regular 1-hour driving lesson',
    60,
    75.00,
    '#3B82F6',
    1,
    true
FROM businesses b WHERE b.subdomain = 'main'
UNION ALL
SELECT 
    b.id,
    'Extended Lesson',
    '90-minute intensive lesson',
    90,
    105.00,
    '#8B5CF6',
    2,
    true
FROM businesses b WHERE b.subdomain = 'main'
UNION ALL
SELECT 
    b.id,
    'Highway Driving',
    'Highway and freeway practice',
    60,
    85.00,
    '#EF4444',
    3,
    true
FROM businesses b WHERE b.subdomain = 'main'
UNION ALL
SELECT 
    b.id,
    'Test Preparation',
    'Pre-test preparation lesson',
    60,
    90.00,
    '#F59E0B',
    4,
    true
FROM businesses b WHERE b.subdomain = 'main';

-- Acme Driving Academy - Lesson Types
INSERT INTO lesson_types (business_id, name, description, duration_minutes, price, color_code, display_order, is_active)
SELECT 
    b.id,
    'Beginner Package',
    'For new learners',
    60,
    70.00,
    '#10B981',
    1,
    true
FROM businesses b WHERE b.subdomain = 'acme-driving'
UNION ALL
SELECT 
    b.id,
    'Advanced Package',
    'For experienced learners',
    60,
    80.00,
    '#6366F1',
    2,
    true
FROM businesses b WHERE b.subdomain = 'acme-driving'
UNION ALL
SELECT 
    b.id,
    'Night Driving',
    'Night time driving practice',
    60,
    95.00,
    '#EC4899',
    3,
    true
FROM businesses b WHERE b.subdomain = 'acme-driving';

-- City Driving School - Lesson Types
INSERT INTO lesson_types (business_id, name, description, duration_minutes, price, color_code, display_order, is_active)
SELECT 
    b.id,
    'City Basics',
    'Urban driving fundamentals',
    60,
    65.00,
    '#14B8A6',
    1,
    true
FROM businesses b WHERE b.subdomain = 'city-school'
UNION ALL
SELECT 
    b.id,
    'Parking Mastery',
    'Parallel and reverse parking',
    60,
    70.00,
    '#F97316',
    2,
    true
FROM businesses b WHERE b.subdomain = 'city-school';

-- ============================================
-- 3. CREATE INSTRUCTORS FOR EACH BUSINESS
-- ============================================

-- Sydney Driving School - Instructors
INSERT INTO instructors (business_id, name, email, phone, max_hours_per_week, bio, is_active)
SELECT 
    b.id,
    'David Wilson',
    'david@sydneydriving.com',
    '+61411222333',
    40,
    '15 years of teaching experience. Patient and friendly instructor.',
    true
FROM businesses b WHERE b.subdomain = 'main'
UNION ALL
SELECT 
    b.id,
    'Emma Thompson',
    'emma@sydneydriving.com',
    '+61411222444',
    35,
    'Specializes in nervous drivers. Calm and supportive approach.',
    true
FROM businesses b WHERE b.subdomain = 'main'
UNION ALL
SELECT 
    b.id,
    'James Lee',
    'james@sydneydriving.com',
    '+61411222555',
    40,
    'Expert in test preparation. High pass rate.',
    true
FROM businesses b WHERE b.subdomain = 'main';

-- Acme Driving Academy - Instructors
INSERT INTO instructors (business_id, name, email, phone, max_hours_per_week, bio, is_active)
SELECT 
    b.id,
    'Lisa Chen',
    'lisa@acmedriving.com',
    '+61422333444',
    40,
    'Young and energetic instructor. Great with teenagers.',
    true
FROM businesses b WHERE b.subdomain = 'acme-driving'
UNION ALL
SELECT 
    b.id,
    'Robert Martinez',
    'robert@acmedriving.com',
    '+61422333555',
    30,
    'Bilingual instructor (English/Spanish).',
    true
FROM businesses b WHERE b.subdomain = 'acme-driving';

-- City Driving School - Instructors
INSERT INTO instructors (business_id, name, email, phone, max_hours_per_week, bio, is_active)
SELECT 
    b.id,
    'Patricia Green',
    'patricia@citydriving.com',
    '+61433444555',
    40,
    '20 years experience. Former driving examiner.',
    true
FROM businesses b WHERE b.subdomain = 'city-school'
UNION ALL
SELECT 
    b.id,
    'Tom Anderson',
    'tom@citydriving.com',
    '+61433444666',
    25,
    'Part-time instructor. Weekend specialist.',
    true
FROM businesses b WHERE b.subdomain = 'city-school';

-- ============================================
-- 4. CREATE TEST STUDENTS FOR EACH BUSINESS
-- ============================================

-- Sydney Driving School - Students
INSERT INTO students (business_id, phone, name, email, otp_verified)
SELECT 
    b.id,
    '+61400111222',
    'Alice Student',
    'alice@example.com',
    true
FROM businesses b WHERE b.subdomain = 'main'
UNION ALL
SELECT 
    b.id,
    '+61400111333',
    'Bob Student',
    'bob@example.com',
    true
FROM businesses b WHERE b.subdomain = 'main'
UNION ALL
SELECT 
    b.id,
    '+61400111444',
    'Carol Student',
    'carol@example.com',
    true
FROM businesses b WHERE b.subdomain = 'main';

-- Acme Driving Academy - Students
INSERT INTO students (business_id, phone, name, email, otp_verified)
SELECT 
    b.id,
    '+61400222333',
    'Daniel Student',
    'daniel@example.com',
    true
FROM businesses b WHERE b.subdomain = 'acme-driving'
UNION ALL
SELECT 
    b.id,
    '+61400222444',
    'Eva Student',
    'eva@example.com',
    true
FROM businesses b WHERE b.subdomain = 'acme-driving';

-- City Driving School - Students
INSERT INTO students (business_id, phone, name, email, otp_verified)
SELECT 
    b.id,
    '+61400333444',
    'Frank Student',
    'frank@example.com',
    true
FROM businesses b WHERE b.subdomain = 'city-school'
UNION ALL
SELECT 
    b.id,
    '+61400333555',
    'Grace Student',
    'grace@example.com',
    true
FROM businesses b WHERE b.subdomain = 'city-school';

-- ============================================
-- 5. CREATE SAMPLE LESSONS
-- ============================================

-- Sydney Driving School - Lessons (next week)
INSERT INTO lessons (business_id, lesson_type_id, instructor_id, student_id, scheduled_at, status, deposit_paid)
SELECT 
    b.id,
    lt.id,
    i.id,
    s.id,
    CURRENT_DATE + INTERVAL '2 days' + INTERVAL '10 hours',
    'confirmed',
    true
FROM businesses b
JOIN lesson_types lt ON lt.business_id = b.id AND lt.name = 'Standard Lesson'
JOIN instructors i ON i.business_id = b.id AND i.name = 'David Wilson'
JOIN students s ON s.business_id = b.id AND s.name = 'Alice Student'
WHERE b.subdomain = 'main'
UNION ALL
SELECT 
    b.id,
    lt.id,
    i.id,
    s.id,
    CURRENT_DATE + INTERVAL '2 days' + INTERVAL '14 hours',
    'confirmed',
    true
FROM businesses b
JOIN lesson_types lt ON lt.business_id = b.id AND lt.name = 'Highway Driving'
JOIN instructors i ON i.business_id = b.id AND i.name = 'Emma Thompson'
JOIN students s ON s.business_id = b.id AND s.name = 'Bob Student'
WHERE b.subdomain = 'main'
UNION ALL
SELECT 
    b.id,
    lt.id,
    i.id,
    s.id,
    CURRENT_DATE + INTERVAL '3 days' + INTERVAL '9 hours',
    'pending_deposit',
    false
FROM businesses b
JOIN lesson_types lt ON lt.business_id = b.id AND lt.name = 'Test Preparation'
JOIN instructors i ON i.business_id = b.id AND i.name = 'James Lee'
JOIN students s ON s.business_id = b.id AND s.name = 'Carol Student'
WHERE b.subdomain = 'main';

-- Acme Driving Academy - Lessons
INSERT INTO lessons (business_id, lesson_type_id, instructor_id, student_id, scheduled_at, status, deposit_paid)
SELECT 
    b.id,
    lt.id,
    i.id,
    s.id,
    CURRENT_DATE + INTERVAL '1 day' + INTERVAL '11 hours',
    'confirmed',
    true
FROM businesses b
JOIN lesson_types lt ON lt.business_id = b.id AND lt.name = 'Beginner Package'
JOIN instructors i ON i.business_id = b.id AND i.name = 'Lisa Chen'
JOIN students s ON s.business_id = b.id AND s.name = 'Daniel Student'
WHERE b.subdomain = 'acme-driving'
UNION ALL
SELECT 
    b.id,
    lt.id,
    i.id,
    s.id,
    CURRENT_DATE + INTERVAL '1 day' + INTERVAL '15 hours',
    'pending_deposit',
    false
FROM businesses b
JOIN lesson_types lt ON lt.business_id = b.id AND lt.name = 'Night Driving'
JOIN instructors i ON i.business_id = b.id AND i.name = 'Robert Martinez'
JOIN students s ON s.business_id = b.id AND s.name = 'Eva Student'
WHERE b.subdomain = 'acme-driving';

-- City Driving School - Lessons
INSERT INTO lessons (business_id, lesson_type_id, instructor_id, student_id, scheduled_at, status, deposit_paid)
SELECT 
    b.id,
    lt.id,
    i.id,
    s.id,
    CURRENT_DATE + INTERVAL '2 days' + INTERVAL '13 hours',
    'confirmed',
    true
FROM businesses b
JOIN lesson_types lt ON lt.business_id = b.id AND lt.name = 'City Basics'
JOIN instructors i ON i.business_id = b.id AND i.name = 'Patricia Green'
JOIN students s ON s.business_id = b.id AND s.name = 'Frank Student'
WHERE b.subdomain = 'city-school';

-- ============================================
-- 6. CREATE PAYMENT DEPOSITS
-- ============================================

-- Create deposits for confirmed lessons
INSERT INTO payment_deposits (business_id, lesson_id, amount, payid_reference, status, verified_at, currency)
SELECT 
    l.business_id,
    l.id,
    (lt.price * 0.5)::DECIMAL(10,2),
    'PAY' || LPAD((RANDOM() * 999999)::INT::TEXT, 6, '0'),
    'confirmed',
    l.created_at + INTERVAL '5 minutes',
    'AUD'
FROM lessons l
JOIN lesson_types lt ON l.lesson_type_id = lt.id
WHERE l.status = 'confirmed';

-- Create pending deposits for pending lessons
INSERT INTO payment_deposits (business_id, lesson_id, amount, payid_reference, status, currency)
SELECT 
    l.business_id,
    l.id,
    (lt.price * 0.5)::DECIMAL(10,2),
    'PAY' || LPAD((RANDOM() * 999999)::INT::TEXT, 6, '0'),
    'pending',
    'AUD'
FROM lessons l
JOIN lesson_types lt ON l.lesson_type_id = lt.id
WHERE l.status = 'pending_deposit';

-- ============================================
-- 7. VERIFY DATA
-- ============================================

-- Show summary
DO $$
DECLARE
    v_businesses INT;
    v_instructors INT;
    v_students INT;
    v_lesson_types INT;
    v_lessons INT;
    v_deposits INT;
BEGIN
    SELECT COUNT(*) INTO v_businesses FROM businesses WHERE deleted_at IS NULL;
    SELECT COUNT(*) INTO v_instructors FROM instructors;
    SELECT COUNT(*) INTO v_students FROM students;
    SELECT COUNT(*) INTO v_lesson_types FROM lesson_types;
    SELECT COUNT(*) INTO v_lessons FROM lessons;
    SELECT COUNT(*) INTO v_deposits FROM payment_deposits;
    
    RAISE NOTICE '===========================================';
    RAISE NOTICE 'DUMMY DATA LOADED SUCCESSFULLY!';
    RAISE NOTICE '===========================================';
    RAISE NOTICE 'Businesses: %', v_businesses;
    RAISE NOTICE 'Instructors: %', v_instructors;
    RAISE NOTICE 'Students: %', v_students;
    RAISE NOTICE 'Lesson Types: %', v_lesson_types;
    RAISE NOTICE 'Lessons: %', v_lessons;
    RAISE NOTICE 'Payment Deposits: %', v_deposits;
    RAISE NOTICE '===========================================';
END $$;

-- Show business details
SELECT 
    business_name,
    subdomain,
    status,
    plan,
    (SELECT COUNT(*) FROM instructors WHERE business_id = b.id) as instructors,
    (SELECT COUNT(*) FROM students WHERE business_id = b.id) as students,
    (SELECT COUNT(*) FROM lessons WHERE business_id = b.id) as lessons
FROM businesses b
WHERE deleted_at IS NULL
ORDER BY business_name;

-- ============================================
-- TEST QUERIES
-- ============================================

-- Test 1: Get all lesson types for Sydney Driving School
SELECT 
    'TEST 1: Sydney Driving School - Lesson Types' as test_name;
    
SELECT name, duration_minutes, price 
FROM lesson_types 
WHERE business_id = (SELECT id FROM businesses WHERE subdomain = 'main')
ORDER BY display_order;

-- Test 2: Get all instructors for Acme Driving
SELECT 
    'TEST 2: Acme Driving Academy - Instructors' as test_name;
    
SELECT name, email, phone 
FROM instructors 
WHERE business_id = (SELECT id FROM businesses WHERE subdomain = 'acme-driving')
  AND is_active = true;

-- Test 3: Get upcoming lessons for each business
SELECT 
    'TEST 3: Upcoming Lessons by Business' as test_name;
    
SELECT 
    b.business_name,
    i.name as instructor,
    s.name as student,
    l.scheduled_at,
    l.status,
    lt.name as lesson_type
FROM lessons l
JOIN businesses b ON l.business_id = b.id
JOIN instructors i ON l.instructor_id = i.id
JOIN students s ON l.student_id = s.id
JOIN lesson_types lt ON l.lesson_type_id = lt.id
WHERE l.scheduled_at >= CURRENT_DATE
ORDER BY b.business_name, l.scheduled_at;

-- ============================================
-- SCRIPT COMPLETE
-- ============================================
-- 
-- To load this data:
-- psql -U postgres -p 5433 -d driving_school -f dummy_data.sql
--
-- Test credentials:
-- 
-- Business 1: main (Sydney Driving School)
--   - Student: +61400111222 (Alice)
--   - Instructor: David Wilson
--
-- Business 2: acme-driving (Acme Driving Academy)
--   - Student: +61400222333 (Daniel)
--   - Instructor: Lisa Chen
--
-- Business 3: city-school (City Driving School)
--   - Student: +61400333444 (Frank)
--   - Instructor: Patricia Green
--
-- ============================================