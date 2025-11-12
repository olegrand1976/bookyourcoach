-- ============================================================================
-- SCRIPT DE CORRECTION DE COHÉRENCE - BASE DE DONNÉES PRODUCTION
-- ============================================================================
-- Date: 2025-11-08
-- Base: book-your-coach
-- 
-- IMPORTANT: Ce script doit être exécuté avec précaution sur la production
-- Il est recommandé de faire une sauvegarde complète avant l'exécution
-- 
-- UTILISATION:
-- 1. Faire une sauvegarde: mysqldump -u user -p book-your-coach > backup.sql
-- 2. Réviser ce script
-- 3. Exécuter: mysql -u user -p book-your-coach < CORRECTIFS_PRODUCTION.sql
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================================
-- SECTION 1: NETTOYAGE DES FOREIGN KEYS ORPHELINES
-- ============================================================================

-- 1.1 Vérifier et nettoyer les teachers avec user_id inexistant
-- ----------------------------------------------------------------------------
DELETE FROM teacher_disciplines 
WHERE teacher_id IN (
    SELECT id FROM teachers 
    WHERE user_id IS NOT NULL 
    AND user_id NOT IN (SELECT id FROM users)
);

DELETE FROM teacher_certifications 
WHERE teacher_id IN (
    SELECT id FROM teachers 
    WHERE user_id IS NOT NULL 
    AND user_id NOT IN (SELECT id FROM users)
);

DELETE FROM club_teachers 
WHERE teacher_id IN (
    SELECT id FROM teachers 
    WHERE user_id IS NOT NULL 
    AND user_id NOT IN (SELECT id FROM users)
);

-- Note: Ne pas supprimer les teachers orphelins car ils peuvent avoir des lessons
-- À la place, les marquer comme non disponibles et soft delete
UPDATE teachers 
SET is_available = 0,
    deleted_at = NOW()
WHERE user_id IS NOT NULL 
AND user_id NOT IN (SELECT id FROM users);

-- 1.2 Vérifier et nettoyer les students avec user_id inexistant
-- ----------------------------------------------------------------------------
DELETE FROM student_disciplines 
WHERE student_id IN (
    SELECT id FROM students 
    WHERE user_id IS NOT NULL 
    AND user_id NOT IN (SELECT id FROM users)
);

DELETE FROM club_students 
WHERE student_id IN (
    SELECT id FROM students 
    WHERE user_id IS NOT NULL 
    AND user_id NOT IN (SELECT id FROM users)
);

-- Note: Ne pas supprimer les students orphelins s'ils ont des subscriptions actives
-- Utiliser le soft delete
UPDATE students 
SET deleted_at = NOW()
WHERE user_id IS NOT NULL 
AND user_id NOT IN (SELECT id FROM users)
AND id NOT IN (
    SELECT DISTINCT sis.student_id 
    FROM subscription_instance_students sis
    INNER JOIN subscription_instances si ON sis.subscription_instance_id = si.id
    WHERE si.status IN ('active', 'pending')
);

-- 1.3 Nettoyer les club_students avec foreign keys orphelines
-- ----------------------------------------------------------------------------
DELETE FROM club_students 
WHERE club_id NOT IN (SELECT id FROM clubs);

DELETE FROM club_students 
WHERE student_id NOT IN (SELECT id FROM students);

-- 1.4 Nettoyer les club_teachers avec foreign keys orphelines
-- ----------------------------------------------------------------------------
DELETE FROM club_teachers 
WHERE club_id NOT IN (SELECT id FROM clubs);

DELETE FROM club_teachers 
WHERE teacher_id NOT IN (SELECT id FROM teachers);

-- 1.5 Nettoyer les club_managers avec foreign keys orphelines
-- ----------------------------------------------------------------------------
DELETE FROM club_managers 
WHERE club_id NOT IN (SELECT id FROM clubs);

