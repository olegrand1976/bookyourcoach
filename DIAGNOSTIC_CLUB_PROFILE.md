# 🔍 Diagnostic du Profil Club - Guide d'Utilisation

## Problème Signalé
**Route:** `club/profile`  
**Symptôme:** Tous les champs ne sont pas enregistrés en base de données

---

## 🛠️ Outils de Diagnostic Créés

### 1. Page Web de Diagnostic
**URL:** `http://localhost:3000/club/diagnose`

**Fonctionnalités:**
- ✅ Affiche toutes les colonnes présentes dans la table `clubs`
- ✅ Vérifie l'existence des 10 champs légaux nécessaires
- ✅ Affiche les valeurs actuelles enregistrées dans votre club
- ✅ Indique quels champs sont vides ou manquants
- ✅ Bouton pour rafraîchir le diagnostic

**Comment l'utiliser:**
1. Connectez-vous en tant que Club
2. Allez sur `/club/diagnose`
3. Vérifiez les sections:
   - **Total Colonnes** : nombre de colonnes dans la table
   - **Champs Légaux Présents** : doit afficher 10/10
   - **État des Champs Légaux** : chaque champ doit avoir ✅
   - **Données Actuelles** : vos valeurs enregistrées

---

### 2. Endpoint API de Diagnostic
**Endpoint:** `GET /api/club/diagnose-columns`

**Utilisation:**
```bash
# Avec curl (remplacez TOKEN par votre token)
curl -H "Authorization: Bearer TOKEN" \
     http://localhost:8000/api/club/diagnose-columns
```

**Réponse JSON:**
```json
{
  "success": true,
  "all_columns": ["id", "name", "company_number", ...],
  "legal_fields_status": {
    "company_number": "EXISTS",
    "legal_representative_name": "EXISTS",
    ...
  },
  "current_club_data": {
    "company_number": {
      "value": "BE 0123.456.789",
      "is_empty": false,
      "type": "string"
    },
    ...
  },
  "total_columns": 45,
  "legal_fields_existing": 10
}
```

---

### 3. Commande Artisan (Backend)
**Commande:** `php artisan club:diagnose-profile [club_id]`

**Exemples:**
```bash
# Diagnostic global
php artisan club:diagnose-profile

# Diagnostic d'un club spécifique
php artisan club:diagnose-profile 1
```

**Output:**
- Liste de toutes les colonnes de la table
- Vérification des champs légaux (✅ ou ❌)
- Données du club sélectionné
- Pourcentage de complétion pour chaque club

---

## 📋 Champs Légaux Vérifiés

1. `company_number` - Numéro d'entreprise
2. `legal_representative_name` - Nom du représentant légal
3. `legal_representative_role` - Fonction du représentant
4. `insurance_rc_company` - Compagnie d'assurance RC
5. `insurance_rc_policy_number` - Numéro de police RC
6. `insurance_additional_company` - Assurance complémentaire
7. `insurance_additional_policy_number` - Numéro police complémentaire
8. `insurance_additional_details` - Détails assurance
9. `expense_reimbursement_type` - Type de défraiement
10. `expense_reimbursement_details` - Détails défraiement

---

## 🔎 Logs Ajoutés

Des logs détaillés ont été ajoutés dans `ClubController::updateProfile()` :

### Log 1 : Données Reçues
```php
'legal_fields_received' => [
  'company_number' => 'BE 0123.456.789',
  'legal_representative_name' => 'Jean Dupont',
  ...
]
```

### Log 2 : Avant UPDATE
```php
'data' => [...], // Données qui vont être sauvegardées
'existing_columns' => [...], // Colonnes détectées en DB
'all_data_received' => [...], // Toutes les données reçues
'filtered_out_fields' => [...] // Champs ignorés (pourquoi?)
```

### Log 3 : Après UPDATE
```php
'legal_fields_after_update' => [
  'company_number' => 'BE 0123.456.789',
  'legal_representative_name' => 'Jean Dupont',
  ...
]
```

**Emplacement des logs:**
```bash
# En local
tail -f storage/logs/laravel.log | grep 'ClubController::updateProfile'

# Ou filtrer pour un utilisateur
grep 'ClubController::updateProfile' storage/logs/laravel.log | grep 'user_id: X'
```

---

