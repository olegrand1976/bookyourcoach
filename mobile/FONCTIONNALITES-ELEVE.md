# 📚 Fonctionnalités Élève - BookYourCoach Mobile

## 🎯 Vue d'ensemble
L'application mobile BookYourCoach offre un ensemble complet de fonctionnalités dédiées aux élèves pour découvrir, réserver et gérer leurs cours avec des enseignants qualifiés.

## 🏗️ Architecture Technique

### 📁 Structure des Fichiers
```
mobile/lib/
├── models/
│   ├── user.dart
│   ├── lesson.dart
│   └── booking.dart
├── services/
│   ├── auth_service.dart
│   └── student_service.dart
├── providers/
│   ├── auth_provider.dart
│   └── student_provider.dart
├── screens/
│   ├── login_screen.dart
│   ├── home_screen.dart
│   ├── student_dashboard.dart
│   └── student_bookings_screen.dart
└── widgets/
    ├── custom_button.dart
    └── custom_text_field.dart
```

### 🔧 Technologies Utilisées
- **Flutter/Dart** : Framework de développement mobile
- **Riverpod** : Gestion d'état réactive
- **Dio** : Client HTTP pour les appels API
- **FlutterSecureStorage** : Stockage sécurisé des tokens
- **Mockito** : Framework de tests avec mocks

## 📊 Modèles de Données

### 🎫 Booking (Réservation)
```dart
class Booking {
  final int id;
  final int studentId;
  final int lessonId;
  final String status; // 'pending', 'confirmed', 'cancelled', 'completed'
  final DateTime? bookedAt;
  final String? notes;
  final DateTime createdAt;
  final DateTime updatedAt;
  final Lesson? lesson;
  final User? student;
}
```

**Propriétés principales :**
- `status` : État de la réservation (en attente, confirmée, annulée, terminée)
- `bookedAt` : Date et heure de la réservation
- `notes` : Notes optionnelles de l'élève
- `lesson` : Cours associé à la réservation
- `student` : Élève qui a fait la réservation

## 🔌 Services API

### 📡 StudentService
Service principal pour toutes les opérations liées aux élèves.

#### 🔍 Méthodes Principales

**Récupération des cours disponibles :**
```dart
Future<List<Lesson>> getAvailableLessons({
  String? subject,
  DateTime? date,
})
```

**Gestion des réservations :**
```dart
Future<List<Booking>> getStudentBookings({String? status})
Future<Booking> bookLesson({required int lessonId, String? notes})
Future<bool> cancelBooking(int bookingId)
```

**Recherche et filtres :**
```dart
Future<List<Lesson>> searchLessons({
  String? query,
  String? subject,
  DateTime? startDate,
  DateTime? endDate,
  double? maxPrice,
})
```

**Gestion des enseignants :**
```dart
Future<List<User>> getAvailableTeachers({String? subject})
Future<List<User>> getFavoriteTeachers()
Future<bool> toggleFavoriteTeacher(int teacherId)
```

**Statistiques et historique :**
```dart
Future<Map<String, dynamic>> getStudentStats()
Future<List<Booking>> getLessonHistory()
Future<bool> rateLesson({required int bookingId, required int rating, String? review})
```

## 🎛️ Gestion d'État (Riverpod)

### 📊 Providers Principaux

**AvailableLessonsProvider :**
```dart
final availableLessonsProvider = StateNotifierProvider<AvailableLessonsNotifier, AvailableLessonsState>
```

**StudentBookingsProvider :**
```dart
final studentBookingsProvider = StateNotifierProvider<StudentBookingsNotifier, StudentBookingsState>
```

**AvailableTeachersProvider :**
```dart
final availableTeachersProvider = StateNotifierProvider<AvailableTeachersNotifier, AvailableTeachersState>
```

**StudentStatsProvider :**
```dart
final studentStatsProvider = StateNotifierProvider<StudentStatsNotifier, StudentStatsState>
```

