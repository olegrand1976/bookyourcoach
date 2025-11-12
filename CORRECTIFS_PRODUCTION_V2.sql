-- ============================================================================
-- SCRIPT DE CORRECTION DE COHÉRENCE - BASE DE DONNÉES PRODUCTION V2
-- ============================================================================
-- Date: 2025-11-08
-- Base: book-your-coach
-- Version: 2.0 - Adapté à l'architecture réelle de la base
-- 
-- ARCHITECTURE DES SUBSCRIPTIONS:
-- - subscriptions: table principale (juste id, numéro, template_id)
-- - subscription_templates: modèles d'abonnements (prix, nombre de leçons, etc.)
-- - subscription_instances: instances actives (status, dates, lessons_used)
-- - subscription_instance_students: liaison instances<->students
-- - subscription_lessons: liaison instances<->lessons
--
-- IMPORTANT: Ce script doit être exécuté avec précaution sur la production
-- Il est recommandé de faire une sauvegarde complète avant l'exécution
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================================
-- SECTION 1: NETTOYAGE DES FOREIGN KEYS ORPHELINES
-- ============================================================================

-- 1.1 Nettoyer teacher_disciplines avec teachers inexistants
-- ----------------------------------------------------------------------------
DELETE FROM teacher_disciplines 
WHERE teacher_id NOT IN (SELECT id FROM teachers);

-- 1.2 Nettoyer teacher_disciplines avec disciplines inexistantes
-- ----------------------------------------------------------------------------
DELETE FROM teacher_disciplines 
WHERE discipline_id NOT IN (SELECT id FROM disciplines);

-- 1.3 Nettoyer teacher_certifications orphelins
-- ----------------------------------------------------------------------------
DELETE FROM teacher_certifications 
WHERE teacher_id NOT IN (SELECT id FROM teachers);

DELETE FROM teacher_certifications 
WHERE certification_id IS NOT NULL 
AND certification_id NOT IN (SELECT id FROM certifications);

-- 1.4 Nettoyer club_teachers orphelins
-- ----------------------------------------------------------------------------
DELETE FROM club_teachers 
WHERE club_id NOT IN (SELECT id FROM clubs);

DELETE FROM club_teachers 
WHERE teacher_id NOT IN (SELECT id FROM teachers);

-- 1.5 Teachers orphelins (sans user valide) -> marquer comme non disponibles
-- ----------------------------------------------------------------------------
-- Note: Ne pas supprimer car ils peuvent avoir un historique
UPDATE teachers 
SET is_available = 0,
    deleted_at = NOW()
WHERE user_id IS NOT NULL 
AND user_id NOT IN (SELECT id FROM users)
AND deleted_at IS NULL;

-- 1.6 Nettoyer student_disciplines orphelins
-- ----------------------------------------------------------------------------
DELETE FROM student_disciplines 
WHERE student_id NOT IN (SELECT id FROM students);

DELETE FROM student_disciplines 
WHERE discipline_id NOT IN (SELECT id FROM disciplines);

-- 1.7 Nettoyer club_students orphelins
-- ----------------------------------------------------------------------------
DELETE FROM club_students 
WHERE club_id NOT IN (SELECT id FROM clubs);

DELETE FROM club_students 
WHERE student_id NOT IN (SELECT id FROM students);

-- 1.8 Students orphelins (sans user valide) -> soft delete SAUF si actifs
-- ----------------------------------------------------------------------------
-- Note: Beaucoup de students n'ont PAS de user_id (créés directement par club)
-- On ne touche QUE aux students avec user_id invalide ET sans subscription active
UPDATE students 
SET deleted_at = NOW()
WHERE user_id IS NOT NULL 
AND user_id NOT IN (SELECT id FROM users)
AND deleted_at IS NULL
AND id NOT IN (
    SELECT DISTINCT sis.student_id 
    FROM subscription_instance_students sis
    INNER JOIN subscription_instances si ON sis.subscription_instance_id = si.id
    WHERE si.status IN ('active', 'pending')
);

-- 1.9 Nettoyer club_managers orphelins
-- ----------------------------------------------------------------------------
DELETE FROM club_managers 
WHERE club_id NOT IN (SELECT id FROM clubs);

