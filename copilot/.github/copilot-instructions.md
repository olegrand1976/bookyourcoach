# Copilot Instructions pour BookYourCoach

<!-- Use this file to provide workspace-specific custom instructions to Copilot. For more details, visit https://code.visualstudio.com/docs/copilot/copilot-customization#_use-a-githubcopilotinstructionsmd-file -->

## Contexte du Projet

Ce projet est une API REST Laravel pour une plateforme de réservation de cours avec coaches (équestres ou autres). L'application gère trois types d'utilisateurs : Administrateurs, Enseignants et Élèves.

## Architecture et Conventions

### Modèles Principaux

-   **User** : Utilisateurs avec rôles (admin/teacher/student)
-   **Profile** : Profils utilisateurs avec informations personnelles
-   **Teacher** : Enseignants avec leurs spécificités
-   **Student** : Élèves avec leurs informations
-   **CourseType** : Types de cours (dressage, obstacle, etc.)
-   **Lesson** : Leçons/cours réservés
-   **Location** : Lieux de cours
-   **Payment** : Paiements via Stripe
-   **Invoice** : Facturation
-   **Subscription** : Abonnements élèves
-   **Availability** : Disponibilités enseignants
-   **TimeBlock** : Blocages de créneaux
-   **Payout** : Reversements aux enseignants
-   **AuditLog** : Journalisation des actions

### Règles de Développement

1. Utiliser les **Eloquent Models** avec des relations bien définies
2. Implémenter un système **RBAC** (Role-Based Access Control)
3. Toujours valider les données avec des **Form Requests**
4. Utiliser des **Resources** pour formatter les réponses API
5. Implémenter des **Policies** pour l'autorisation
6. Gérer les **fuseaux horaires** (Europe/Brussels par défaut)
7. Intégrer **Stripe** pour les paiements et Connect pour les reversements

### API REST

-   Utiliser les conventions RESTful
-   Réponses JSON standardisées
-   Gestion d'erreurs cohérente
-   Documentation API avec annotations

### Base de Données

-   Migrations avec Foreign Keys
-   Index appropriés pour les performances
-   Soft deletes pour les données sensibles
-   Champs timestamp et audit

### Fonctionnalités Clés

-   Authentification multi-rôles
-   Gestion des disponibilités avec calcul de trajets
-   Système de réservation avec verrouillage optimiste
-   Intégration paiements Stripe + Connect
-   Facturation automatisée
-   Notifications (email, push, SMS)
-   Génération de factures PDF

### Tests

-   Tests unitaires pour la logique métier
-   Tests de feature pour les API endpoints
-   Tests d'intégration pour Stripe
-   Factories pour les données de test
