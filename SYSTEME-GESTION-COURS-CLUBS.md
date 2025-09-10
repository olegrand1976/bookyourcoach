# 🏇 Système de Gestion des Cours pour Clubs Équestres

## 📋 Vue d'ensemble

Ce système permet aux clubs équestres de gérer efficacement leurs cours avec trois tableaux de bord principaux :

1. **Tableau de bord Club** : Calendrier des plages d'ouverture et gestion des installations
2. **Système d'Affectation** : Affectation automatique et manuelle des enseignants
3. **Tableau Récapitulatif** : Vue d'ensemble des affectations et alertes

## 🏗️ Architecture

### Modèles Principaux

#### `ClubFacility` - Installations du Club
- **Capacité** : Nombre de cours simultanés possibles
- **Types** : Manège, carrière, paddock, obstacles, etc.
- **Équipements** : Liste des équipements disponibles
- **Indoor/Outdoor** : Distinction intérieur/extérieur

#### `CourseSlot` - Plages Horaires
- **Récurrence** : Hebdomadaire avec dates de début/fin
- **Horaires** : Heure de début et fin
- **Capacité** : Nombre maximum d'élèves
- **Prix** : Tarif du cours

#### `TeacherContract` - Contrats Enseignants
- **Types** : CDI, CDD, Freelance, Saisonnier
- **Contraintes** : Heures min/max par semaine
- **Disponibilités** : Jours et horaires autorisés
- **Disciplines** : Spécialités autorisées/restreintes

#### `CourseAssignment` - Affectations
- **Statuts** : Affecté, Confirmé, Terminé, Annulé, Absent
- **Coûts** : Taux horaire et durée réelle
- **Suivi** : Dates de confirmation et completion

## 🎯 Fonctionnalités Principales

### 1. Tableau de Bord Club

#### Calendrier des Plages
```php
GET /api/clubs/{clubId}/course-dashboard
```
- Vue calendaire des plages d'ouverture
- Gestion des créneaux multiples par installation
- Statistiques de couverture

#### Gestion des Installations
```php
GET /api/clubs/{clubId}/facilities
POST /api/clubs/{clubId}/facilities
```
- Création et gestion des installations
- Configuration de la capacité simultanée
- Gestion des équipements

#### Création de Plages
```php
POST /api/clubs/{clubId}/course-slots
```
- Plages récurrentes ou ponctuelles
- Vérification des conflits d'horaires
- Association installation ↔ type de cours

### 2. Système d'Affectation

#### Affectation Automatique
```php
POST /api/clubs/{clubId}/assignments/auto-assign
```
- Algorithme intelligent de matching
- Respect des contraintes de contrat
- Priorisation par type de contrat et disponibilité

#### Affectation Manuelle
```php
POST /api/clubs/{clubId}/assignments/{assignmentId}/assign
```
- Sélection manuelle d'enseignants
- Vérification des contraintes en temps réel
- Gestion des conflits

#### Enseignants Disponibles
```php
GET /api/clubs/{clubId}/teachers/available
```
- Filtrage par jour, horaire, date
- Affichage des contraintes de contrat
- Statut de disponibilité

### 3. Tableau Récapitulatif

#### Résumé des Affectations
```php
GET /api/clubs/{clubId}/assignment-summary
```
- Vue d'ensemble par enseignant, installation, type de cours
- Statistiques de couverture
- Identification des affectations manquantes

#### Alertes
```php
GET /api/clubs/{clubId}/assignment-alerts
```
- Alertes critiques (≤ 1 jour)
- Alertes d'avertissement (≤ 3 jours)
- Priorisation par importance

#### Charge de Travail
```php
GET /api/clubs/{clubId}/teacher-workload
```
- Heures travaillées par enseignant
- Respect des contraintes min/max
- Détection de surcharge/sous-charge

## 🔧 Service d'Affectation Intelligente

### Algorithme de Scoring

Le service `TeacherAssignmentService` utilise un système de scoring pour optimiser les affectations :

```php
$score = 0;

// Priorité par type de contrat
$contractPriority = [
    'permanent' => 0,    // CDI prioritaire
    'temporary' => 1,   // CDD
    'seasonal' => 2,     // Saisonnier
    'freelance' => 3     // Freelance
];

// Heures déjà travaillées cette semaine
$score += $hoursThisWeek * 2;

// Note de l'enseignant (5 = meilleure note)
$score += (5 - $teacher->rating) * 3;

// Installation préférée
if (in_array($facility_id, $preferred_facilities)) {
    $score -= 2;
}
```

### Contraintes Respectées

1. **Contrat actif** pour la date donnée
2. **Jours autorisés** selon le contrat
3. **Horaires** dans les limites autorisées
4. **Disciplines** autorisées/restreintes
5. **Heures max** par semaine non dépassées
6. **Absence de conflits** d'horaires

## 📊 Exemples d'Utilisation

### Créer une Installation
```json
POST /api/clubs/1/facilities
{
    "name": "Manège principal",
    "type": "manège",
    "capacity": 2,
    "equipment": ["obstacles", "miroir", "sono"],
    "is_indoor": true
}
```

### Créer une Plage de Cours
```json
POST /api/clubs/1/course-slots
{
    "facility_id": 1,
    "course_type_id": 1,
    "name": "Cours dressage matin",
    "start_time": "09:00",
    "end_time": "10:00",
    "day_of_week": "monday",
    "start_date": "2025-01-15",
    "max_students": 8,
    "price": 45.00,
    "is_recurring": true
}
```

### Affectation Automatique
```json
POST /api/clubs/1/assignments/auto-assign
{
    "start_date": "2025-01-15",
    "end_date": "2025-01-21",
    "force_reassign": false
}
```

## 🚨 Gestion des Alertes

### Types d'Alertes

1. **Critiques** (≤ 1 jour) : Rouge
2. **Avertissement** (≤ 3 jours) : Orange
3. **Information** (> 3 jours) : Bleu

### Priorisation

- Cours du weekend : +2 priorité
- Cours avec > 6 élèves : +1 priorité
- Jours restants : priorité de base

## 📈 Statistiques Disponibles

### Tableau de Bord
- Taux d'affectation global
- Nombre de plages par installation
- Répartition par type de cours

### Charge de Travail
- Heures par enseignant
- Respect des contraintes min/max
- Coût total des affectations

### Alertes
- Nombre d'affectations manquantes
- Répartition par niveau de priorité
- Tendances temporelles

## 🔄 Optimisations Futures

1. **Machine Learning** : Apprentissage des préférences
2. **Prédiction** : Anticipation des besoins
3. **Intégration** : Synchronisation avec calendriers externes
4. **Mobile** : Application mobile pour enseignants
5. **Notifications** : Alertes push et emails

## 🛠️ Installation et Configuration

### Migrations
```bash
php artisan migrate
```

### Seeders
```bash
php artisan db:seed --class=ClubFacilitySeeder
php artisan db:seed --class=CourseSlotSeeder
php artisan db:seed --class=TeacherContractSeeder
```

### Permissions
- `club.manage_courses` : Gestion des cours
- `club.assign_teachers` : Affectation des enseignants
- `club.view_reports` : Consultation des rapports

---

Ce système offre une solution complète pour la gestion des cours dans les clubs équestres, avec une attention particulière aux contraintes contractuelles et à l'optimisation des affectations.