DELETE FROM club_managers 
WHERE user_id NOT IN (SELECT id FROM users);

-- 1.10 Nettoyer subscription_instance_students orphelins
-- ----------------------------------------------------------------------------
DELETE FROM subscription_instance_students 
WHERE subscription_instance_id NOT IN (SELECT id FROM subscription_instances);

DELETE FROM subscription_instance_students 
WHERE student_id NOT IN (SELECT id FROM students);

-- 1.11 Nettoyer subscription_instances orphelines
-- ----------------------------------------------------------------------------
DELETE FROM subscription_instances 
WHERE subscription_id NOT IN (SELECT id FROM subscriptions);

-- 1.12 Nettoyer subscription_lessons orphelins
-- ----------------------------------------------------------------------------
DELETE FROM subscription_lessons 
WHERE subscription_instance_id NOT IN (SELECT id FROM subscription_instances);

DELETE FROM subscription_lessons 
WHERE lesson_id NOT IN (SELECT id FROM lessons);

-- 1.13 Nettoyer subscriptions orphelines (sans template)
-- ----------------------------------------------------------------------------
-- Note: On met juste un warning, pas de suppression automatique
-- SELECT COUNT(*) as orphaned_subscriptions 
-- FROM subscriptions 
-- WHERE subscription_template_id NOT IN (SELECT id FROM subscription_templates);

-- 1.14 Nettoyer subscription_templates orphelins
-- ----------------------------------------------------------------------------
DELETE FROM subscription_template_course_types 
WHERE subscription_template_id NOT IN (SELECT id FROM subscription_templates);

-- 1.15 Nettoyer lesson_student orphelins
-- ----------------------------------------------------------------------------
DELETE FROM lesson_student 
WHERE lesson_id NOT IN (SELECT id FROM lessons);

DELETE FROM lesson_student 
WHERE student_id NOT IN (SELECT id FROM students);

-- ============================================================================
-- SECTION 2: CORRECTION DES STATUTS INCOHÉRENTS
-- ============================================================================

-- 2.1 Corriger les subscription_instances actives mais expirées
-- ----------------------------------------------------------------------------
UPDATE subscription_instances 
SET status = 'expired' 
WHERE status = 'active' 
AND expires_at IS NOT NULL 
AND expires_at < CURDATE();

-- 2.2 Corriger les lessons passées toujours en statut 'scheduled'
-- ----------------------------------------------------------------------------
UPDATE lessons 
SET status = 'completed' 
WHERE status = 'scheduled' 
AND end_time < NOW() - INTERVAL 1 DAY;

-- 2.3 Corriger les bookings avec statut incohérent
-- ----------------------------------------------------------------------------
UPDATE bookings b
INNER JOIN lessons l ON b.lesson_id = l.id
SET b.status = 'completed' 
WHERE b.status = 'confirmed' 
AND l.status = 'completed';

UPDATE bookings b
INNER JOIN lessons l ON b.lesson_id = l.id
SET b.status = 'cancelled' 
WHERE b.status IN ('pending', 'confirmed')
AND l.status = 'cancelled';

-- ============================================================================
-- SECTION 3: CORRECTION DES VALEURS NUMÉRIQUES INVALIDES
-- ============================================================================

-- 3.1 Corriger les lessons_used négatifs dans subscription_instances
-- ----------------------------------------------------------------------------
UPDATE subscription_instances 
SET lessons_used = 0 
WHERE lessons_used < 0;

-- 3.2 Corriger les prix négatifs dans lessons
-- ----------------------------------------------------------------------------
UPDATE lessons 
SET price = 0 
WHERE price < 0;

-- 3.3 Corriger les prix négatifs dans subscription_templates
-- ----------------------------------------------------------------------------
UPDATE subscription_templates 
SET price = 0 
WHERE price < 0;

-- 3.4 Corriger les prix négatifs dans course_types
-- ----------------------------------------------------------------------------
UPDATE course_types 
SET price = 0 
WHERE price < 0;

-- 3.5 Note: lessons n'a pas de champ max_students
-- La capacité est gérée via course_types.max_participants
-- ----------------------------------------------------------------------------
-- Cette section est désactivée car le champ n'existe pas

