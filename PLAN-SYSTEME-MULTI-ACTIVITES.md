# 🏊‍♂️🐎 SYSTÈME MULTI-ACTIVITÉS : ÉQUITATION & NATATION

## 📊 Analyse Comparative des Besoins

### **1. CLUB D'ÉQUITATION** 🐎

#### **Emplacements Spécifiques**
- **Manèges couverts** : 1-3 par club, capacité 1-4 cavaliers simultanés
- **Carrières extérieures** : 1-2 par club, capacité 1-6 cavaliers simultanés  
- **Paddocks** : Zones de détente, capacité illimitée
- **Écuries** : Stockage matériel, capacité selon nombre de boxes

#### **Calendriers par Emplacement**
- **Manège 1** : 6h-22h, créneaux de 1h, max 4 cours simultanés
- **Carrière A** : 7h-20h, créneaux de 1h30, max 6 cours simultanés
- **Paddock** : 8h-18h, créneaux libres, capacité flexible

#### **Disciplines Équestres**
- **Dressage** : 1-2 cavaliers, matériel spécifique
- **CSO (Saut d'obstacles)** : 1-4 cavaliers, obstacles variables
- **CCE (Concours Complet)** : 1-2 cavaliers, terrain varié
- **Balade** : 2-8 cavaliers, parcours extérieur
- **Voltige** : 1-3 cavaliers, matériel spécialisé
- **Attelage** : 1-2 attelages, matériel spécifique

#### **Caractéristiques Spécifiques**
- **Saisonnalité** : Forte variation (été: +80%, hiver: -40%)
- **Météo** : Impact majeur sur les cours extérieurs
- **Équipements** : Chevaux, selles, casques, bottes, matériel de soins
- **Maintenance** : Soins des chevaux, entretien des installations

---

### **2. CENTRE DE NATATION** 🏊‍♂️

#### **Emplacements Spécifiques**
- **Bassin 25m** : 1-2 par centre, capacité 8-16 nageurs simultanés
- **Bassin 50m** : 0-1 par centre, capacité 12-24 nageurs simultanés
- **Piscine enfants** : 1 par centre, capacité 6-12 enfants simultanés
- **Jacuzzi/Spa** : 1-2 par centre, capacité 4-8 personnes simultanées

#### **Calendriers par Emplacement**
- **Bassin 25m** : 6h-23h, créneaux de 45min, max 2 cours simultanés
- **Bassin 50m** : 6h-22h, créneaux de 1h, max 3 cours simultanés
- **Piscine enfants** : 9h-19h, créneaux de 30min, max 1 cours simultané
- **Jacuzzi** : 8h-22h, créneaux de 30min, capacité libre

#### **Disciplines Aquatiques**
- **Natation sportive** : 8-16 nageurs, niveaux débutant à expert
- **Aquagym** : 12-20 participants, matériel aquatique
- **Aquabike** : 8-12 participants, vélos aquatiques
- **Bébés nageurs** : 6-8 bébés + parents, eau chauffée
- **Aquaphobie** : 4-8 participants, approche progressive
- **Natation synchronisée** : 8-12 participants, chorégraphie

#### **Caractéristiques Spécifiques**
- **Saisonnalité** : Constante toute l'année (+/- 10%)
- **Météo** : Aucun impact (intérieur)
- **Équipements** : Matériel aquatique, chronomètres, flotteurs, planches
- **Maintenance** : Traitement de l'eau, nettoyage, sécurité

---

## 🏗️ Architecture Technique à Implémenter

### **1. Base de Données**

#### **Nouvelle Table : `activity_types`**
```sql
- id (PK)
- name (varchar) : "Équitation", "Natation"
- slug (varchar) : "equestrian", "swimming"
- description (text)
- icon (varchar) : "🐎", "🏊‍♂️"
- color (varchar) : "#8B4513", "#0066CC"
- is_active (boolean)
- created_at, updated_at
```

#### **Nouvelle Table : `facilities`**
```sql
- id (PK)
- activity_type_id (FK)
- name (varchar) : "Manège 1", "Bassin 25m"
- type (enum) : "indoor", "outdoor", "covered"
- capacity (integer) : nombre max de participants
- dimensions (json) : {"length": 20, "width": 40, "depth": 1.5}
- equipment (json) : équipements disponibles
- is_active (boolean)
- created_at, updated_at
```

#### **Nouvelle Table : `disciplines`**
```sql
- id (PK)
- activity_type_id (FK)
- name (varchar) : "Dressage", "Aquagym"
- slug (varchar) : "dressage", "aquagym"
- description (text)
- min_participants (integer)
- max_participants (integer)
- duration_minutes (integer)
- equipment_required (json)
- skill_levels (json) : ["débutant", "intermédiaire", "expert"]
- is_active (boolean)
- created_at, updated_at
```

#### **Modification Table : `clubs`**
```sql
- activity_type_id (FK) : type d'activité principal
- facilities (json) : installations disponibles
- disciplines (json) : disciplines proposées
- seasonal_variation (decimal) : variation saisonnière
- weather_dependency (boolean) : dépendance météo
```

### **2. Modèles Laravel**

#### **ActivityType Model**
```php
- relationships: facilities(), disciplines(), clubs()
- scopes: active(), bySlug()
- methods: getIcon(), getColor()
```

#### **Facility Model**
```php
- relationships: activityType(), lessons(), availabilities()
- scopes: byActivityType(), active()
- methods: getCapacity(), getDimensions()
```

#### **Discipline Model**
```php
- relationships: activityType(), lessons(), courseTypes()
- scopes: byActivityType(), active()
- methods: getSkillLevels(), getEquipmentRequired()
```

### **3. Contrôleurs API**

#### **ActivityTypeController**
- `index()` : Liste des types d'activités
- `show($id)` : Détails d'un type
- `facilities($id)` : Installations d'un type
- `disciplines($id)` : Disciplines d'un type

#### **FacilityController**
- `index()` : Liste des installations
- `show($id)` : Détails d'une installation
- `availability($id)` : Disponibilités d'une installation
- `schedule($id)` : Planning d'une installation

### **4. Frontend**

#### **Composants Spécifiques**
- `ActivityTypeSelector.vue` : Sélection du type d'activité
- `FacilityCard.vue` : Carte d'installation avec caractéristiques
- `DisciplineCard.vue` : Carte de discipline avec détails
- `SeasonalChart.vue` : Graphique de variation saisonnière

#### **Pages Adaptées**
- `/clubs/equestrian` : Clubs d'équitation
- `/clubs/swimming` : Centres de natation
- `/facilities/:type` : Installations par type
- `/disciplines/:type` : Disciplines par type

### **5. Système de Réservation Adaptatif**

#### **Logique de Réservation**
- **Équitation** : Réservation par cheval + instructeur + installation
- **Natation** : Réservation par créneau + instructeur + bassin
- **Capacité** : Calcul dynamique selon installation et discipline
- **Saisonnalité** : Prix et disponibilité selon période

#### **Calendrier Intelligent**
- **Équitation** : Gestion météo, saisons, soins chevaux
- **Natation** : Créneaux fixes, maintenance bassins
- **Conflits** : Détection automatique des conflits de réservation

---

## 🚀 Plan d'Implémentation

### **Phase 1 : Structure de Base**
1. Créer les migrations pour les nouvelles tables
2. Créer les modèles avec relations
3. Créer les contrôleurs API de base

### **Phase 2 : Données de Test**
1. Seeder pour les types d'activités
2. Seeder pour les installations (manèges, bassins)
3. Seeder pour les disciplines (dressage, aquagym, etc.)

### **Phase 3 : Interface Utilisateur**
1. Composants de sélection de type d'activité
2. Adaptation des dashboards par type
3. Interface de gestion des installations

### **Phase 4 : Logique Métier**
1. Système de réservation adaptatif
2. Calcul de capacité dynamique
3. Gestion des conflits et disponibilités

### **Phase 5 : Optimisations**
1. Cache des données fréquentes
2. Optimisation des requêtes
3. Tests de performance

---

**Prochaine étape** : Commencer par la Phase 1 avec la création de la structure de base de données et des modèles.