**FavoriteTeachersProvider :**
```dart
final favoriteTeachersProvider = StateNotifierProvider<FavoriteTeachersNotifier, FavoriteTeachersState>
```

**LessonHistoryProvider :**
```dart
final lessonHistoryProvider = StateNotifierProvider<LessonHistoryNotifier, LessonHistoryState>
```

## 🖥️ Interface Utilisateur

### 📱 StudentDashboard
Tableau de bord principal pour les élèves avec :
- **En-tête personnalisé** : Informations de l'utilisateur et rôle
- **Statistiques rapides** : Cours suivis, réservations actives, heures d'apprentissage, nombre d'enseignants
- **Actions rapides** : Rechercher des cours, accéder aux réservations
- **Navigation par onglets** : Vue d'ensemble, Cours disponibles, Mes réservations, Enseignants, Historique
- **Prochaines réservations** : Affichage des réservations confirmées à venir
- **Cours disponibles** : Liste des cours récents disponibles

### 📚 StudentBookingsScreen
Écran de gestion des réservations avec :
- **Filtres** : Toutes, En attente, Confirmées, Terminées
- **Cartes de réservation** : Détails complets avec statut coloré
- **Actions contextuelles** : Annuler, contacter l'enseignant, noter
- **États vides** : Messages informatifs quand aucune réservation
- **Gestion d'erreurs** : Affichage des erreurs avec possibilité de réessayer

## 🔗 Endpoints API

### 🔐 Authentification
```
POST /api/auth/login
POST /api/auth/logout
GET  /api/user
```

### 📖 Cours et Réservations
```
GET  /api/student/available-lessons
GET  /api/student/bookings
POST /api/student/bookings
PUT  /api/student/bookings/{id}/cancel
GET  /api/student/search-lessons
```

### 👨‍🏫 Enseignants
```
GET  /api/student/available-teachers
GET  /api/student/favorite-teachers
POST /api/student/favorite-teachers/{id}/toggle
GET  /api/student/teachers/{id}/lessons
```

### 📊 Statistiques et Historique
```
GET  /api/student/stats
GET  /api/student/lesson-history
POST /api/student/bookings/{id}/rate
```

## 🧪 Tests

### 🧩 Tests Unitaires
- **student_service_test.dart** : Tests du service API
- **student_provider_test.dart** : Tests des providers Riverpod

### 🔗 Tests d'Intégration
- **student_integration_test.dart** : Tests end-to-end des fonctionnalités

### 🚀 Script de Test Automatisé
- **test_student_features.sh** : Script complet de validation

## 🎯 Fonctionnalités Clés

### 🔍 Découverte de Cours
- **Recherche avancée** : Par matière, date, prix, enseignant
- **Filtres multiples** : Disponibilité, niveau, localisation
- **Cours recommandés** : Basés sur l'historique et les préférences
- **Détails complets** : Description, horaires, prix, enseignant

### 📅 Gestion des Réservations
- **Réservation simple** : En quelques clics
- **Suivi en temps réel** : Statut des réservations
- **Annulation flexible** : Selon les conditions du cours
- **Notifications** : Rappels et confirmations

### 👨‍🏫 Gestion des Enseignants
- **Profils détaillés** : Expérience, spécialités, avis
- **Système de favoris** : Enseignants préférés
- **Contact direct** : Messages et questions
- **Évaluations** : Notes et commentaires

### 📊 Suivi de Progression
- **Statistiques personnelles** : Cours suivis, heures d'apprentissage
- **Historique complet** : Tous les cours passés
- **Objectifs d'apprentissage** : Suivi des progrès
- **Certificats** : Attestations de participation

### ⭐ Système de Notation
- **Évaluation des cours** : Notes de 1 à 5 étoiles
- **Commentaires détaillés** : Retours constructifs
- **Avis publics** : Partage d'expériences
- **Recommandations** : Basées sur les évaluations

## 🚀 Guide de Démarrage

### 📋 Prérequis
```bash
# Vérifier Flutter
flutter doctor

# Vérifier l'API Laravel
curl http://localhost:8081/api
```

