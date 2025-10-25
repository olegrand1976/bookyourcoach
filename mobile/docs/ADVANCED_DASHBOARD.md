# Tableaux de Bord Avancés pour activibe

## Vue d'ensemble

activibe intègre maintenant des tableaux de bord avancés adaptatifs pour les versions web et mobile, spécialement conçus pour l'équitation avec gestion complète des disciplines équestres.

## Architecture des Disciplines Équestres

### 1. Modèles de Données

#### **EquestrianDiscipline**
```dart
class EquestrianDiscipline {
  final int id;
  final String name;
  final String code; // 'dressage', 'jumping', 'eventing', 'western', etc.
  final String description;
  final List<String> levels; // ['debutant', 'intermediaire', 'avance', 'expert']
  final bool isActive;
}
```

#### **StudentDiscipline**
```dart
class StudentDiscipline {
  final int studentId;
  final int disciplineId;
  final String currentLevel;
  final String? targetLevel;
  final double? currentScore;
  final double? targetScore;
  final DateTime startDate;
  final DateTime? lastAssessmentDate;
}
```

#### **PerformanceMetrics**
```dart
class PerformanceMetrics {
  final Map<String, dynamic> gpsData; // Données GPS
  final Map<String, dynamic> sensorData; // Données capteurs
  final Map<String, dynamic> videoData; // Données vidéo
  final Map<String, dynamic> assessmentData; // Notations officielles
  final double? overallScore;
}
```

### 2. Disciplines Supportées

| Discipline | Code | Description | Niveaux |
|------------|------|-------------|---------|
| **Dressage** | `dressage` | Dressage classique | Débutant → Expert |
| **Saut d'obstacles** | `jumping` | Concours hippique | Débutant → Expert |
| **Concours complet** | `eventing` | Dressage + Cross + Obstacles | Débutant → Expert |
| **Western** | `western` | Équitation western | Débutant → Expert |
| **Endurance** | `endurance` | Courses d'endurance | Débutant → Expert |

## Fonctionnalités du Tableau de Bord

### 1. Interface Web Adaptative

#### **Layout Desktop**
- **Sidebar** (280px) : Filtres, navigation rapide, alertes
- **Contenu principal** : Grille 2 colonnes avec métriques et graphiques
- **Responsive** : Adaptation automatique selon la taille d'écran

#### **Layout Mobile**
- **Tabs** : "Mes Disciplines" / "Disponibles"
- **Interface compacte** : Optimisée pour les écrans tactiles
- **Navigation fluide** : Swipe et tap gestures

### 2. Métriques de Performance

#### **Données GPS**
- Vitesse moyenne et maximale
- Distance parcourue
- Trajectoire et zones d'entraînement

#### **Données Capteurs**
- Rythme et cadence
- Équilibre et position
- Fréquence cardiaque (si disponible)

#### **Évaluations Officielles**
- Scores techniques
- Scores artistiques
- Commentaires des juges

### 3. Widgets Spécialisés

#### **DisciplineSelector**
- Sélection visuelle des disciplines
- Icônes spécifiques par discipline
- Filtrage en temps réel

#### **PerformanceChart**
- Graphique personnalisé avec CustomPainter
- Évolution temporelle des scores
- Indicateurs de tendance

#### **GoalsWidget**
- Objectifs d'entraînement
- Progression en pourcentage
- Alertes d'échéance

#### **AlertsWidget**
- Surcharge de chevaux
- Objectifs à échéance
- Baisse de performance
- Nouveaux badges

### 4. Intégrations Externes

#### **Equilab**
```dart
Future<Map<String, dynamic>> syncEquilabData(int studentId, String apiKey)
```
- Synchronisation automatique des données GPS
- Import des métriques de performance
- Historique des séances

#### **Stridera**
```dart
Future<Map<String, dynamic>> syncStrideraData(int studentId, String apiKey)
```
- Coaching vidéo annoté
- Analyse technique
- Comparaisons avec des références

## Gestion des Disciplines

### 1. Écran de Gestion

#### **Interface Web**
- **Sidebar** : Disciplines disponibles en grille
- **Contenu principal** : Disciplines de l'étudiant avec progression
- **Actions** : Ajouter, modifier, supprimer

#### **Interface Mobile**
- **Tabs** : Navigation entre "Mes Disciplines" et "Disponibles"
- **Cards** : Affichage compact avec actions contextuelles

### 2. Workflow d'Ajout

1. **Sélection** : Choisir une discipline disponible
2. **Configuration** : Niveau actuel et cible
3. **Validation** : Sauvegarde avec progression initiale

### 3. Suivi de Progression

#### **Calcul de Progression**
```dart
double _calculateProgress(StudentDiscipline studentDiscipline) {
  if (studentDiscipline.currentScore == null || 
      studentDiscipline.targetScore == null) {
    return 0.0;
  }
  
  final progress = studentDiscipline.currentScore! / 
                   studentDiscipline.targetScore!;
  return progress.clamp(0.0, 1.0);
}
```

## Services et Providers

### 1. EquestrianService

#### **Gestion des Disciplines**
```dart
Future<List<EquestrianDiscipline>> getDisciplines()
Future<StudentDiscipline> addStudentDiscipline(int studentId, Map<String, dynamic> data)
Future<void> removeStudentDiscipline(int studentId, int disciplineId)
```

