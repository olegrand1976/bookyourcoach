-- Script simplifié pour créer un abonnement de test
-- Club ID: 1 (votre club principal)

SET @club_id = 1;
SET @student_id = (SELECT id FROM students WHERE club_id = @club_id LIMIT 1);
SET @teacher_id = (SELECT id FROM teachers WHERE club_id = @club_id LIMIT 1);
SET @location_id = (SELECT id FROM locations LIMIT 1);
SET @course_type_id = (SELECT id FROM course_types WHERE is_active = 1 LIMIT 1);

-- Afficher ce qu'on a trouvé
SELECT @club_id as club_id, @student_id as student_id, @teacher_id as teacher_id, @location_id as location_id, @course_type_id as course_type_id;

-- Créer le template
INSERT INTO subscription_templates (club_id, model_number, total_lessons, price, validity_months, is_active, created_at, updated_at)
VALUES (@club_id, CONCAT('TEST-', UNIX_TIMESTAMP()), 10, 150.00, 6, 1, NOW(), NOW());

SET @template_id = LAST_INSERT_ID();

-- Lier le type de cours
INSERT INTO subscription_template_course_types (subscription_template_id, course_type_id, created_at, updated_at)
VALUES (@template_id, @course_type_id, NOW(), NOW());

-- Créer l'abonnement
INSERT INTO subscriptions (club_id, subscription_template_id, subscription_number, name, total_lessons, price, description, is_active, created_at, updated_at)
VALUES (@club_id, @template_id, CONCAT('SUB-TEST-', UNIX_TIMESTAMP()), 'Abonnement Test', 10, 150.00, 'Abonnement de test créé automatiquement', 1, NOW(), NOW());

SET @subscription_id = LAST_INSERT_ID();

-- Créer l'instance (démarrée il y a 2 mois, expire dans 4 mois)
INSERT INTO subscription_instances (subscription_id, lessons_used, started_at, expires_at, status, created_at, updated_at)
VALUES (@subscription_id, 0, DATE_SUB(NOW(), INTERVAL 2 MONTH), DATE_ADD(NOW(), INTERVAL 4 MONTH), 'active', NOW(), NOW());

SET @instance_id = LAST_INSERT_ID();

-- Lier l'élève
INSERT INTO subscription_instance_students (subscription_instance_id, student_id, created_at, updated_at)
VALUES (@instance_id, @student_id, NOW(), NOW());

-- Créer 7 cours confirmés
INSERT INTO lessons (club_id, teacher_id, student_id, course_type_id, location_id, start_time, end_time, status, price, created_at, updated_at)
VALUES 
(@club_id, @teacher_id, @student_id, @course_type_id, @location_id, DATE_SUB(NOW(), INTERVAL 50 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 50 DAY) + INTERVAL 11 HOUR, 'confirmed', 15.00, NOW(), NOW()),
(@club_id, @teacher_id, @student_id, @course_type_id, @location_id, DATE_SUB(NOW(), INTERVAL 43 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 43 DAY) + INTERVAL 11 HOUR, 'confirmed', 15.00, NOW(), NOW()),
(@club_id, @teacher_id, @student_id, @course_type_id, @location_id, DATE_SUB(NOW(), INTERVAL 36 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 36 DAY) + INTERVAL 11 HOUR, 'completed', 15.00, NOW(), NOW()),
(@club_id, @teacher_id, @student_id, @course_type_id, @location_id, DATE_SUB(NOW(), INTERVAL 29 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 29 DAY) + INTERVAL 11 HOUR, 'confirmed', 15.00, NOW(), NOW()),
(@club_id, @teacher_id, @student_id, @course_type_id, @location_id, DATE_SUB(NOW(), INTERVAL 15 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 15 DAY) + INTERVAL 11 HOUR, 'completed', 15.00, NOW(), NOW()),
(@club_id, @teacher_id, @student_id, @course_type_id, @location_id, DATE_SUB(NOW(), INTERVAL 8 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 8 DAY) + INTERVAL 11 HOUR, 'confirmed', 15.00, NOW(), NOW()),
(@club_id, @teacher_id, @student_id, @course_type_id, @location_id, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 11 HOUR, 'confirmed', 15.00, NOW(), NOW());

-- Créer 1 cours annulé (NE DOIT PAS COMPTER)
INSERT INTO lessons (club_id, teacher_id, student_id, course_type_id, location_id, start_time, end_time, status, price, created_at, updated_at)
VALUES (@club_id, @teacher_id, @student_id, @course_type_id, @location_id, DATE_SUB(NOW(), INTERVAL 22 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 22 DAY) + INTERVAL 11 HOUR, 'cancelled', 15.00, NOW(), NOW());

-- Récupérer les 8 derniers IDs de cours
SET @lesson1_id = LAST_INSERT_ID() - 7;
SET @lesson2_id = LAST_INSERT_ID() - 6;
SET @lesson3_id = LAST_INSERT_ID() - 5;
SET @lesson4_id = LAST_INSERT_ID() - 4;
SET @lesson5_id = LAST_INSERT_ID() - 3;
SET @lesson6_id = LAST_INSERT_ID() - 2;
SET @lesson7_id = LAST_INSERT_ID() - 1;
SET @lesson8_id = LAST_INSERT_ID();

-- Lier tous les cours à l'abonnement
INSERT INTO subscription_lessons (subscription_instance_id, lesson_id, created_at, updated_at)
SELECT @instance_id, id, NOW(), NOW()
FROM lessons 
WHERE id >= @lesson1_id AND id <= @lesson8_id;

-- METTRE UN MAUVAIS COMPTEUR (3 au lieu de 7)
UPDATE subscription_instances SET lessons_used = 3 WHERE id = @instance_id;

-- RÉSULTAT
SELECT 
    '=== ✅ STRUCTURE CRÉÉE ===' as '📊 INFO',
    @instance_id as instance_id,
    (SELECT subscription_number FROM subscriptions WHERE id = @subscription_id) as subscription_number,
    '3 (FAUX)' as compteur_actuel,
    '7 (ATTENDU)' as compteur_attendu,
    '+4' as difference;

SELECT 
    l.id,
    DATE_FORMAT(l.start_time, '%d/%m/%Y') as date_cours,
    l.status,
    CASE WHEN l.status = 'cancelled' THEN '❌ NE COMPTE PAS' ELSE '✅ COMPTE' END as comptage
FROM lessons l
WHERE l.id >= @lesson1_id AND l.id <= @lesson8_id
ORDER BY l.start_time;

