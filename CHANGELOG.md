# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
et ce projet adhère au [Versioning Sémantique](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Ajouté
- Pipeline CI/CD avec GitHub Actions
- Support complet PHP 8.3
- Documentation technique complète
- Tests Feature avec authentification corrigée

### Modifié
- Migration vers PHPUnit 12
- Amélioration de la couverture de tests
- Configuration Docker optimisée

## [2.1.0] - 2025-09-10

### Ajouté
- **PHP 8.3 Support** - Migration complète vers PHP 8.3.25
- **PHPUnit 12** - Mise à jour vers PHPUnit 12.3.8 avec attributs PHP 8+
- **Pipeline CI/CD** - GitHub Actions pour tests automatiques et déploiement
- **Tests Feature** - Correction de l'authentification dans les tests Feature
- **Neo4jAnalysisService** - Correction des erreurs de syntaxe
- **Documentation** - Documentation technique complète

### Modifié
- **Configuration Docker** - Mise à jour vers PHP 8.3 dans les containers
- **PHPUnit Configuration** - Suppression des éléments dépréciés (XML validation)
- **Tests Attributes** - Conversion de `/** @test */` vers `#[Test]`
- **Memory Limit** - Augmentation à 2G pour PHPUnit 12
- **TestCase** - Amélioration de la méthode `actingAsAdmin()` pour compatibilité

### Corrigé
- **Syntaxe Neo4jAnalysisService** - Correction de l'erreur "Unclosed '{' on line 9"
- **Tests unitaires** - 303 tests passent maintenant avec 0 échec
- **Authentification tests** - Correction du middleware admin pour les tests Feature
- **Mémoire PHPUnit** - Résolution des problèmes de mémoire insuffisante

### Supprimé
- **Éléments PHPUnit dépréciés** - Suppression des métadonnées doc-comment
- **Scripts temporaires** - Nettoyage des scripts de conversion PHPUnit

## [2.0.0] - 2025-09-09

### Ajouté
- **Tests unitaires complets** - 303 tests couvrant tous les modèles et services
- **Factories** - Factories complètes pour tous les modèles
- **QrCodeService** - Service de génération et gestion des QR codes
- **ClubSettings** - Système de configuration des clubs
- **StudentPreferences** - Gestion des préférences étudiants
- **Neo4j Integration** - Analyses graphiques avancées

### Modifié
- **Structure de base de données** - Optimisation des relations et contraintes
- **Modèles Eloquent** - Amélioration des relations et casting
- **Configuration tests** - Migration vers MySQL dans Docker

### Corrigé
- **Migrations** - Correction de l'ordre des migrations
- **Contraintes uniques** - Résolution des violations de contraintes
- **Casting décimal** - Correction du comportement `decimal:2` en Laravel
- **Relations pivot** - Correction des données pivot boolean/integer

## [1.5.0] - 2025-09-08

### Ajouté
- **Docker Compose** - Configuration complète pour développement
- **MySQL 8.0** - Migration vers MySQL avec Docker
- **Redis** - Cache et sessions avec Redis
- **Middleware personnalisés** - AdminMiddleware, ClubMiddleware

### Modifié
- **Configuration environnement** - Optimisation pour Docker
- **Base de données** - Migration SQLite vers MySQL

### Corrigé
- **Permissions base de données** - Configuration utilisateur MySQL
- **Tests environnement** - Configuration tests avec MySQL

## [1.0.0] - 2025-09-07

### Ajouté
- **Architecture Laravel 11** - Base du projet avec Laravel 11
- **Modèles principaux** - User, Club, Teacher, Student, Lesson
- **Authentification** - Laravel Sanctum avec rôles multiples
- **API REST** - Endpoints pour toutes les entités principales
- **Dashboard** - Tableaux de bord pour chaque rôle utilisateur

### Fonctionnalités Principales
- Gestion des utilisateurs avec rôles (admin, teacher, student, club)
- Système de clubs avec enseignants et étudiants
- Planification et gestion des cours
- Authentification sécurisée avec tokens
- QR Codes pour adhésion aux clubs
- Dashboard financier pour les clubs

---

## Types de Changements

- **Ajouté** pour les nouvelles fonctionnalités
- **Modifié** pour les changements dans les fonctionnalités existantes
- **Déprécié** pour les fonctionnalités qui seront supprimées prochainement
- **Supprimé** pour les fonctionnalités supprimées
- **Corrigé** pour les corrections de bugs
- **Sécurité** pour les vulnérabilités corrigées