-- 3.6 Corriger total_lessons invalides dans subscription_templates
-- ----------------------------------------------------------------------------
UPDATE subscription_templates 
SET total_lessons = 1 
WHERE total_lessons < 1;

-- ============================================================================
-- SECTION 4: CORRECTION DES DATES INCOHÉRENTES
-- ============================================================================

-- 4.1 Corriger les lessons avec start_time >= end_time
-- ----------------------------------------------------------------------------
UPDATE lessons 
SET end_time = DATE_ADD(start_time, INTERVAL 1 HOUR) 
WHERE start_time >= end_time;

-- 4.2 Corriger les subscription_instances avec started_at >= expires_at
-- ----------------------------------------------------------------------------
UPDATE subscription_instances 
SET expires_at = DATE_ADD(started_at, INTERVAL 4 MONTH) 
WHERE started_at IS NOT NULL 
AND expires_at IS NOT NULL 
AND started_at >= expires_at;

-- 4.3 Corriger les teacher_certifications avec obtained_at dans le futur
-- ----------------------------------------------------------------------------
UPDATE teacher_certifications 
SET obtained_at = CURDATE()
WHERE obtained_at > CURDATE();

-- 4.4 Corriger les club_open_slots avec start_time >= end_time
-- ----------------------------------------------------------------------------
UPDATE club_open_slots 
SET end_time = '23:59:00'
WHERE start_time >= end_time;

-- ============================================================================
-- SECTION 5: SUPPRESSION DES DOUBLONS
-- ============================================================================

-- 5.1 Supprimer les doublons dans club_students (garder le plus récent)
-- ----------------------------------------------------------------------------
DELETE cs1 FROM club_students cs1
INNER JOIN club_students cs2 
WHERE cs1.club_id = cs2.club_id 
AND cs1.student_id = cs2.student_id 
AND cs1.id < cs2.id;

-- 5.2 Supprimer les doublons dans club_teachers (garder le plus récent)
-- ----------------------------------------------------------------------------
DELETE ct1 FROM club_teachers ct1
INNER JOIN club_teachers ct2 
WHERE ct1.club_id = ct2.club_id 
AND ct1.teacher_id = ct2.teacher_id 
AND ct1.id < ct2.id;

-- 5.3 Supprimer les doublons dans club_disciplines (garder le plus récent)
-- ----------------------------------------------------------------------------
DELETE cd1 FROM club_disciplines cd1
INNER JOIN club_disciplines cd2 
WHERE cd1.club_id = cd2.club_id 
AND cd1.discipline_id = cd2.discipline_id 
AND cd1.id < cd2.id;

-- 5.4 Supprimer les doublons dans student_disciplines (garder le plus récent)
-- ----------------------------------------------------------------------------
DELETE sd1 FROM student_disciplines sd1
INNER JOIN student_disciplines sd2 
WHERE sd1.student_id = sd2.student_id 
AND sd1.discipline_id = sd2.discipline_id 
AND sd1.id < sd2.id;

-- 5.5 Supprimer les doublons dans teacher_disciplines (garder le plus récent)
-- ----------------------------------------------------------------------------
DELETE td1 FROM teacher_disciplines td1
INNER JOIN teacher_disciplines td2 
WHERE td1.teacher_id = td2.teacher_id 
AND td1.discipline_id = td2.discipline_id 
AND td1.id < td2.id;

-- 5.6 Supprimer les doublons dans subscription_instance_students
-- ----------------------------------------------------------------------------
DELETE sis1 FROM subscription_instance_students sis1
INNER JOIN subscription_instance_students sis2 
WHERE sis1.subscription_instance_id = sis2.subscription_instance_id 
AND sis1.student_id = sis2.student_id 
AND sis1.id < sis2.id;

-- 5.7 Supprimer les doublons dans subscription_lessons
-- ----------------------------------------------------------------------------
DELETE sl1 FROM subscription_lessons sl1
INNER JOIN subscription_lessons sl2 
WHERE sl1.subscription_instance_id = sl2.subscription_instance_id 
AND sl1.lesson_id = sl2.lesson_id 
AND sl1.id < sl2.id;

