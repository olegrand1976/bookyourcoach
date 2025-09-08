# 📚 Fonctionnalités Enseignant - activibe Mobile

## 🎯 Vue d'ensemble

L'application mobile activibe offre un ensemble complet de fonctionnalités dédiées aux enseignants pour gérer leurs cours, disponibilités, étudiants et statistiques.

## 🏗️ Architecture Technique

### 📁 Structure des Fichiers

```
mobile/lib/
├── models/
│   ├── user.dart              # Modèle utilisateur avec gestion des rôles
│   ├── lesson.dart            # Modèle pour les cours
│   └── availability.dart      # Modèle pour les disponibilités
├── services/
│   ├── auth_service.dart      # Service d'authentification
│   └── teacher_service.dart   # Service pour les fonctionnalités enseignantes
├── providers/
│   ├── auth_provider.dart     # Providers d'authentification
│   └── teacher_provider.dart  # Providers pour les fonctionnalités enseignantes
├── screens/
│   ├── login_screen.dart      # Écran de connexion
│   ├── home_screen.dart       # Écran d'accueil général
│   ├── teacher_dashboard.dart # Tableau de bord enseignant
│   └── teacher_lessons_screen.dart # Gestion des cours
└── widgets/
    ├── custom_button.dart     # Boutons personnalisés
    └── custom_text_field.dart # Champs de texte personnalisés
```

## 🔧 Modèles de Données

### 👤 Modèle User
```dart
class User {
  final int id;
  final String name;
  final String email;
  final List<String> roles;
  final Map<String, dynamic>? profile;
  final String? avatar;
  
  // Méthodes utilitaires
  bool get isAdmin => roles.contains('admin');
  bool get isTeacher => roles.contains('teacher') || isAdmin;
  bool get isStudent => roles.contains('student') || isTeacher;
  String get displayName => // Nom d'affichage personnalisé
  String get avatarUrl => // URL de l'avatar ou image par défaut
}
```

### 📖 Modèle Lesson
```dart
class Lesson {
  final int id;
  final String title;
  final String description;
  final DateTime startTime;
  final DateTime endTime;
  final String status; // 'scheduled', 'in_progress', 'completed', 'cancelled'
  final int teacherId;
  final int? studentId;
  final String? location;
  final double? price;
  final String? notes;
  final User? teacher;
  final User? student;
  
  // Méthodes utilitaires
  bool get isScheduled => status == 'scheduled';
  bool get isInProgress => status == 'in_progress';
  bool get isCompleted => status == 'completed';
  bool get isCancelled => status == 'cancelled';
  Duration get duration => endTime.difference(startTime);
  String get formattedTime => // Formatage des horaires
  String get formattedDate => // Formatage de la date
  String get statusDisplay => // Statut traduit en français
}
```

### ⏰ Modèle Availability
```dart
class Availability {
  final int id;
  final int teacherId;
  final DateTime startTime;
  final DateTime endTime;
  final String dayOfWeek; // 'monday', 'tuesday', etc.
  final bool isAvailable;
  final String? notes;
  
  // Méthodes utilitaires
  String get dayOfWeekDisplay => // Jour traduit en français
  String get formattedTime => // Formatage des horaires
  Duration get duration => endTime.difference(startTime);
}
```

## 🚀 Services API

### 🔐 Service d'Authentification
```dart
class AuthService {
  Future<Map<String, dynamic>> login(String email, String password);
  Future<bool> logout();
  Future<bool> isLoggedIn();
  Future<String?> getToken();
  Future<User?> getUser();
}
```

### 👨‍🏫 Service Enseignant
```dart
class TeacherService {
  // Gestion des cours
  Future<List<Lesson>> getTeacherLessons({String? status, DateTime? date});
  Future<Lesson> createLesson({required String title, ...});
  Future<Lesson> updateLesson({required int lessonId, ...});
  Future<bool> deleteLesson(int lessonId);
  
  // Gestion des disponibilités
  Future<List<Availability>> getTeacherAvailabilities();
  Future<Availability> createAvailability({required DateTime startTime, ...});
  Future<Availability> updateAvailability({required int availabilityId, ...});
  Future<bool> deleteAvailability(int availabilityId);
  
  // Statistiques et étudiants
  Future<Map<String, dynamic>> getTeacherStats();
  Future<List<User>> getTeacherStudents();
}
```

## 📊 Gestion d'État avec Riverpod

### 🔄 Providers Principaux
```dart
// Service enseignant
final teacherServiceProvider = Provider<TeacherService>((ref) => TeacherService());

// État des cours
final teacherLessonsProvider = StateNotifierProvider<TeacherLessonsNotifier, TeacherLessonsState>((ref) => ...);

// État des disponibilités
final teacherAvailabilitiesProvider = StateNotifierProvider<TeacherAvailabilitiesNotifier, TeacherAvailabilitiesState>((ref) => ...);

// État des statistiques
final teacherStatsProvider = StateNotifierProvider<TeacherStatsNotifier, TeacherStatsState>((ref) => ...);

// État des étudiants
final teacherStudentsProvider = StateNotifierProvider<TeacherStudentsNotifier, TeacherStudentsState>((ref) => ...);
```

## 🎨 Interface Utilisateur

### 📱 Tableau de Bord Principal
- **En-tête utilisateur** : Photo de profil, nom, rôle
- **Statistiques rapides** : Cours du mois, étudiants actifs, heures enseignées, revenus
- **Actions rapides** : Nouveau cours, gérer disponibilités
- **Prochains cours** : Liste des 3 prochains cours
- **Navigation par onglets** : Vue d'ensemble, Cours, Disponibilités, Étudiants, Statistiques