DELETE FROM club_managers 
WHERE user_id NOT IN (SELECT id FROM users);

-- 1.6 Nettoyer les subscriptions avec foreign keys orphelines
-- ----------------------------------------------------------------------------
-- Mettre en cancelled au lieu de supprimer
UPDATE subscriptions 
SET status = 'cancelled' 
WHERE student_id NOT IN (SELECT id FROM students);

UPDATE subscriptions 
SET status = 'cancelled' 
WHERE club_id NOT IN (SELECT id FROM clubs);

UPDATE subscriptions 
SET status = 'cancelled' 
WHERE discipline_id IS NOT NULL 
AND discipline_id NOT IN (SELECT id FROM disciplines);

-- 1.7 Nettoyer les subscription_course_types orphelins
-- ----------------------------------------------------------------------------
DELETE FROM subscription_course_types 
WHERE subscription_id NOT IN (SELECT id FROM subscriptions);

DELETE FROM subscription_course_types 
WHERE course_type_id NOT IN (SELECT id FROM course_types);

-- 1.8 Nettoyer les subscription_instances orphelines
-- ----------------------------------------------------------------------------
DELETE FROM subscription_instance_students 
WHERE subscription_instance_id NOT IN (SELECT id FROM subscription_instances);

DELETE FROM subscription_instances 
WHERE subscription_id NOT IN (SELECT id FROM subscriptions);

-- 1.9 Nettoyer les lesson_student orphelins
-- ----------------------------------------------------------------------------
DELETE FROM lesson_student 
WHERE lesson_id NOT IN (SELECT id FROM lessons);

DELETE FROM lesson_student 
WHERE student_id NOT IN (SELECT id FROM students);

-- 1.10 Nettoyer les subscription_lessons orphelins
-- ----------------------------------------------------------------------------
DELETE FROM subscription_lessons 
WHERE subscription_id NOT IN (SELECT id FROM subscriptions);

DELETE FROM subscription_lessons 
WHERE lesson_id NOT IN (SELECT id FROM lessons);

-- ============================================================================
-- SECTION 2: CORRECTION DES STATUTS INCOHÉRENTS
-- ============================================================================

-- 2.1 Corriger les subscriptions actives mais expirées
-- ----------------------------------------------------------------------------
UPDATE subscriptions 
SET status = 'expired' 
WHERE status = 'active' 
AND valid_until IS NOT NULL 
AND valid_until < NOW();

-- 2.2 Corriger les subscription_instances actives mais expirées
-- ----------------------------------------------------------------------------
UPDATE subscription_instances 
SET status = 'expired' 
WHERE status = 'active' 
AND expires_at IS NOT NULL 
AND expires_at < NOW();

-- 2.3 Corriger les lessons passées toujours en statut 'scheduled'
-- ----------------------------------------------------------------------------
UPDATE lessons 
SET status = 'completed' 
WHERE status = 'scheduled' 
AND end_time < NOW() - INTERVAL 1 DAY;

-- 2.4 Corriger les bookings avec statut incohérent
-- ----------------------------------------------------------------------------
UPDATE bookings 
SET status = 'completed' 
WHERE status = 'confirmed' 
AND lesson_id IN (
    SELECT id FROM lessons WHERE status = 'completed'
);

UPDATE bookings 
SET status = 'cancelled' 
WHERE lesson_id IN (
    SELECT id FROM lessons WHERE status = 'cancelled'
);

-- ============================================================================
-- SECTION 3: CORRECTION DES VALEURS NUMÉRIQUES INVALIDES
-- ============================================================================

-- 3.1 Corriger les remaining_lessons négatifs
-- ----------------------------------------------------------------------------
UPDATE subscriptions 
SET remaining_lessons = 0 
WHERE remaining_lessons < 0;

-- 3.2 Corriger les used_lessons supérieur à total_lessons
-- ----------------------------------------------------------------------------
UPDATE subscriptions 
SET used_lessons = total_lessons 
WHERE used_lessons > total_lessons;