-- 5.8 Supprimer les doublons dans lesson_student (garder le plus récent)
-- ----------------------------------------------------------------------------
DELETE ls1 FROM lesson_student ls1
INNER JOIN lesson_student ls2 
WHERE ls1.lesson_id = ls2.lesson_id 
AND ls1.student_id = ls2.student_id 
AND ls1.id < ls2.id;

-- ============================================================================
-- SECTION 6: CORRECTION DES VALEURS NULL INAPPROPRIÉES
-- ============================================================================

-- 6.1 Mettre à jour les users sans status
-- ----------------------------------------------------------------------------
UPDATE users 
SET status = 'active' 
WHERE status IS NULL OR status = '';

-- 6.2 Mettre à jour les users sans role
-- ----------------------------------------------------------------------------
UPDATE users 
SET role = 'student' 
WHERE role IS NULL OR role = '';

-- 6.3 Mettre à jour les clubs sans email avec un email par défaut
-- ----------------------------------------------------------------------------
UPDATE clubs 
SET email = CONCAT('club', id, '@activibe.com') 
WHERE email IS NULL OR email = '';

-- 6.4 Note: lessons n'a pas de champ max_students
-- ----------------------------------------------------------------------------
-- Cette section est désactivée car le champ n'existe pas

-- 6.5 Mettre à jour les lessons sans price
-- ----------------------------------------------------------------------------
UPDATE lessons 
SET price = 0 
WHERE price IS NULL;

-- ============================================================================
-- SECTION 7: SYNCHRONISATION ET COHÉRENCE DES COMPTEURS
-- ============================================================================

-- 7.1 Recalculer lessons_used pour toutes les subscription_instances
-- ----------------------------------------------------------------------------
UPDATE subscription_instances si
SET lessons_used = (
    SELECT COUNT(*) 
    FROM subscription_lessons sl 
    WHERE sl.subscription_instance_id = si.id
);

-- 7.2 Mettre à jour le status des instances basé sur les lessons utilisées
-- ----------------------------------------------------------------------------
-- Marquer comme completed si toutes les leçons ont été utilisées
UPDATE subscription_instances si
INNER JOIN subscriptions s ON si.subscription_id = s.id
INNER JOIN subscription_templates st ON s.subscription_template_id = st.id
SET si.status = 'completed' 
WHERE si.status = 'active' 
AND si.lessons_used >= st.total_lessons;

-- 7.3 Note: lessons n'a pas de champ current_capacity
-- Le nombre de participants est calculé dynamiquement via lesson_student
-- ----------------------------------------------------------------------------
-- Cette section est désactivée car le champ n'existe pas

-- ============================================================================
-- SECTION 8: NETTOYAGE DES DONNÉES OBSOLÈTES
-- ============================================================================

-- 8.1 Supprimer les anciens personal_access_tokens expirés
-- ----------------------------------------------------------------------------
DELETE FROM personal_access_tokens 
WHERE expires_at IS NOT NULL 
AND expires_at < NOW() - INTERVAL 30 DAY;

-- 8.2 Supprimer les anciennes sessions expirées
-- ----------------------------------------------------------------------------
DELETE FROM sessions 
WHERE last_activity < UNIX_TIMESTAMP(NOW() - INTERVAL 30 DAY);

-- 8.3 Nettoyer les anciennes notifications lues
-- ----------------------------------------------------------------------------
DELETE FROM notifications 
WHERE read_at IS NOT NULL 
AND read_at < NOW() - INTERVAL 90 DAY;

-- 8.4 Nettoyer le cache expiré
-- ----------------------------------------------------------------------------
DELETE FROM cache 
WHERE expiration < UNIX_TIMESTAMP(NOW());

-- ============================================================================
-- SECTION 9: OPTIMISATION ET INDEXATION
-- ============================================================================

-- 9.1 Optimiser les tables principales
-- ----------------------------------------------------------------------------
OPTIMIZE TABLE users;
OPTIMIZE TABLE clubs;
OPTIMIZE TABLE teachers;
OPTIMIZE TABLE students;
OPTIMIZE TABLE lessons;
OPTIMIZE TABLE subscriptions;
OPTIMIZE TABLE subscription_templates;
OPTIMIZE TABLE subscription_instances;
OPTIMIZE TABLE subscription_instance_students;
OPTIMIZE TABLE subscription_lessons;
OPTIMIZE TABLE club_students;
OPTIMIZE TABLE club_teachers;
OPTIMIZE TABLE lesson_student;