## 🧪 Procédure de Test Complète

### Étape 1 : Diagnostic Initial
1. Allez sur `/club/diagnose`
2. **Notez** combien de champs légaux sont présents (doit être 10/10)
3. **Notez** les valeurs actuelles de vos champs

### Étape 2 : Test de Sauvegarde
1. Allez sur `/club/profile`
2. Remplissez **TOUS** les champs du formulaire, y compris:
   - Numéro d'entreprise
   - Représentant légal (nom + fonction)
   - Assurance RC (compagnie + numéro)
   - Assurance complémentaire (facultatif)
   - Type de défraiement + détails
3. Cliquez sur **"Enregistrer"**
4. Attendez le message de succès

### Étape 3 : Vérification
1. Retournez sur `/club/diagnose`
2. Cliquez sur **"🔄 Rafraîchir"**
3. **Vérifiez** dans "Données Actuelles de Votre Club" que :
   - Tous les champs que vous avez remplis ont leurs valeurs
   - Aucun champ ne dit "COLUMN_NOT_EXISTS"

### Étape 4 : Analyse des Logs (Optionnel)
```bash
# Voir les logs de la dernière sauvegarde
tail -100 storage/logs/laravel.log | grep -A 20 'ClubController::updateProfile'
```

**Cherchez:**
- `legal_fields_received` : Les champs envoyés par le frontend
- `filtered_out_fields` : Les champs qui ont été supprimés (IMPORTANT!)
- `legal_fields_after_update` : Les champs réellement enregistrés

---

## ❓ Problèmes Possibles

### Problème 1 : Colonnes Manquantes en DB
**Symptôme:** `/club/diagnose` affiche "❌ Manquant" pour certains champs

**Cause:** Les migrations n'ont pas été exécutées

**Solution:**
```bash
# Vérifier le statut des migrations
php artisan migrate:status

# Exécuter les migrations manquantes
php artisan migrate

# Si erreur "Duplicate column", marquer comme exécuté
php artisan migrate --pretend
php artisan migrate:status
```

### Problème 2 : Champs Filtrés par le Backend
**Symptôme:** Les logs montrent des champs dans `filtered_out_fields`

**Cause:** Le système `getTableColumns()` ne détecte pas les colonnes

**Solution:** Vérifier que les colonnes existent vraiment :
```sql
DESCRIBE clubs;
-- ou
SHOW COLUMNS FROM clubs;
```

### Problème 3 : Valeurs Vides Après Sauvegarde
**Symptôme:** `/club/diagnose` montre `(vide)` ou `NULL`

**Causes possibles:**
1. Les champs n'ont pas été remplis dans le formulaire
2. Le frontend ne les envoie pas (vérifier console navigateur)
3. Conversion en `NULL` si chaîne vide (ligne 300-306 du contrôleur)

**Solution:**
1. Vérifier le `formData` dans le frontend
2. Vérifier les logs `legal_fields_received`
3. Comparer avec `data` avant l'UPDATE

---

## 📊 Cas d'Usage Typiques

### Cas 1 : Migration Non Exécutée
```
Diagnostic → 5/10 champs légaux
Solution → php artisan migrate
```

### Cas 2 : Formulaire Non Rempli
```
Diagnostic → 10/10 colonnes OK, mais valeurs vides
Solution → Remplir le formulaire et sauvegarder
```

### Cas 3 : Bug Backend
```
Diagnostic → 10/10 colonnes OK
Logs → Champs dans filtered_out_fields
Solution → Bug de filtrage, contactez le développeur
```

---

## ✅ Résultat Attendu

Après correction, vous devriez avoir :

✅ **Diagnostic:** 10/10 champs légaux présents  
✅ **Formulaire:** Tous les champs remplis  
✅ **Sauvegarde:** Message de succès  
✅ **Vérification:** Toutes les valeurs présentes dans `/club/diagnose`  
✅ **Logs:** Aucun champ dans `filtered_out_fields`  

---

## 📞 Support

Si le problème persiste après ces vérifications :

1. **Prenez une capture** de la page `/club/diagnose`
2. **Copiez les logs** de `ClubController::updateProfile`
3. **Notez** exactement quels champs ne sont pas sauvegardés
4. **Contactez** le développeur avec ces informations

---

*Dernière mise à jour : 2025-10-30*
