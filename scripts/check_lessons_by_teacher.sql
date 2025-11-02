-- Script SQL pour vérifier les cours affectés aux enseignants

-- 1. Vue d'ensemble : Nombre de cours par enseignant
SELECT 
    t.id AS teacher_id,
    u.name AS teacher_name,
    u.email AS teacher_email,
    COUNT(l.id) AS total_lessons,
    SUM(CASE WHEN l.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_lessons,
    SUM(CASE WHEN l.status = 'pending' THEN 1 ELSE 0 END) AS pending_lessons,
    SUM(CASE WHEN l.status = 'completed' THEN 1 ELSE 0 END) AS completed_lessons,
    SUM(CASE WHEN l.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_lessons
FROM teachers t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN lessons l ON l.teacher_id = t.id
GROUP BY t.id, u.name, u.email
ORDER BY total_lessons DESC, teacher_name;

-- 2. Détail des cours par enseignant
SELECT 
    l.id AS lesson_id,
    l.teacher_id,
    u.name AS teacher_name,
    u.email AS teacher_email,
    c.name AS club_name,
    l.start_time,
    l.end_time,
    l.status,
    l.price,
    s.user_id AS student_user_id,
    su.name AS student_name
FROM lessons l
LEFT JOIN teachers t ON l.teacher_id = t.id
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN clubs c ON l.club_id = c.id
LEFT JOIN students s ON l.student_id = s.id
LEFT JOIN users su ON s.user_id = su.id
ORDER BY l.teacher_id, l.start_time;

-- 3. Enseignants sans cours
SELECT 
    t.id AS teacher_id,
    u.name AS teacher_name,
    u.email AS teacher_email
FROM teachers t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN lessons l ON l.teacher_id = t.id
WHERE l.id IS NULL
ORDER BY teacher_name;

-- 4. Cours orphelins (sans enseignant valide)
SELECT 
    l.id AS lesson_id,
    l.teacher_id,
    l.start_time,
    l.status,
    c.name AS club_name
FROM lessons l
LEFT JOIN teachers t ON l.teacher_id = t.id
LEFT JOIN clubs c ON l.club_id = c.id
WHERE t.id IS NULL OR l.teacher_id IS NULL
ORDER BY l.id;

-- 5. Cours par club et enseignant
SELECT 
    c.name AS club_name,
    u.name AS teacher_name,
    COUNT(l.id) AS lessons_count,
    AVG(l.price) AS avg_price,
    SUM(l.price) AS total_revenue
FROM lessons l
LEFT JOIN teachers t ON l.teacher_id = t.id
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN clubs c ON l.club_id = c.id
GROUP BY c.id, c.name, t.id, u.name
ORDER BY c.name, lessons_count DESC;