-- 3.3 Recalculer remaining_lessons basé sur total_lessons et used_lessons
-- ----------------------------------------------------------------------------
UPDATE subscriptions 
SET remaining_lessons = total_lessons - used_lessons 
WHERE remaining_lessons != (total_lessons - used_lessons);

-- 3.4 Corriger les subscription_instances avec lessons_used négatif
-- ----------------------------------------------------------------------------
UPDATE subscription_instances 
SET lessons_used = 0 
WHERE lessons_used < 0;

-- 3.5 Corriger les prix négatifs dans lessons
-- ----------------------------------------------------------------------------
UPDATE lessons 
SET price = 0 
WHERE price < 0;

-- 3.6 Corriger les prix négatifs dans subscriptions
-- ----------------------------------------------------------------------------
UPDATE subscriptions 
SET price = 0 
WHERE price < 0;

-- 3.7 Corriger les prix négatifs dans course_types
-- ----------------------------------------------------------------------------
UPDATE course_types 
SET base_price = 0 
WHERE base_price < 0;

-- 3.8 Corriger max_students invalides dans lessons
-- ----------------------------------------------------------------------------
UPDATE lessons 
SET max_students = 1 
WHERE max_students < 1;

-- ============================================================================
-- SECTION 4: CORRECTION DES DATES INCOHÉRENTES
-- ============================================================================

-- 4.1 Corriger les lessons avec start_time >= end_time
-- ----------------------------------------------------------------------------
-- Ajouter 1 heure à end_time si problème
UPDATE lessons 
SET end_time = DATE_ADD(start_time, INTERVAL 1 HOUR) 
WHERE start_time >= end_time;

-- 4.2 Corriger les subscriptions avec valid_from >= valid_until
-- ----------------------------------------------------------------------------
UPDATE subscriptions 
SET valid_until = DATE_ADD(valid_from, INTERVAL 3 MONTH) 
WHERE valid_from IS NOT NULL 
AND valid_until IS NOT NULL 
AND valid_from >= valid_until;

-- 4.3 Corriger les subscription_instances avec started_at >= expires_at
-- ----------------------------------------------------------------------------
UPDATE subscription_instances 
SET expires_at = DATE_ADD(started_at, INTERVAL 4 MONTH) 
WHERE started_at IS NOT NULL 
AND expires_at IS NOT NULL 
AND started_at >= expires_at;

-- 4.4 Corriger les teacher_certifications avec obtained_at dans le futur
-- ----------------------------------------------------------------------------
UPDATE teacher_certifications 
SET obtained_at = NOW() 
WHERE obtained_at > NOW();

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

-- 5.6 Supprimer les doublons dans subscription_course_types (garder le plus récent)
-- ----------------------------------------------------------------------------
DELETE sct1 FROM subscription_course_types sct1
INNER JOIN subscription_course_types sct2 
WHERE sct1.subscription_id = sct2.subscription_id 
AND sct1.course_type_id = sct2.course_type_id 
AND sct1.id < sct2.id;

-- 5.7 Supprimer les doublons dans lesson_student (garder le plus récent)
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

-- 6.2 Mettre à jour les users sans role (utiliser 'student' par défaut)
-- ----------------------------------------------------------------------------
UPDATE users 
SET role = 'student' 
WHERE role IS NULL OR role = '';

-- 6.3 Mettre à jour les clubs sans email avec un email par défaut
-- ----------------------------------------------------------------------------
-- Note: À adapter selon vos besoins
UPDATE clubs 
SET email = CONCAT('club', id, '@activibe.com') 
WHERE email IS NULL OR email = '';

-- 6.4 Mettre à jour les lessons sans max_students
-- ----------------------------------------------------------------------------
UPDATE lessons 
SET max_students = 1 
WHERE max_students IS NULL;

