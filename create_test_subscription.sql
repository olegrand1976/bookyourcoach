-- Script pour créer une structure de test d'abonnement
-- À exécuter sur votre base de données locale

-- Variables (à adapter selon votre base de données)
SET @club_id = (SELECT c.id FROM clubs c JOIN users u ON u.id IN (SELECT cu.user_id FROM club_user cu WHERE cu.club_id = c.id) WHERE u.email = 'b.murgo1976@gmail.com' LIMIT 1);
SET @student_user_id = (SELECT id FROM users WHERE email = 'test.student@example.com' LIMIT 1);
SET @teacher_user_id = (SELECT id FROM users WHERE email = 'test.teacher@example.com' LIMIT 1);

-- Si les utilisateurs n'existent pas, les créer
INSERT IGNORE INTO users (name, email, password, role, email_verified_at, created_at, updated_at)
VALUES 
('Test Student', 'test.student@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', NOW(), NOW(), NOW()),
('Test Teacher', 'test.teacher@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NOW(), NOW(), NOW());

-- Récupérer les IDs après création
SET @student_user_id = (SELECT id FROM users WHERE email = 'test.student@example.com' LIMIT 1);
SET @teacher_user_id = (SELECT id FROM users WHERE email = 'test.teacher@example.com' LIMIT 1);

-- Créer l'élève
INSERT IGNORE INTO students (user_id, club_id, is_active, created_at, updated_at)
VALUES (@student_user_id, @club_id, 1, NOW(), NOW());

SET @student_id = (SELECT id FROM students WHERE user_id = @student_user_id LIMIT 1);

-- Créer l'enseignant
INSERT IGNORE INTO teachers (user_id, club_id, specialties, is_active, created_at, updated_at)
VALUES (@teacher_user_id, @club_id, '["test"]', 1, NOW(), NOW());

SET @teacher_id = (SELECT id FROM teachers WHERE user_id = @teacher_user_id LIMIT 1);

-- Créer un lieu si nécessaire
INSERT IGNORE INTO locations (club_id, name, address, city, postal_code, is_active, created_at, updated_at)
VALUES (@club_id, 'Test Location', '123 Test Street', 'Test City', '12345', 1, NOW(), NOW());

SET @location_id = (SELECT id FROM locations WHERE club_id = @club_id AND name = 'Test Location' LIMIT 1);

-- Récupérer un type de cours existant
SET @course_type_id = (SELECT id FROM course_types WHERE (club_id = @club_id OR club_id IS NULL) AND is_active = 1 LIMIT 1);

-- Créer un modèle d'abonnement de test
INSERT INTO subscription_templates (club_id, model_number, name, total_lessons, price, validity_months, is_active, created_at, updated_at)
VALUES (@club_id, CONCAT('TEST-', UNIX_TIMESTAMP()), 'Abonnement Test 10 cours', 10, 150.00, 6, 1, NOW(), NOW());

SET @template_id = LAST_INSERT_ID();

-- Associer le type de cours au template
INSERT INTO subscription_template_course_types (subscription_template_id, course_type_id, created_at, updated_at)
VALUES (@template_id, @course_type_id, NOW(), NOW());

-- Créer un abonnement
INSERT INTO subscriptions (club_id, subscription_template_id, subscription_number, total_available_lessons, validity_months, is_family_shared, max_family_members, created_at, updated_at)
VALUES (@club_id, @template_id, CONCAT('SUB-TEST-', UNIX_TIMESTAMP()), 10, 6, 0, 1, NOW(), NOW());

SET @subscription_id = LAST_INSERT_ID();

-- Créer une instance d'abonnement (démarrée il y a 2 mois, expire dans 4 mois)
INSERT INTO subscription_instances (subscription_id, lessons_used, started_at, expires_at, status, created_at, updated_at)
VALUES (@subscription_id, 0, DATE_SUB(NOW(), INTERVAL 2 MONTH), DATE_ADD(NOW(), INTERVAL 4 MONTH), 'active', NOW(), NOW());

SET @instance_id = LAST_INSERT_ID();

-- Associer l'élève à l'instance
INSERT INTO subscription_instance_students (subscription_instance_id, student_id, created_at, updated_at)
VALUES (@instance_id, @student_id, NOW(), NOW());

-- Créer 8 cours de test (7 confirmés/complétés + 1 annulé)
-- Cours 1 - Confirmé (il y a 50 jours)
INSERT INTO lessons (club_id, teacher_id, student_id, course_type_id, location_id, start_time, end_time, status, price, notes, created_at, updated_at)
VALUES (@club_id, @teacher_id, @student_id, @course_type_id, @location_id, 
        DATE_SUB(NOW(), INTERVAL 50 DAY) + INTERVAL 10 HOUR, 
        DATE_SUB(NOW(), INTERVAL 50 DAY) + INTERVAL 11 HOUR, 
        'confirmed', 15.00, 'Cours de test 1', NOW(), NOW());
SET @lesson1_id = LAST_INSERT_ID();

-- Cours 2 - Confirmé (il y a 43 jours)
INSERT INTO lessons (club_id, teacher_id, student_id, course_type_id, location_id, start_time, end_time, status, price, notes, created_at, updated_at)
VALUES (@club_id, @teacher_id, @student_id, @course_type_id, @location_id, 
        DATE_SUB(NOW(), INTERVAL 43 DAY) + INTERVAL 10 HOUR, 
        DATE_SUB(NOW(), INTERVAL 43 DAY) + INTERVAL 11 HOUR, 
        'confirmed', 15.00, 'Cours de test 2', NOW(), NOW());
SET @lesson2_id = LAST_INSERT_ID();

-- Cours 3 - Complété (il y a 36 jours)
INSERT INTO lessons (club_id, teacher_id, student_id, course_type_id, location_id, start_time, end_time, status, price, notes, created_at, updated_at)
VALUES (@club_id, @teacher_id, @student_id, @course_type_id, @location_id, 
        DATE_SUB(NOW(), INTERVAL 36 DAY) + INTERVAL 10 HOUR, 
        DATE_SUB(NOW(), INTERVAL 36 DAY) + INTERVAL 11 HOUR, 
        'completed', 15.00, 'Cours de test 3', NOW(), NOW());
SET @lesson3_id = LAST_INSERT_ID();

-- Cours 4 - Confirmé (il y a 29 jours)
INSERT INTO lessons (club_id, teacher_id, student_id, course_type_id, location_id, start_time, end_time, status, price, notes, created_at, updated_at)
VALUES (@club_id, @teacher_id, @student_id, @course_type_id, @location_id, 
        DATE_SUB(NOW(), INTERVAL 29 DAY) + INTERVAL 10 HOUR, 
        DATE_SUB(NOW(), INTERVAL 29 DAY) + INTERVAL 11 HOUR, 
        'confirmed', 15.00, 'Cours de test 4', NOW(), NOW());
SET @lesson4_id = LAST_INSERT_ID();

-- Cours 5 - ANNULÉ (il y a 22 jours) - NE DOIT PAS COMPTER
INSERT INTO lessons (club_id, teacher_id, student_id, course_type_id, location_id, start_time, end_time, status, price, notes, created_at, updated_at)
VALUES (@club_id, @teacher_id, @student_id, @course_type_id, @location_id, 
        DATE_SUB(NOW(), INTERVAL 22 DAY) + INTERVAL 10 HOUR, 
        DATE_SUB(NOW(), INTERVAL 22 DAY) + INTERVAL 11 HOUR, 
        'cancelled', 15.00, 'Cours de test 5 - ANNULÉ', NOW(), NOW());
SET @lesson5_id = LAST_INSERT_ID();

-- Cours 6 - Complété (il y a 15 jours)
INSERT INTO lessons (club_id, teacher_id, student_id, course_type_id, location_id, start_time, end_time, status, price, notes, created_at, updated_at)
VALUES (@club_id, @teacher_id, @student_id, @course_type_id, @location_id, 
        DATE_SUB(NOW(), INTERVAL 15 DAY) + INTERVAL 10 HOUR, 
        DATE_SUB(NOW(), INTERVAL 15 DAY) + INTERVAL 11 HOUR, 
        'completed', 15.00, 'Cours de test 6', NOW(), NOW());
SET @lesson6_id = LAST_INSERT_ID();

-- Cours 7 - Confirmé (il y a 8 jours)
INSERT INTO lessons (club_id, teacher_id, student_id, course_type_id, location_id, start_time, end_time, status, price, notes, created_at, updated_at)
VALUES (@club_id, @teacher_id, @student_id, @course_type_id, @location_id, 
        DATE_SUB(NOW(), INTERVAL 8 DAY) + INTERVAL 10 HOUR, 
        DATE_SUB(NOW(), INTERVAL 8 DAY) + INTERVAL 11 HOUR, 
        'confirmed', 15.00, 'Cours de test 7', NOW(), NOW());
SET @lesson7_id = LAST_INSERT_ID();

-- Cours 8 - Confirmé (il y a 1 jour)
INSERT INTO lessons (club_id, teacher_id, student_id, course_type_id, location_id, start_time, end_time, status, price, notes, created_at, updated_at)
VALUES (@club_id, @teacher_id, @student_id, @course_type_id, @location_id, 
        DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, 
        DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 11 HOUR, 
        'confirmed', 15.00, 'Cours de test 8', NOW(), NOW());
SET @lesson8_id = LAST_INSERT_ID();

-- Lier tous les cours à l'instance d'abonnement
INSERT INTO subscription_lessons (subscription_instance_id, lesson_id, created_at, updated_at)
VALUES 
(@instance_id, @lesson1_id, NOW(), NOW()),
(@instance_id, @lesson2_id, NOW(), NOW()),
(@instance_id, @lesson3_id, NOW(), NOW()),
(@instance_id, @lesson4_id, NOW(), NOW()),
(@instance_id, @lesson5_id, NOW(), NOW()),
(@instance_id, @lesson6_id, NOW(), NOW()),
(@instance_id, @lesson7_id, NOW(), NOW()),
(@instance_id, @lesson8_id, NOW(), NOW());

-- METTRE VOLONTAIREMENT UN MAUVAIS COMPTEUR POUR TESTER LE RECALCUL
-- On met 3 alors qu'il devrait être 7 (8 cours dont 1 annulé)
UPDATE subscription_instances SET lessons_used = 3 WHERE id = @instance_id;

-- Afficher le résumé
SELECT 
    '=== STRUCTURE DE TEST CRÉÉE ===' as Info,
    @club_id as club_id,
    @student_id as student_id,
    @teacher_id as teacher_id,
    @subscription_id as subscription_id,
    @instance_id as instance_id,
    'SUB-TEST-*' as subscription_number,
    '10 cours' as total_cours,
    '8 cours créés' as cours_crees,
    '7 comptés + 1 annulé' as repartition,
    '3 (FAUX)' as compteur_actuel,
    '7 (ATTENDU)' as compteur_attendu,
    '+4' as difference;

SELECT 
    '=== COURS CRÉÉS ===' as Info,
    l.id,
    l.start_time,
    l.status,
    CASE WHEN l.status = 'cancelled' THEN '❌ NE COMPTE PAS' ELSE '✅ COMPTE' END as comptage
FROM lessons l
WHERE l.id IN (@lesson1_id, @lesson2_id, @lesson3_id, @lesson4_id, @lesson5_id, @lesson6_id, @lesson7_id, @lesson8_id)
ORDER BY l.start_time;

SELECT 
    '=== ABONNEMENT ===' as Info,
    si.id as instance_id,
    si.subscription_id,
    si.lessons_used as compteur_actuel,
    COUNT(sl.id) as cours_lies_total,
    SUM(CASE WHEN l.status != 'cancelled' THEN 1 ELSE 0 END) as cours_lies_comptables,
    si.started_at as debut,
    si.expires_at as expiration,
    si.status
FROM subscription_instances si
LEFT JOIN subscription_lessons sl ON sl.subscription_instance_id = si.id
LEFT JOIN lessons l ON sl.lesson_id = l.id
WHERE si.id = @instance_id
GROUP BY si.id;

