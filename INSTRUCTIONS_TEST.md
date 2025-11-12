# Instructions pour créer un abonnement de test

## Option 1 : Via l'interface web (RECOMMANDÉ)

### Étape 1 : Vérifier votre club ID
Connectez-vous à votre base de données et exécutez :
```sql
SELECT c.id, c.name, u.email 
FROM clubs c 
JOIN club_user cu ON cu.club_id = c.id 
JOIN users u ON u.id = cu.user_id 
WHERE u.email = 'b.murgo1976@gmail.com';
```

### Étape 2 : Exécuter le script
Le script SQL complet est dans `create_test_subscription.sql`

**Commande Docker :**
```bash
docker exec -i activibe-mysql-local mysql -u activibe_user -pactivibe_password book_your_coach_local < create_test_subscription.sql
```

### Étape 3 : Vérifier la création
```sql
SELECT 
    si.id as instance_id,
    s.subscription_number,
    si.lessons_used as compteur_actuel,
    COUNT(sl.id) as cours_lies_total,
    SUM(CASE WHEN l.status != 'cancelled' THEN 1 ELSE 0 END) as cours_lies_comptables,
    si.started_at,
    si.expires_at
FROM subscription_instances si
JOIN subscriptions s ON s.id = si.subscription_id
LEFT JOIN subscription_lessons sl ON sl.subscription_instance_id = si.id
LEFT JOIN lessons l ON sl.lesson_id = l.id
WHERE s.subscription_number LIKE 'SUB-TEST-%'
GROUP BY si.id;
```

**Résultat attendu :**
- `compteur_actuel`: 3 (FAUX volontairement)
- `cours_lies_comptables`: 7 (7 cours confirmés/complétés)
- Différence : +4

---

## Option 2 : Création manuelle via l'interface

Si le script ne fonctionne pas, voici comment créer manuellement un test :

### 1. Créer un modèle d'abonnement
- Allez sur `/club/subscription-templates`
- Créez un modèle "Test 10 cours"
- 10 cours, validité 6 mois

### 2. Créer un abonnement et l'assigner à un élève
- Allez sur `/club/subscriptions`
- Assignez l'abonnement à un élève existant

### 3. Créer des cours
- Allez sur `/club/planning`
- Créez 8 cours pour cet élève :
  - 7 avec statut "confirmé" ou "complété"
  - 1 avec statut "annulé"

### 4. Lier les cours à l'abonnement
Les cours devraient être automatiquement liés à l'abonnement si l'élève a un abonnement actif.

### 5. Modifier le compteur manuellement (pour tester)
```sql
-- Récupérez l'ID de votre instance
SELECT id, lessons_used FROM subscription_instances WHERE status = 'active' LIMIT 1;

-- Mettez un mauvais compteur (ex: 3 au lieu de 7)
UPDATE subscription_instances SET lessons_used = 3 WHERE id = [VOTRE_INSTANCE_ID];
```

### 6. Tester le recalcul
- Allez sur `/club/subscriptions`
- Cliquez sur "Recalculer les Cours Restants"
- Le compteur devrait passer de 3 à 7

---

## Vérifications après recalcul

### Dans les logs Laravel (`storage/logs/laravel.log`)
Cherchez :
```
🔍 Recalcul lessons_used pour subscription_instance
✅ Lessons_used mis à jour pour subscription_instance
```

### Dans l'interface
- L'abonnement devrait afficher "7 / 10 cours utilisés (70%)"
- La carte devrait être en ORANGE (≥70%)
- Les périodes de validité devraient s'afficher

---

## Dépannage

### Le script SQL ne s'exécute pas
```bash
# Se connecter au conteneur MySQL
docker exec -it activibe-mysql-local bash

# Dans le conteneur
mysql -u activibe_user -pactivibe_password book_your_coach_local

# Copier-coller le contenu de create_test_subscription.sql
```

### "0 abonnements mis à jour"
Cela signifie que tous les compteurs sont déjà corrects ! 
Pour tester, modifiez manuellement un compteur :
```sql
UPDATE subscription_instances 
SET lessons_used = 0 
WHERE status = 'active' 
LIMIT 1;
```

Puis relancez le recalcul.