-- ============================================================================
-- SECTION 10: CRÉATION DE VUES POUR FACILITER LES VÉRIFICATIONS
-- ============================================================================

-- 10.1 Vue pour les subscriptions avec détails complets
-- ----------------------------------------------------------------------------
CREATE OR REPLACE VIEW v_subscriptions_complete AS
SELECT 
    s.id as subscription_id,
    s.subscription_number,
    st.model_number as template_name,
    st.total_lessons,
    st.price as template_price,
    si.id as instance_id,
    si.status as instance_status,
    si.lessons_used,
    (st.total_lessons - si.lessons_used) as lessons_remaining,
    si.started_at,
    si.expires_at,
    DATEDIFF(si.expires_at, CURDATE()) as days_remaining,
    sis.student_id,
    CONCAT(stu.first_name, ' ', stu.last_name) as student_name,
    c.name as club_name,
    CASE 
        WHEN si.status = 'active' AND si.expires_at < CURDATE() THEN 'EXPIRED_BUT_ACTIVE'
        WHEN si.lessons_used < 0 THEN 'NEGATIVE_LESSONS'
        WHEN si.lessons_used > st.total_lessons THEN 'EXCEEDED_LESSONS'
        WHEN si.started_at >= si.expires_at THEN 'INVALID_DATES'
        ELSE 'OK'
    END as coherence_status
FROM subscriptions s
INNER JOIN subscription_templates st ON s.subscription_template_id = st.id
INNER JOIN subscription_instances si ON s.id = si.subscription_id
LEFT JOIN subscription_instance_students sis ON si.id = sis.subscription_instance_id
LEFT JOIN students stu ON sis.student_id = stu.id
LEFT JOIN clubs c ON st.club_id = c.id;

-- 10.2 Vue pour les lessons avec problèmes
-- ----------------------------------------------------------------------------
CREATE OR REPLACE VIEW v_lessons_issues AS
SELECT 
    l.id,
    l.start_time,
    l.end_time,
    l.status,
    l.price,
    c.name as club_name,
    ct.name as course_type_name,
    (SELECT COUNT(*) FROM lesson_student ls WHERE ls.lesson_id = l.id) as participants_count,
    ct.max_participants,
    CASE 
        WHEN l.start_time >= l.end_time THEN 'INVALID_DATES'
        WHEN l.price < 0 THEN 'NEGATIVE_PRICE'
        WHEN l.status = 'confirmed' AND l.end_time < NOW() - INTERVAL 1 DAY THEN 'PAST_CONFIRMED'
        WHEN (SELECT COUNT(*) FROM lesson_student ls WHERE ls.lesson_id = l.id) > COALESCE(ct.max_participants, 999) THEN 'OVER_CAPACITY'
        ELSE 'OK'
    END as issue_type
FROM lessons l
LEFT JOIN clubs c ON l.club_id = c.id
LEFT JOIN course_types ct ON l.course_type_id = ct.id
WHERE l.start_time >= l.end_time
   OR l.price < 0
   OR (l.status = 'confirmed' AND l.end_time < NOW() - INTERVAL 1 DAY)
   OR (SELECT COUNT(*) FROM lesson_student ls WHERE ls.lesson_id = l.id) > COALESCE(ct.max_participants, 999);

-- 10.3 Vue pour les students avec leurs abonnements actifs
-- ----------------------------------------------------------------------------
CREATE OR REPLACE VIEW v_students_subscriptions AS
SELECT 
    s.id as student_id,
    CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) as student_name,
    s.user_id,
    COUNT(DISTINCT si.id) as total_subscriptions,
    SUM(CASE WHEN si.status = 'active' THEN 1 ELSE 0 END) as active_subscriptions,
    SUM(CASE WHEN si.status = 'expired' THEN 1 ELSE 0 END) as expired_subscriptions,
    MAX(si.expires_at) as latest_expiration
FROM students s
LEFT JOIN subscription_instance_students sis ON s.id = sis.student_id
LEFT JOIN subscription_instances si ON sis.subscription_instance_id = si.id
GROUP BY s.id, s.first_name, s.last_name, s.user_id;

