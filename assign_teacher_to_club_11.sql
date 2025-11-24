-- Script SQL pour assigner un enseignant au club 11
-- Remplacez TEACHER_ID par l'ID de l'enseignant souhaité

-- Option 1: Assigner Sophie Martin (premier enseignant, généralement ID 1)
-- Si l'enseignant n'est pas encore assigné au club
INSERT INTO club_teachers (club_id, teacher_id, is_active, joined_at, created_at, updated_at)
SELECT 11, t.id, true, NOW(), NOW(), NOW()
FROM teachers t
INNER JOIN users u ON t.user_id = u.id
WHERE u.email = 'sophie.martin@activibe.com'
AND NOT EXISTS (
    SELECT 1 FROM club_teachers ct 
    WHERE ct.club_id = 11 AND ct.teacher_id = t.id
)
LIMIT 1;

-- Option 2: Assigner tous les enseignants disponibles au club 11
-- (Décommentez si nécessaire)
/*
INSERT INTO club_teachers (club_id, teacher_id, is_active, joined_at, created_at, updated_at)
SELECT 11, t.id, true, NOW(), NOW(), NOW()
FROM teachers t
WHERE NOT EXISTS (
    SELECT 1 FROM club_teachers ct 
    WHERE ct.club_id = 11 AND ct.teacher_id = t.id
);
*/

-- Vérification: Voir les enseignants assignés au club 11
SELECT 
    ct.id,
    ct.club_id,
    ct.teacher_id,
    u.name as teacher_name,
    u.email as teacher_email,
    ct.is_active,
    ct.joined_at
FROM club_teachers ct
INNER JOIN teachers t ON ct.teacher_id = t.id
INNER JOIN users u ON t.user_id = u.id
WHERE ct.club_id = 11;

