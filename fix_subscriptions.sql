-- ============================================
-- Script de correction des abonnements
-- Date: 2025-11-07
-- Objectif: Séparer les instances multiples du même élève sur des abonnements distincts
-- ============================================

USE book_your_coach_local;

SET @club_id = 1;

-- ============================================
-- 1. CORRECTION ABONNEMENT 2511-001 (Nathan Martin)
-- ============================================

-- Nathan Martin a 4 instances sur le même abonnement (29 cours total)
-- On va créer 3 nouveaux abonnements et garder seulement l'instance 14 sur 2511-001

-- 1.1 Créer un nouvel abonnement pour l'instance 17 (la plus ancienne - sept)
INSERT INTO subscriptions (subscription_number, club_id, subscription_template_id, created_at, updated_at)
VALUES ('2511-001-A', @club_id, 1, NOW(), NOW());

SET @new_sub_17 = LAST_INSERT_ID();

-- Réaffecter l'instance 17 au nouvel abonnement
UPDATE subscription_instances SET subscription_id = @new_sub_17 WHERE id = 17;

-- Clôturer cette instance (9 cours utilisés sur 11)
UPDATE subscription_instances SET status = 'completed' WHERE id = 17;

-- 1.2 Créer un nouvel abonnement pour l'instance 16 (oct début)
INSERT INTO subscriptions (subscription_number, club_id, subscription_template_id, created_at, updated_at)
VALUES ('2511-001-B', @club_id, 1, NOW(), NOW());

SET @new_sub_16 = LAST_INSERT_ID();

-- Réaffecter l'instance 16 au nouvel abonnement
UPDATE subscription_instances SET subscription_id = @new_sub_16 WHERE id = 16;

-- Clôturer cette instance (8 cours utilisés sur 11)
UPDATE subscription_instances SET status = 'completed' WHERE id = 16;

-- 1.3 Créer un nouvel abonnement pour l'instance 15 (oct milieu)
INSERT INTO subscriptions (subscription_number, club_id, subscription_template_id, created_at, updated_at)
VALUES ('2511-001-C', @club_id, 1, NOW(), NOW());

SET @new_sub_15 = LAST_INSERT_ID();

-- Réaffecter l'instance 15 au nouvel abonnement
UPDATE subscription_instances SET subscription_id = @new_sub_15 WHERE id = 15;

-- Clôturer cette instance (7 cours utilisés sur 11)
UPDATE subscription_instances SET status = 'completed' WHERE id = 15;

-- 1.4 L'instance 14 reste sur 2511-001 (5 cours utilisés sur 11) - ACTIVE

-- ============================================
-- 2. CORRECTION ABONNEMENT SUB-TEST-1762252072 (Nathan Martin)
-- ============================================

-- Nathan Martin a 3 instances sur le même abonnement (24 cours total)
-- On va créer 2 nouveaux abonnements et garder seulement l'instance 13

-- 2.1 Créer un nouvel abonnement pour l'instance 19 (sept)
INSERT INTO subscriptions (subscription_number, club_id, subscription_template_id, created_at, updated_at)
VALUES ('SUB-TEST-1762252072-A', @club_id, 7, NOW(), NOW());

SET @new_sub_19 = LAST_INSERT_ID();

-- Réaffecter l'instance 19 au nouvel abonnement
UPDATE subscription_instances SET subscription_id = @new_sub_19 WHERE id = 19;

-- Clôturer cette instance (9 cours utilisés sur 10)
UPDATE subscription_instances SET status = 'completed' WHERE id = 19;

-- 2.2 Créer un nouvel abonnement pour l'instance 18 (oct)
INSERT INTO subscriptions (subscription_number, club_id, subscription_template_id, created_at, updated_at)
VALUES ('SUB-TEST-1762252072-B', @club_id, 7, NOW(), NOW());

SET @new_sub_18 = LAST_INSERT_ID();

-- Réaffecter l'instance 18 au nouvel abonnement
UPDATE subscription_instances SET subscription_id = @new_sub_18 WHERE id = 18;

-- Clôturer cette instance (8 cours utilisés sur 10)
UPDATE subscription_instances SET status = 'completed' WHERE id = 18;

-- 2.3 L'instance 13 reste sur SUB-TEST-1762252072 (7 cours utilisés sur 10) - ACTIVE

-- ============================================
-- 3. VÉRIFICATION DES COURS LIÉS
-- ============================================

-- Les cours restent liés aux mêmes instances, donc rien à changer dans subscription_lessons

-- ============================================
-- 4. RECALCUL DES COMPTEURS (optionnel - fait automatiquement par l'app)
-- ============================================

-- Les compteurs lessons_used sont corrects selon subscription_lessons
-- Le système les recalculera automatiquement au prochain chargement

-- ============================================
-- 5. RAPPORT FINAL
-- ============================================

SELECT 
    '=== RAPPORT DE CORRECTION ===' as info;

SELECT 
    'Abonnement 2511-001' as abonnement,
    '4 instances séparées en 4 abonnements distincts' as action,
    'Instance 14 (5 cours) reste active sur 2511-001' as instance_active,
    'Instances 15,16,17 déplacées et clôturées' as instances_cloturees;

SELECT 
    'Abonnement SUB-TEST-1762252072' as abonnement,
    '3 instances séparées en 3 abonnements distincts' as action,
    'Instance 13 (7 cours) reste active' as instance_active,
    'Instances 18,19 déplacées et clôturées' as instances_cloturees;

-- Vérification finale: plus de doublons d'instances actives pour le même élève
SELECT 
    s.subscription_number,
    COUNT(DISTINCT si.id) as nb_instances_actives,
    GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') as students,
    GROUP_CONCAT(si.id SEPARATOR ',') as instance_ids
FROM subscriptions s
JOIN subscription_instances si ON s.id = si.subscription_id
JOIN subscription_instance_students sis ON si.id = sis.subscription_instance_id
JOIN students st ON sis.student_id = st.id
JOIN users u ON st.user_id = u.id
WHERE si.status = 'active'
GROUP BY s.id, sis.student_id
HAVING nb_instances_actives > 1;

SELECT 'Si la requête ci-dessus ne retourne aucune ligne, la correction est réussie !' as resultat;