-- ============================================================================
-- SECTION 11: GÉNÉRATION DE STATISTIQUES POST-CORRECTION
-- ============================================================================

-- 11.1 Créer une table temporaire pour les statistiques
-- ----------------------------------------------------------------------------
CREATE TEMPORARY TABLE IF NOT EXISTS correction_stats (
    metric VARCHAR(255),
    value INT,
    description TEXT
);

INSERT INTO correction_stats (metric, value, description) VALUES
('total_users', (SELECT COUNT(*) FROM users), 'Nombre total d\'utilisateurs'),
('total_clubs', (SELECT COUNT(*) FROM clubs), 'Nombre total de clubs'),
('total_teachers', (SELECT COUNT(*) FROM teachers WHERE deleted_at IS NULL), 'Nombre de professeurs actifs'),
('total_students', (SELECT COUNT(*) FROM students WHERE deleted_at IS NULL), 'Nombre d\'étudiants actifs'),
('students_with_user', (SELECT COUNT(*) FROM students WHERE user_id IS NOT NULL AND deleted_at IS NULL), 'Étudiants avec compte utilisateur'),
('students_without_user', (SELECT COUNT(*) FROM students WHERE user_id IS NULL AND deleted_at IS NULL), 'Étudiants sans compte (créés par club)'),
('subscriptions_total', (SELECT COUNT(*) FROM subscriptions), 'Total abonnements'),
('subscription_instances_active', (SELECT COUNT(*) FROM subscription_instances WHERE status = 'active'), 'Instances actives'),
('subscription_instances_expired', (SELECT COUNT(*) FROM subscription_instances WHERE status = 'expired'), 'Instances expirées'),
('scheduled_lessons', (SELECT COUNT(*) FROM lessons WHERE status = 'scheduled'), 'Cours programmés'),
('completed_lessons', (SELECT COUNT(*) FROM lessons WHERE status = 'completed'), 'Cours complétés'),
('club_student_relations', (SELECT COUNT(*) FROM club_students WHERE is_active = 1), 'Relations club-étudiant actives'),
('club_teacher_relations', (SELECT COUNT(*) FROM club_teachers), 'Relations club-professeur');

-- Afficher les statistiques
SELECT 
    '============================================================================' as separator
UNION ALL
SELECT 'STATISTIQUES POST-CORRECTION'
UNION ALL
SELECT '============================================================================'
UNION ALL
SELECT CONCAT(metric, ': ', value, ' - ', description)
FROM correction_stats;

-- ============================================================================
-- FINALISATION
-- ============================================================================

-- Vérification finale: afficher les problèmes restants
SELECT 
    '============================================================================' as separator
UNION ALL
SELECT 'VÉRIFICATION FINALE - PROBLÈMES POTENTIELS'
UNION ALL
SELECT '============================================================================'
UNION ALL
SELECT 'Subscriptions avec incohérences:' as check_type
UNION ALL
SELECT CONCAT('  - ', coherence_status, ': ', COUNT(*), ' subscription(s)')
FROM v_subscriptions_complete
WHERE coherence_status != 'OK'
GROUP BY coherence_status
UNION ALL
SELECT ''
UNION ALL
SELECT 'Lessons avec problèmes:' as check_type
UNION ALL
SELECT CONCAT('  - ', issue_type, ': ', COUNT(*), ' lesson(s)')
FROM v_lessons_issues
WHERE issue_type != 'OK'
GROUP BY issue_type;

-- ============================================================================
-- COMMIT OU ROLLBACK
-- ============================================================================

-- Pour appliquer les modifications:
COMMIT;

-- Pour annuler les modifications (si quelque chose ne va pas):
-- ROLLBACK;

-- Message de fin
SELECT '✅ Script de correction exécuté avec succès!' as message;
SELECT '⚠️  Veuillez vérifier les statistiques et les problèmes restants ci-dessus.' as warning;
SELECT '📊 Utilisez les vues créées pour le monitoring continu:' as info;
SELECT '   - v_subscriptions_complete' as vue1;
SELECT '   - v_lessons_issues' as vue2;
SELECT '   - v_students_subscriptions' as vue3;

