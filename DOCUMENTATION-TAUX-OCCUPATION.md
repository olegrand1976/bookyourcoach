# 📊 CALCUL DU TAUX D'OCCUPATION DES CLUBS

## 🎯 Définition

Le **taux d'occupation** représente le pourcentage d'étudiants inscrits par rapport à la capacité maximale du club.

## 🧮 Formule de Calcul

```php
occupancy_rate = (nombre_étudiants_inscrits / capacité_maximale) × 100
```

### Code Source
```php
'occupancy_rate' => $club->max_students > 0 ? 
    round(($club->students()->count() / $club->max_students) * 100, 2) : 0
```

## 📋 Composants du Calcul

### 1. **Nombre d'Étudiants Inscrits**
- **Source** : Table `club_user` avec `role = 'student'`
- **Méthode** : `$club->students()->count()`
- **Inclut** : Tous les étudiants actuellement associés au club

### 2. **Capacité Maximale**
- **Source** : Colonne `max_students` de la table `clubs`
- **Type** : Entier (integer)
- **Définition** : Nombre maximum d'étudiants que le club peut accueillir

### 3. **Protection contre Division par Zéro**
- **Condition** : `$club->max_students > 0`
- **Si `max_students = 0` ou `null`** : Taux d'occupation = 0%

## 📊 Exemples Concrets

### Exemple 1 : Club avec Capacité Définie
```
Club : "Club Équestre de Test"
- Étudiants inscrits : 5
- Capacité maximale : 20
- Taux d'occupation : (5 / 20) × 100 = 25%
```

### Exemple 2 : Club à Capacité Maximale
```
Club : "Centre Équestre Complet"
- Étudiants inscrits : 15
- Capacité maximale : 15
- Taux d'occupation : (15 / 15) × 100 = 100%
```

### Exemple 3 : Club sans Limite Définie
```
Club : "École Sans Limite"
- Étudiants inscrits : 8
- Capacité maximale : 0 (non définie)
- Taux d'occupation : 0% (protection contre division par zéro)
```

## 🔍 Où est Utilisé le Taux d'Occupation ?

### 1. **Dashboard des Clubs**
- Affiché dans la carte "Taux d'occupation"
- Format : `XX%` avec 2 décimales

### 2. **API `/api/club/dashboard`**
- Retourné dans la réponse JSON
- Clé : `stats.occupancy_rate`

### 3. **Interface Frontend**
- Composant : `frontend/pages/club/dashboard.vue`
- Affichage : Carte avec icône et pourcentage

## ⚙️ Configuration et Paramètres

### Définition de la Capacité Maximale
```php
// Dans la migration clubs
$table->integer('max_students')->nullable();

// Dans le modèle Club
protected $casts = [
    'max_students' => 'integer'
];
```

### Mise à Jour du Taux
- **Fréquence** : Calculé en temps réel à chaque appel API
- **Déclencheur** : Accès au dashboard du club
- **Performance** : Calcul optimisé avec une seule requête

## 🎯 Interprétation des Valeurs

| Taux | Interprétation | Action Recommandée |
|------|----------------|-------------------|
| 0-25% | Faible occupation | Promouvoir le club |
| 26-50% | Occupation modérée | Maintenir les efforts |
| 51-75% | Bonne occupation | Optimiser les créneaux |
| 76-99% | Forte occupation | Prévoir l'expansion |
| 100% | Capacité maximale | Limiter les inscriptions |

## 🔧 Améliorations Possibles

### 1. **Calcul Plus Sophistiqué**
```php
// Taux basé sur les cours réservés plutôt que les inscriptions
$totalBookedSlots = Lesson::whereIn('teacher_id', $teacherIds)
    ->where('status', 'confirmed')
    ->where('start_time', '>=', now())
    ->count();

$totalAvailableSlots = $club->teachers()->count() * $averageSlotsPerTeacher;
$occupancy_rate = ($totalBookedSlots / $totalAvailableSlots) * 100;
```

### 2. **Historique des Taux**
- Stocker les taux d'occupation par période
- Permettre l'analyse des tendances
- Créer des graphiques d'évolution

### 3. **Taux par Discipline**
- Calculer le taux d'occupation par discipline équestre
- Identifier les disciplines les plus/moins populaires
- Optimiser l'allocation des ressources

## 📈 Métriques Associées

Le taux d'occupation est souvent analysé avec :
- **Revenus totaux** : Corrélation occupation/revenus
- **Nombre de cours** : Activité du club
- **Satisfaction des étudiants** : Qualité vs quantité
- **Taux de rétention** : Fidélisation des étudiants

---

**Fichier Source** : `app/Http/Controllers/Api/ClubController.php` (ligne 67-68)  
**Dernière Mise à Jour** : $(date)