-- 6.5 Mettre à jour les lessons sans price
-- ----------------------------------------------------------------------------
UPDATE lessons 
SET price = 0 
WHERE price IS NULL;

-- ============================================================================
-- SECTION 7: SYNCHRONISATION ET COHÉRENCE DES COMPTEURS
-- ============================================================================

-- 7.1 Recalculer used_lessons pour toutes les subscriptions
-- ----------------------------------------------------------------------------
UPDATE subscriptions s
SET used_lessons = (
    SELECT COUNT(*) 
    FROM subscription_lessons sl 
    WHERE sl.subscription_id = s.id
);

-- 7.2 Recalculer remaining_lessons
-- ----------------------------------------------------------------------------
UPDATE subscriptions 
SET remaining_lessons = total_lessons - used_lessons;

-- 7.3 Mettre à jour le status des subscriptions basé sur remaining_lessons
-- ----------------------------------------------------------------------------
UPDATE subscriptions 
SET status = 'completed' 
WHERE status = 'active' 
AND remaining_lessons = 0 
AND total_lessons > 0;

-- 7.4 Recalculer lessons_used pour subscription_instances
-- ----------------------------------------------------------------------------
UPDATE subscription_instances si
SET lessons_used = (
    SELECT COUNT(DISTINCT sl.lesson_id)
    FROM subscription_lessons sl
    INNER JOIN lessons l ON sl.lesson_id = l.id
    WHERE sl.subscription_id = si.subscription_id
    AND l.start_time >= si.started_at
    AND (si.expires_at IS NULL OR l.start_time <= si.expires_at)
);

-- 7.5 Mettre à jour current_capacity dans lessons
-- ----------------------------------------------------------------------------
UPDATE lessons l
SET current_capacity = (
    SELECT COUNT(*) 
    FROM lesson_student ls 
    WHERE ls.lesson_id = l.id
);

-- ============================================================================
-- SECTION 8: VÉRIFICATION DES DÉPASSEMENTS DE CAPACITÉ
-- ============================================================================

-- 8.1 Identifier les lessons qui dépassent la capacité
-- ----------------------------------------------------------------------------
-- Les mettre en statut 'full' s'il existe
UPDATE lessons 
SET status = 'cancelled' 
WHERE current_capacity > max_students 
AND status = 'scheduled';

-- Note: Vous devrez peut-être gérer manuellement ces cas

-- ============================================================================
-- SECTION 9: NETTOYAGE DES DONNÉES OBSOLÈTES
-- ============================================================================

-- 9.1 Supprimer les anciens personal_access_tokens expirés
-- ----------------------------------------------------------------------------
DELETE FROM personal_access_tokens 
WHERE expires_at IS NOT NULL 
AND expires_at < NOW() - INTERVAL 30 DAY;

-- 9.2 Supprimer les anciennes sessions expirées
-- ----------------------------------------------------------------------------
DELETE FROM sessions 
WHERE last_activity < UNIX_TIMESTAMP(NOW() - INTERVAL 30 DAY);

-- 9.3 Nettoyer les anciennes notifications lues
-- ----------------------------------------------------------------------------
DELETE FROM notifications 
WHERE read_at IS NOT NULL 
AND read_at < NOW() - INTERVAL 90 DAY;

-- 9.4 Nettoyer le cache expiré
-- ----------------------------------------------------------------------------
DELETE FROM cache 
WHERE expiration < UNIX_TIMESTAMP(NOW());

-- ============================================================================
-- SECTION 10: OPTIMISATION ET INDEXATION
-- ============================================================================

-- 10.1 Vérifier et optimiser les tables
-- ----------------------------------------------------------------------------
OPTIMIZE TABLE users;
OPTIMIZE TABLE clubs;
OPTIMIZE TABLE teachers;
OPTIMIZE TABLE students;
OPTIMIZE TABLE lessons;
OPTIMIZE TABLE subscriptions;
OPTIMIZE TABLE subscription_instances;
OPTIMIZE TABLE club_students;
OPTIMIZE TABLE club_teachers;
OPTIMIZE TABLE lesson_student;
OPTIMIZE TABLE subscription_lessons;

