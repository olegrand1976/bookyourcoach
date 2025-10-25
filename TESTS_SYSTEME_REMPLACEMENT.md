# Tests du Système de Remplacement d'Enseignants

**Date**: 24 octobre 2025  
**Objectif**: Vérifier le bon fonctionnement complet du système de remplacement d'enseignants

---

## 📋 Points Implémentés

### 1. Backend

#### ✅ Base de données
- [x] Table `lesson_replacements` créée avec:
  - `lesson_id` (FK vers lessons)
  - `original_teacher_id` (FK vers teachers)
  - `replacement_teacher_id` (FK vers teachers)
  - `status` (pending, accepted, rejected, cancelled)
  - `reason` (raison du remplacement)
  - `notes` (notes supplémentaires)
  - `requested_at` & `responded_at` (timestamps)

#### ✅ Modèle `LessonReplacement`
- [x] Relations: `lesson()`, `originalTeacher()`, `replacementTeacher()`
- [x] Fillable et casts configurés
- [x] Fichier: `app/Models/LessonReplacement.php`

#### ✅ Controller `LessonReplacementController`
- [x] `index()`: Liste des remplacements (envoyés + reçus)
- [x] `store()`: Créer une demande avec validations:
  - Vérification que le cours appartient au demandeur
  - Vérification que le cours n'est pas passé
  - Vérification de disponibilité du remplaçant
  - Empêche les demandes multiples pour un même cours
- [x] `respond()`: Accepter/refuser une demande:
  - Mise à jour automatique du `teacher_id` du cours
  - Vérification des droits (seul le remplaçant peut répondre)
- [x] `cancel()`: Annuler une demande (seul le demandeur)
- [x] Fichier: `app/Http/Controllers/Api/LessonReplacementController.php`