#### **Métriques de Performance**
```dart
Future<List<PerformanceMetrics>> getPerformanceMetrics({
  int? studentId,
  int? disciplineId,
  DateTime? startDate,
  DateTime? endDate,
})
```

#### **Tableaux de Bord**
```dart
Future<DashboardStats> getDashboardStats({
  required int userId,
  required String userType,
  DateTime? startDate,
  DateTime? endDate,
})
```

### 2. Providers Riverpod

#### **DisciplinesProvider**
- État des disciplines disponibles
- Chargement et gestion d'erreurs
- Cache local pour performance

#### **StudentDisciplinesProvider**
- Disciplines de l'étudiant
- Actions CRUD complètes
- Synchronisation automatique

#### **PerformanceMetricsProvider**
- Métriques de performance
- Filtrage par discipline et période
- Graphiques en temps réel

## Utilisation

### 1. Accès au Tableau de Bord

```dart
// Navigation vers le tableau de bord avancé
Navigator.push(
  context,
  MaterialPageRoute(
    builder: (context) => const AdvancedDashboardScreen(),
  ),
);
```

### 2. Gestion des Disciplines

```dart
// Navigation vers la gestion des disciplines
Navigator.push(
  context,
  MaterialPageRoute(
    builder: (context) => const DisciplinesManagementScreen(),
  ),
);
```

### 3. Intégration dans le Menu Principal

```dart
// Ajouter dans le menu de navigation
ListTile(
  leading: const Icon(Icons.analytics),
  title: const Text('Tableau de Bord Avancé'),
  onTap: () => Navigator.pushNamed(context, '/advanced-dashboard'),
),
```

## Configuration Backend

### 1. Routes API Requises

```php
// Disciplines
GET /api/equestrian/disciplines
GET /api/equestrian/disciplines/{id}

// Disciplines des étudiants
GET /api/equestrian/students/{studentId}/disciplines
POST /api/equestrian/students/{studentId}/disciplines
PUT /api/equestrian/students/{studentId}/disciplines/{disciplineId}
DELETE /api/equestrian/students/{studentId}/disciplines/{disciplineId}

// Métriques de performance
GET /api/equestrian/performance-metrics
POST /api/equestrian/performance-metrics
PUT /api/equestrian/performance-metrics/{id}

// Tableaux de bord
GET /api/equestrian/dashboard/{userId}

// Intégrations
POST /api/equestrian/integrations/equilab/sync
POST /api/equestrian/integrations/stridera/sync
```

### 2. Structure de Base de Données

#### **Table `equestrian_disciplines`**
```sql
CREATE TABLE equestrian_disciplines (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  code VARCHAR(50) UNIQUE NOT NULL,
  description TEXT,
  icon_path VARCHAR(255),
  levels JSON,
  rules JSON,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **Table `student_disciplines`**
```sql
CREATE TABLE student_disciplines (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  discipline_id INT NOT NULL,
  current_level VARCHAR(50) NOT NULL,
  target_level VARCHAR(50),
  start_date DATE NOT NULL,
  last_assessment_date DATE,
  current_score DECIMAL(5,2),
  target_score DECIMAL(5,2),
  notes TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id),
  FOREIGN KEY (discipline_id) REFERENCES equestrian_disciplines(id)
);
```

#### **Table `performance_metrics`**
```sql
CREATE TABLE performance_metrics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  lesson_id INT NOT NULL,
  student_id INT NOT NULL,
  discipline_id INT NOT NULL,
  date DATE NOT NULL,
  gps_data JSON,
  sensor_data JSON,
  video_data JSON,
  assessment_data JSON,
  overall_score DECIMAL(5,2),
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lesson_id) REFERENCES lessons(id),
  FOREIGN KEY (student_id) REFERENCES students(id),
  FOREIGN KEY (discipline_id) REFERENCES equestrian_disciplines(id)
);
```

## Avantages

### 1. Pour les Élèves
- **Suivi personnalisé** : Progression par discipline
- **Objectifs clairs** : Niveaux cibles et échéances
- **Feedback visuel** : Graphiques et métriques
- **Motivation** : Badges et accomplissements

### 2. Pour les Professeurs
- **Vue d'ensemble** : Tous les élèves et leurs progrès
- **Alertes intelligentes** : Surcharge, échéances
- **Analyses détaillées** : Métriques par discipline
- **Planification** : Objectifs et évaluations

### 3. Pour l'Administration
- **Statistiques globales** : Performance de la plateforme
- **Intégrations** : Synchronisation avec outils externes
- **Export** : Rapports Excel/PDF
- **Monitoring** : Alertes et notifications

## Évolutions Futures

### 1. Fonctionnalités Avancées
- **IA prédictive** : Recommandations d'entraînement
- **Reconnaissance vidéo** : Analyse automatique des mouvements
- **Communauté** : Partage de progrès et défis
- **Gamification** : Système de points et classements

### 2. Intégrations Supplémentaires
- **MyHorse** : Gestion des chevaux
- **FEI** : Standards officiels
- **FFE** : Fédération française d'équitation
- **Wearables** : Montres et capteurs connectés

Cette implémentation offre une solution complète et moderne pour la gestion des disciplines équestres, avec une interface adaptative qui s'adapte parfaitement aux besoins des utilisateurs web et mobile.