-- ============================================================================
-- SECTION 11: CRÉATION DE VUES POUR FACILITER LES VÉRIFICATIONS
-- ============================================================================

-- 11.1 Vue pour les subscriptions avec détails
-- ----------------------------------------------------------------------------
CREATE OR REPLACE VIEW v_subscriptions_details AS
SELECT 
    s.id,
    s.subscription_number,
    s.status,
    s.total_lessons,
    s.used_lessons,
    s.remaining_lessons,
    s.valid_from,
    s.valid_until,
    st.id as student_id,
    CONCAT(u.first_name, ' ', u.last_name) as student_name,
    c.name as club_name,
    d.name as discipline_name,
    CASE 
        WHEN s.status = 'active' AND s.valid_until < NOW() THEN 'EXPIRED_BUT_ACTIVE'
        WHEN s.remaining_lessons < 0 THEN 'NEGATIVE_REMAINING'
        WHEN s.used_lessons > s.total_lessons THEN 'USED_EXCEEDS_TOTAL'
        ELSE 'OK'
    END as coherence_status
FROM subscriptions s
LEFT JOIN students st ON s.student_id = st.id
LEFT JOIN users u ON st.user_id = u.id
LEFT JOIN clubs c ON s.club_id = c.id
LEFT JOIN disciplines d ON s.discipline_id = d.id;

-- 11.2 Vue pour les lessons avec problèmes
-- ----------------------------------------------------------------------------
CREATE OR REPLACE VIEW v_lessons_issues AS
SELECT 
    l.id,
    l.title,
    l.status,
    l.start_time,
    l.end_time,
    l.current_capacity,
    l.max_students,
    l.price,
    CASE 
        WHEN l.start_time >= l.end_time THEN 'INVALID_DATES'
        WHEN l.current_capacity > l.max_students THEN 'OVER_CAPACITY'
        WHEN l.price < 0 THEN 'NEGATIVE_PRICE'
        WHEN l.status = 'scheduled' AND l.end_time < NOW() - INTERVAL 1 DAY THEN 'PAST_SCHEDULED'
        ELSE 'OK'
    END as issue_type
FROM lessons l
WHERE l.start_time >= l.end_time
   OR l.current_capacity > l.max_students
   OR l.price < 0
   OR (l.status = 'scheduled' AND l.end_time < NOW() - INTERVAL 1 DAY);

-- ============================================================================
-- SECTION 12: GÉNÉRATION DE STATISTIQUES POST-CORRECTION
-- ============================================================================

-- 12.1 Créer une table temporaire pour les statistiques
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
('active_subscriptions', (SELECT COUNT(*) FROM subscriptions WHERE status = 'active'), 'Abonnements actifs'),
('expired_subscriptions', (SELECT COUNT(*) FROM subscriptions WHERE status = 'expired'), 'Abonnements expirés'),
('scheduled_lessons', (SELECT COUNT(*) FROM lessons WHERE status = 'scheduled'), 'Cours programmés'),
('completed_lessons', (SELECT COUNT(*) FROM lessons WHERE status = 'completed'), 'Cours complétés'),
('club_student_relations', (SELECT COUNT(*) FROM club_students WHERE is_active = 1), 'Relations club-étudiant actives');

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
FROM v_subscriptions_details
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

-- ATTENTION: Décommentez SOIT commit SOIT rollback après avoir vérifié les résultats

-- Pour appliquer les modifications:
COMMIT;

-- Pour annuler les modifications (si quelque chose ne va pas):
-- ROLLBACK;

-- Message de fin
SELECT '✅ Script de correction exécuté avec succès!' as message;
SELECT '⚠️  Veuillez vérifier les statistiques et les problèmes restants ci-dessus.' as warning;