#### ✅ Controller `TeacherController`
- [x] `index()`: Liste tous les enseignants (sauf l'utilisateur actuel)
- [x] Fichier: `app/Http/Controllers/Api/TeacherController.php`

#### ✅ Controller `LessonController`
- [x] Ajout de la relation `club` dans le chargement des cours
- [x] Fichier: `app/Http/Controllers/Api/LessonController.php`

#### ✅ Routes API
- [x] `GET /teacher/lessons` - Liste des cours
- [x] `GET /teacher/teachers` - Liste des enseignants
- [x] `GET /teacher/lesson-replacements` - Liste des remplacements
- [x] `POST /teacher/lesson-replacements` - Créer une demande
- [x] `POST /teacher/lesson-replacements/{id}/respond` - Répondre (accept/reject)
- [x] `DELETE /teacher/lesson-replacements/{id}` - Annuler
- [x] Fichier: `routes/api.php`

### 2. Frontend

#### ✅ Dashboard Enseignant (`/teacher/dashboard`)
- [x] Statistiques:
  - Cours aujourd'hui
  - Total cours
  - Nombre de remplacements
  - Nombre de clubs
- [x] Notifications en temps réel des demandes en attente
- [x] Tableau des cours avec:
  - Club
  - Date/Heure
  - Type de cours (durée, prix)
  - Élève (nom + âge)
  - Statut (visuel avec couleurs)
  - Actions: "👁️ Voir" et "🔄 Remplacer"
- [x] Boutons Accepter/Refuser pour les remplacements reçus
- [x] Fichier: `frontend/pages/teacher/dashboard.vue`

#### ✅ Modale Fiche Détaillée du Cours
- [x] Informations complètes:
  - Date et horaire
  - Club
  - Type de cours
  - Durée et prix
  - Élève (nom + âge)
  - Professeur
  - Statut (badge coloré)
  - Notes
- [x] Bouton "Demander un remplacement"
- [x] Fichier: `frontend/components/teacher/LessonDetailsModal.vue`

#### ✅ Modale Demande de Remplacement
- [x] Infos du cours à remplacer
- [x] Sélection du professeur de remplacement
- [x] Raison (liste déroulante):
  - Indisponibilité personnelle
  - Problème de santé
  - Urgence familiale
  - Conflit d'horaire
  - Autre
- [x] Notes supplémentaires (textarea)
- [x] Validation et gestion d'erreurs
- [x] Fichier: `frontend/components/teacher/ReplacementRequestModal.vue`

---

## 🧪 Plan de Tests

### Test 1: Création de la table
```bash
# Vérifier que la table existe
docker-compose -f docker-compose.local.yml exec backend mysql -u root -proot activibe -e "DESCRIBE lesson_replacements;"
```

### Test 2: Accès au Dashboard Enseignant
1. Se connecter avec un compte enseignant
2. Accéder à `/teacher/dashboard`
3. Vérifier l'affichage des statistiques
4. Vérifier l'affichage du tableau des cours

### Test 3: Affichage Fiche Détaillée
1. Dans le dashboard, cliquer sur "👁️ Voir" d'un cours
2. Vérifier que la modale s'ouvre
3. Vérifier que toutes les informations sont affichées
4. Vérifier l'affichage de l'âge de l'élève

### Test 4: Créer une Demande de Remplacement
1. Cliquer sur "🔄 Remplacer" ou "Demander un remplacement" dans la modale
2. Vérifier que la liste des enseignants s'affiche
3. Sélectionner un enseignant
4. Choisir une raison
5. Ajouter des notes (optionnel)
6. Soumettre
7. Vérifier la création en DB

### Test 5: Notifications de Remplacement
1. Se connecter avec le compte de l'enseignant remplaçant
2. Accéder à `/teacher/dashboard`
3. Vérifier l'affichage du bandeau orange avec les demandes en attente
4. Vérifier les détails de la demande

### Test 6: Accepter un Remplacement
1. Dans le bandeau de notification, cliquer sur "✓ Accepter"
2. Vérifier que le cours est assigné au nouveau professeur
3. Vérifier que le statut passe à "accepted"
4. Vérifier que le bandeau disparaît

### Test 7: Refuser un Remplacement
1. Créer une nouvelle demande
2. Se connecter avec le remplaçant
3. Cliquer sur "✗ Refuser"
4. Vérifier que le statut passe à "rejected"
5. Vérifier que le cours reste assigné au professeur d'origine

### Test 8: Validations Backend
```bash
# Test: Cours passé
curl -X POST http://localhost:8080/api/teacher/lesson-replacements \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"lesson_id": 999, "replacement_teacher_id": 2, "reason": "Test"}'

# Test: Disponibilité du remplaçant
# (créer 2 cours au même horaire pour tester)

# Test: Demande multiple pour le même cours
# (créer 2 demandes pour le même cours)
```

### Test 9: Vérifications de Sécurité
- [x] Seul le professeur d'un cours peut demander un remplacement
- [x] Seul le remplaçant désigné peut accepter/refuser
- [x] Seul le demandeur peut annuler une demande en attente
- [x] Impossible de remplacer un cours passé

### Test 10: Intégration Complète (Scénario réel)
1. **Enseignant A** se connecte
2. **Enseignant A** voit ses cours du jour
3. **Enseignant A** clique sur un cours et voit la fiche détaillée
4. **Enseignant A** demande un remplacement à **Enseignant B**
5. **Enseignant B** se connecte
6. **Enseignant B** voit la notification de demande
7. **Enseignant B** accepte
8. **Vérification**: Le cours est maintenant assigné à **Enseignant B**
9. **Enseignant A** se reconnecte et ne voit plus ce cours
10. **Enseignant B** voit ce cours dans sa liste

---

## 📊 Résultats Attendus

### Base de données
- La table `lesson_replacements` existe
- Les contraintes de clés étrangères sont actives
- Les index sont créés

### API
- Toutes les routes retournent les bonnes réponses (200, 201, 400, 403, 404, 500)
- Les validations fonctionnent
- Les relations sont bien chargées (eager loading)

### Interface
- Les modales s'ouvrent et se ferment correctement
- Les formulaires valident les données
- Les notifications s'affichent au bon moment
- L'âge de l'élève est affiché partout
- Les boutons sont actifs/désactivés selon le contexte

### Sécurité
- Impossible d'accepter un remplacement qui ne nous concerne pas
- Impossible de demander un remplacement pour un cours qui ne nous appartient pas
- Les tokens sont vérifiés
- Les rôles sont respectés

---

## 🐛 Problèmes Potentiels à Surveiller

1. **Timezone**: Vérifier que les heures sont cohérentes (Europe/Paris)
2. **Eager Loading**: Vérifier que `student.age` est bien calculé
3. **Conflits horaires**: La détection de disponibilité doit être précise
4. **Race conditions**: Deux profs acceptant en même temps
5. **Transactions**: Rollback en cas d'erreur lors de l'acceptation

---

## 🚀 Commandes Utiles

```bash
# Vérifier les logs backend
docker-compose -f docker-compose.local.yml logs -f backend

# Vérifier les logs frontend
docker-compose -f docker-compose.local.yml logs -f frontend

# Accéder à la DB
docker-compose -f docker-compose.local.yml exec backend mysql -u root -proot activibe

# Vider le cache
docker-compose -f docker-compose.local.yml exec backend php artisan cache:clear
docker-compose -f docker-compose.local.yml exec backend php artisan config:clear

# Relancer les services
docker-compose -f docker-compose.local.yml restart
```

---

## ✅ Checklist de Validation Finale

- [ ] La table existe en DB
- [ ] Toutes les routes API répondent
- [ ] Le dashboard enseignant s'affiche correctement
- [ ] La modale de détails s'ouvre
- [ ] La modale de demande de remplacement fonctionne
- [ ] Les notifications s'affichent
- [ ] L'acceptation met à jour le cours
- [ ] Le refus laisse le cours inchangé
- [ ] L'âge de l'élève est affiché partout
- [ ] Les validations empêchent les actions invalides
- [ ] Les erreurs sont gérées gracieusement