### 🔧 Installation
```bash
# Récupérer les dépendances
flutter pub get

# Générer les mocks pour les tests
flutter packages pub run build_runner build
```

### 🧪 Tests
```bash
# Tests unitaires
flutter test test/student_service_test.dart
flutter test test/student_provider_test.dart

# Tests d'intégration
flutter test integration_test/student_integration_test.dart

# Script complet
chmod +x test_student_features.sh
./test_student_features.sh
```

### 🏃‍♂️ Lancement
```bash
# Application web
flutter run -d chrome --web-port 8084

# Application mobile
flutter run -d android
flutter run -d ios
```

## 🔐 Comptes de Test

### 👨‍🎓 Élève de Test
```
Email: alice.durand@email.com
Mot de passe: password123
```

## 🎨 Design et UX

### 🎯 Principes de Design
- **Interface intuitive** : Navigation claire et logique
- **Feedback visuel** : États de chargement et confirmations
- **Accessibilité** : Support des lecteurs d'écran
- **Responsive** : Adaptation à tous les écrans

### 🎨 Palette de Couleurs
- **Vert principal** : `#059669` (Actions et succès)
- **Bleu secondaire** : `#2563EB` (Informations)
- **Rouge accent** : `#DC2626` (Erreurs et alertes)
- **Violet** : `#7C3AED` (Éléments spéciaux)

### 📱 Composants UI
- **Cartes interactives** : Cours et réservations
- **Boutons d'action** : Réserver, annuler, noter
- **Filtres visuels** : Chips colorés pour les statuts
- **Indicateurs de progression** : Barres et pourcentages

## 🔧 Configuration

### ⚙️ Variables d'Environnement
```dart
// lib/utils/api_config.dart
class ApiConfig {
  static const String apiUrl = 'http://localhost:8081/api';
  static const int connectTimeout = 10000;
  static const int receiveTimeout = 10000;
}
```

### 🔒 Sécurité
- **Tokens JWT** : Authentification sécurisée
- **Stockage sécurisé** : Tokens chiffrés localement
- **Validation côté client** : Vérification des données
- **Gestion d'erreurs** : Messages informatifs

## 📈 Métriques et Analytics

### 📊 Données Suivies
- **Temps de session** : Durée d'utilisation
- **Cours réservés** : Nombre et types
- **Taux de conversion** : Réservations vs consultations
- **Satisfaction** : Notes moyennes des cours

### 📈 KPIs Principaux
- **Engagement** : Sessions par utilisateur
- **Rétention** : Utilisateurs actifs
- **Conversion** : Taux de réservation
- **Satisfaction** : Score moyen des évaluations

## 🔮 Roadmap

### 🚀 Fonctionnalités Futures
- **Chat en temps réel** : Communication directe avec les enseignants
- **Paiements intégrés** : Stripe/PayPal
- **Notifications push** : Rappels et alertes
- **Mode hors ligne** : Synchronisation locale
- **Gamification** : Badges et récompenses
- **Intelligence artificielle** : Recommandations personnalisées

### 🔧 Améliorations Techniques
- **Performance** : Optimisation des requêtes
- **Cache intelligent** : Mise en cache des données
- **Tests automatisés** : Couverture complète
- **Monitoring** : Surveillance des erreurs
- **CI/CD** : Déploiement automatisé

## 📚 Documentation Supplémentaire

### 🔗 Liens Utiles
- [Documentation Flutter](https://docs.flutter.dev/)
- [Guide Riverpod](https://riverpod.dev/docs/introduction/getting_started)
- [API Laravel](http://localhost:8081/api/documentation)
- [Design System](https://material.io/design)

### 📖 Guides Spécifiques
- [Guide de Test](TESTS-ELEVE.md)
- [Guide de Déploiement](DEPLOIEMENT-ELEVE.md)
- [Guide de Maintenance](MAINTENANCE-ELEVE.md)

---

**L'application est prête pour la production et offre une expérience utilisateur optimale pour les élèves !**