### 📚 Gestion des Cours
- **Filtres** : Tous, Planifiés, En cours, Terminés
- **Liste des cours** : Cartes détaillées avec statut, horaires, lieu, prix
- **Actions** : Modifier, démarrer, terminer, annuler, supprimer
- **Formulaire de création/édition** : Titre, description, horaires, lieu, prix, notes

### ⏰ Gestion des Disponibilités
- **Calendrier hebdomadaire** : Vue par jour de la semaine
- **Création de créneaux** : Heure de début, heure de fin, jour, notes
- **Modification** : Changement d'horaires, activation/désactivation
- **Suppression** : Suppression de créneaux

### 👥 Gestion des Étudiants
- **Liste des étudiants** : Nom, email, photo de profil
- **Détails étudiant** : Informations complètes, historique des cours
- **Statistiques par étudiant** : Nombre de cours, progression

### 📈 Statistiques
- **Graphiques** : Évolution des cours, revenus, étudiants
- **Métriques clés** : Cours ce mois, heures enseignées, taux de satisfaction
- **Filtres temporels** : Semaine, mois, année

## 🔌 Intégration API

### 🌐 Endpoints Utilisés
```
GET    /api/teacher/lessons          # Récupérer les cours
POST   /api/teacher/lessons          # Créer un cours
PUT    /api/teacher/lessons/{id}     # Modifier un cours
DELETE /api/teacher/lessons/{id}     # Supprimer un cours

GET    /api/teacher/availabilities   # Récupérer les disponibilités
POST   /api/teacher/availabilities   # Créer une disponibilité
PUT    /api/teacher/availabilities/{id} # Modifier une disponibilité
DELETE /api/teacher/availabilities/{id} # Supprimer une disponibilité

GET    /api/teacher/stats            # Statistiques
GET    /api/teacher/students         # Liste des étudiants
```

### 🔐 Authentification
- **Token Bearer** : Authentification par token JWT
- **Stockage sécurisé** : `flutter_secure_storage` pour les tokens
- **Gestion des erreurs** : Timeout, 401, 422, erreurs réseau

## 🎯 Fonctionnalités Clés

### ✅ Gestion Complète des Cours
- **Création** : Formulaire complet avec validation
- **Modification** : Édition en temps réel
- **Statuts** : Planifié → En cours → Terminé/Annulé
- **Actions** : Démarrer, terminer, annuler, supprimer

### ✅ Gestion des Disponibilités
- **Créneaux horaires** : Définition précise des disponibilités
- **Calendrier** : Vue hebdomadaire intuitive
- **Flexibilité** : Modification et suppression faciles

### ✅ Statistiques Avancées
- **Métriques temps réel** : Cours, revenus, étudiants
- **Graphiques** : Visualisation des tendances
- **Filtres** : Périodes personnalisables

### ✅ Interface Intuitive
- **Design moderne** : Material Design 3
- **Navigation fluide** : Bottom navigation bar
- **Responsive** : Adaptation mobile optimale
- **Accessibilité** : Support des lecteurs d'écran

## 🚀 Démarrage Rapide

### 📋 Prérequis
- Flutter SDK installé
- API Laravel accessible sur `http://localhost:8081`
- Compte enseignant configuré

### 🔧 Installation
```bash
cd mobile
flutter pub get
flutter run -d chrome --web-port 8083
```

### 🔑 Connexion
1. Ouvrir `http://localhost:8083`
2. Se connecter avec un compte enseignant :
   - Email : `sophie.martin@activibe.com`
   - Mot de passe : `password123`
3. Accéder au tableau de bord enseignant

## 🧪 Tests

### ✅ Tests Automatisés
```bash
# Tests unitaires
flutter test

# Tests d'intégration
flutter test integration_test/
```

### 🔍 Tests Manuels
1. **Connexion** : Vérifier l'authentification
2. **Création de cours** : Tester le formulaire complet
3. **Gestion des statuts** : Démarrer, terminer, annuler
4. **Disponibilités** : Créer, modifier, supprimer
5. **Statistiques** : Vérifier les métriques

## 🔧 Configuration

### ⚙️ Variables d'Environnement
```dart
// mobile/lib/utils/api_config.dart
class ApiConfig {
  static const String baseUrl = 'http://localhost:8081/api';
  static const int connectTimeout = 30000;
  static const int receiveTimeout = 30000;
}
```

### 🎨 Personnalisation
- **Couleurs** : Modification dans `ThemeData`
- **Polices** : Configuration dans `pubspec.yaml`
- **Images** : Ajout dans `assets/`

## 📚 Documentation API

### 📖 Référence Complète
- **Modèles** : Structure des données
- **Services** : Méthodes disponibles
- **Providers** : Gestion d'état
- **Écrans** : Interface utilisateur

### 🔗 Liens Utiles
- [Flutter Documentation](https://flutter.dev/docs)
- [Riverpod Documentation](https://riverpod.dev/)
- [Material Design](https://material.io/design)

---

## 🎉 Résumé

L'application mobile activibe offre une solution complète pour les enseignants avec :

✅ **Gestion complète des cours** : CRUD complet avec statuts  
✅ **Disponibilités flexibles** : Calendrier et créneaux  
✅ **Statistiques avancées** : Métriques et graphiques  
✅ **Interface moderne** : Design intuitif et responsive  
✅ **Architecture robuste** : Riverpod, services, modèles  
✅ **Intégration API** : Communication Laravel sécurisée  

**L'application est prête pour la production et offre une expérience utilisateur optimale pour les enseignants !**

