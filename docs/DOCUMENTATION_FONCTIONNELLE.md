# 📚 Documentation Fonctionnelle - BookYourCoach

**Version :** 1.5.0  
**Date :** Janvier 2025  
**Plateforme :** activibe (BookYourCoach)

---

## 📋 Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Rôles et Permissions](#rôles-et-permissions)
3. [Fonctionnalités par Rôle](#fonctionnalités-par-rôle)
4. [Gestion des Cours](#gestion-des-cours)
5. [Système d'Abonnements](#système-dabonnements)
6. [Gestion Financière](#gestion-financière)
7. [Notifications et Communication](#notifications-et-communication)
8. [Intégrations](#intégrations)

---

## 🎯 Vue d'ensemble

BookYourCoach (activibe) est une plateforme complète de gestion de clubs sportifs permettant de gérer les cours, enseignants, étudiants, abonnements et paiements. La plateforme supporte plusieurs types d'activités sportives (équitation, natation, fitness, etc.) avec une architecture multi-tenant.

### Objectifs Principaux

- **Gestion complète des clubs** : Administration centralisée de tous les aspects d'un club sportif
- **Planification intelligente** : Système de réservation et de planification avec suggestions IA
- **Gestion financière** : Suivi des paiements, commissions et rapports de paie
- **Expérience utilisateur optimale** : Interfaces web et mobile pour tous les acteurs

---

## 👥 Rôles et Permissions

### 1. Administrateur (`admin`)

**Permissions :**
- Accès complet à toutes les fonctionnalités
- Gestion de tous les clubs, utilisateurs et paramètres système
- Consultation des statistiques globales
- Génération de rapports de paie globaux
- Audit et logs système

**Fonctionnalités principales :**
- Dashboard administratif avec statistiques globales
- Gestion des utilisateurs (création, modification, suppression)
- Gestion des clubs (création, validation, activation)
- Configuration des paramètres système
- Consultation des logs d'audit
- Rapports de paie globaux

### 2. Club (`club`)

**Permissions :**
- Gestion complète de son club
- Gestion des enseignants et étudiants affiliés
- Planification des cours et créneaux
- Gestion des abonnements
- Consultation des revenus et commissions

**Fonctionnalités principales :**
- Dashboard club avec statistiques détaillées
- Gestion du profil du club (informations, disciplines, équipements)
- Gestion des enseignants (ajout, modification, invitations)
- Gestion des étudiants (inscription, historique, abonnements)
- Planification des cours et créneaux récurrents
- Gestion des modèles d'abonnements
- Suivi des paiements et commissions
- Rapports de paie pour les enseignants
- Analyse prédictive avec IA

### 3. Enseignant (`teacher`)

**Permissions :**
- Gestion de son planning personnel
- Consultation de ses étudiants
- Gestion de ses disponibilités
- Suivi de ses revenus

**Fonctionnalités principales :**
- Dashboard enseignant avec planning
- Gestion du profil (spécialités, certifications, bio)
- Consultation des cours assignés
- Gestion des remplacements de cours
- Consultation des étudiants
- Suivi des revenus et commissions
- Synchronisation Google Calendar

### 4. Étudiant (`student`)

**Permissions :**
- Réservation de cours
- Gestion de son profil
- Consultation de ses abonnements
- Suivi de ses réservations

**Fonctionnalités principales :**
- Dashboard étudiant avec cours disponibles
- Recherche et réservation de cours
- Gestion du profil et préférences
- Consultation des abonnements actifs
- Historique des cours
- Gestion des affiliations aux clubs

---

## 🎓 Fonctionnalités par Rôle

### Pour les Clubs

#### 1. Gestion du Profil Club

**Configuration du club :**
- Informations de base (nom, adresse, contact)
- Description et logo
- Disciplines proposées (équitation, natation, etc.)
- Équipements et installations
- Paramètres légaux (assurances, représentant légal)
- Paramètres par défaut des abonnements

**Gestion des disciplines :**
- Sélection des activités proposées
- Configuration des disciplines par activité
- Paramètres de prix par discipline

#### 2. Gestion des Enseignants

**Ajout d'enseignants :**
- Création manuelle avec invitation par email
- Import depuis une liste
- Attribution automatique via QR code

**Gestion des profils enseignants :**
- Informations personnelles
- Spécialités et certifications
- Taux horaire et contrat
- Disponibilités et planning

**Invitations :**
- Envoi d'email de bienvenue avec lien de réinitialisation de mot de passe
- Renvoi d'invitation si nécessaire
- Gestion des statuts (actif/inactif)

#### 3. Gestion des Étudiants

**Inscription d'étudiants :**
- Création manuelle avec ou sans email
- Import depuis une liste
- Inscription via QR code

**Gestion des profils étudiants :**
- Informations personnelles
- Date de naissance et informations médicales
- Disciplines préférées
- Historique des cours et abonnements

**Affiliation aux clubs :**
- Les étudiants peuvent s'affilier à plusieurs clubs
- Gestion des affiliations depuis le profil étudiant

#### 4. Planification des Cours

**Créneaux ouverts (Open Slots) :**
- Création de créneaux récurrents (hebdomadaires)
- Configuration des types de cours par créneau
- Gestion des disponibilités

**Création de cours :**
- Création manuelle avec sélection d'enseignant et étudiants
- Attribution d'un créneau récurrent
- Gestion des récurrences sur 6 mois
- Suggestions IA en cas de conflit

**Gestion des récurrences :**
- Blocage automatique des créneaux récurrents
- Validation de disponibilité sur 26 semaines
- Suggestions alternatives via IA Gemini

#### 5. Système d'Abonnements

**Modèles d'abonnements :**
- Création de modèles (ex: 10 cours, 1 gratuit, validité 12 semaines)
- Configuration des prix et types de cours inclus
- Activation/désactivation des modèles

**Abonnements étudiants :**
- Attribution d'abonnements depuis les modèles
- Suivi de l'utilisation (cours utilisés/restants)
- Gestion des dates d'expiration
- Renouvellement automatique ou manuel

**Créneaux récurrents d'abonnement :**
- Réservation automatique sur 6 mois lors de la création d'un cours
- Gestion des conflits et suggestions alternatives

#### 6. Gestion Financière

**Suivi des paiements :**
- Enregistrement des paiements par cours
- Gestion des statuts (payé/en attente)
- Historique des transactions

**Commissions enseignants :**
- Calcul automatique des commissions
- Gestion des statuts DCL/NDCL (Déclaré/Non Déclaré)
- Suivi par mois de paiement

**Rapports de paie :**
- Génération mensuelle des rapports
- Export CSV pour comptabilité
- Détails par enseignant
- Gestion des paiements manuels

#### 7. Dashboard et Statistiques

**Métriques principales :**
- Nombre de cours planifiés/complétés
- Revenus totaux et par période
- Nombre d'enseignants et étudiants actifs
- Taux d'occupation des créneaux

**Analyse prédictive IA :**
- Alertes critiques (étudiants à risque, enseignants surchargés)
- Suggestions d'optimisation
- Prévisions de revenus

### Pour les Enseignants

#### 1. Dashboard Enseignant

**Vue d'ensemble :**
- Planning de la semaine
- Cours à venir
- Statistiques personnelles (cours donnés, revenus)

#### 2. Gestion du Planning

**Consultation des cours :**
- Liste des cours assignés
- Détails de chaque cours (étudiants, horaire, lieu)
- Statut des cours (planifié, complété, annulé)

**Gestion des remplacements :**
- Demande de remplacement pour un cours
- Consultation des demandes de remplacement
- Acceptation/refus des remplacements

#### 3. Gestion du Profil

**Informations personnelles :**
- Nom, prénom, email, téléphone
- Date de naissance
- Bio et photo

**Spécialités et certifications :**
- Ajout de spécialités
- Gestion des certifications
- Années d'expérience

#### 4. Suivi des Revenus

**Consultation des gains :**
- Revenus par période
- Détail des commissions
- Historique des paiements

### Pour les Étudiants

#### 1. Dashboard Étudiant

**Vue d'ensemble :**
- Cours disponibles à réserver
- Réservations à venir
- Historique des cours complétés
- Abonnements actifs

#### 2. Recherche et Réservation

**Cours disponibles :**
- Filtrage par discipline, date, enseignant
- Affichage des créneaux libres
- Réservation en un clic

**Gestion des réservations :**
- Consultation des réservations
- Annulation de réservations
- Historique complet

#### 3. Gestion du Profil

**Informations personnelles :**
- Modification des informations de base
- Gestion des affiliations aux clubs
- Préférences de disciplines

**Affiliation aux clubs :**
- Sélection lors de l'inscription
- Ajout/retrait de clubs depuis le profil
- Consultation des clubs affiliés

#### 4. Abonnements

**Consultation des abonnements :**
- Abonnements actifs
- Cours restants/utilisés
- Dates d'expiration

**Souscription :**
- Consultation des modèles disponibles
- Souscription à un abonnement
- Renouvellement d'abonnement

---

## 📅 Gestion des Cours

### Création de Cours

**Processus de création :**
1. Sélection du club (pour les admins)
2. Choix de l'enseignant
3. Sélection des étudiants (un ou plusieurs)
4. Choix du type de cours et de la discipline
5. Sélection de la date et heure
6. Choix du lieu
7. Configuration du prix
8. Option de récurrence sur 6 mois

**Gestion des récurrences :**
- Validation automatique de disponibilité sur 26 semaines
- Blocage des créneaux récurrents
- Suggestions IA en cas de conflit
- Création automatique des cours récurrents

### Types de Cours

**Cours individuels :**
- Un étudiant par cours
- Prix fixe ou variable
- Attribution possible depuis un abonnement

**Cours collectifs :**
- Plusieurs étudiants par cours
- Prix par étudiant
- Gestion des capacités

### Statuts des Cours

- **Planifié** : Cours créé, en attente
- **Confirmé** : Confirmé par l'enseignant
- **En cours** : Cours en cours d'exécution
- **Complété** : Cours terminé
- **Annulé** : Cours annulé

---

## 💳 Système d'Abonnements

### Modèles d'Abonnements

**Structure d'un modèle :**
- Nom et description
- Nombre total de cours
- Nombre de cours gratuits
- Prix
- Validité (en semaines)
- Types de cours inclus
- Statut (actif/inactif)

**Exemple :**
```
Modèle "Abonnement 10 cours"
- 10 cours au total
- 1 cours gratuit inclus
- Prix : 180€
- Validité : 12 semaines
- Types de cours : Tous
```

### Instances d'Abonnements

**Création depuis un modèle :**
- Attribution à un ou plusieurs étudiants
- Date de début personnalisable
- Calcul automatique de la date d'expiration

**Suivi de l'utilisation :**
- Cours utilisés / Cours restants
- Date d'expiration
- Statut (actif/expiré/fermé)

**Renouvellement :**
- Renouvellement manuel depuis le modèle
- Création d'une nouvelle instance
- Conservation de l'historique

### Créneaux Récurrents d'Abonnement

**Fonctionnement :**
- Lors de la création d'un cours avec un étudiant ayant un abonnement actif
- Réservation automatique du créneau sur 6 mois (26 semaines)
- Validation de disponibilité avant réservation
- Suggestions alternatives en cas de conflit

---

## 💰 Gestion Financière

### Paiements

**Enregistrement des paiements :**
- Paiement par cours individuel
- Paiement depuis un abonnement
- Statut : payé/en attente/annulé
- Date de paiement

**Gestion des statuts :**
- DCL (Déclaré) : Paiement déclaré pour les commissions
- NDCL (Non Déclaré) : Paiement non déclaré (legacy)

### Commissions Enseignants

**Calcul automatique :**
- Taux horaire de l'enseignant
- Durée du cours
- Statut DCL/NDCL
- Date de paiement (détermine le mois de commission)

**Gestion des commissions :**
- Calcul par mois de paiement
- Suivi des paiements manuels
- Modification possible des commissions

### Rapports de Paie

**Génération mensuelle :**
- Sélection de l'année et du mois
- Calcul automatique des commissions
- Détails par enseignant
- Export CSV pour comptabilité

**Contenu des rapports :**
- Liste des cours avec commissions
- Total des commissions par enseignant
- Paiements manuels
- Statut DCL/NDCL

---

## 🔔 Notifications et Communication

### Types de Notifications

**Notifications système :**
- Nouveau cours assigné
- Demande de remplacement
- Nouvelle réservation
- Abonnement expirant

**Notifications email :**
- Invitation enseignant/étudiant
- Confirmation d'inscription
- Rappels de cours
- Notifications de paiement

### Gestion des Notifications

**Pour tous les utilisateurs :**
- Consultation des notifications non lues
- Marquage comme lues
- Marquage de toutes comme lues
- Compteur de notifications non lues

---

## 🔌 Intégrations

### Google Calendar

**Synchronisation :**
- Connexion OAuth2 avec Google
- Synchronisation bidirectionnelle
- Création automatique d'événements
- Mise à jour automatique des modifications

**Fonctionnalités :**
- Export des cours vers Google Calendar
- Import des événements depuis Google Calendar
- Gestion des conflits

### Stripe (Paiements)

**Intégration :**
- Traitement des paiements en ligne
    Abonnement : 180 € (10 scéances + 1 gratuite)
    Scéance : 18 €
    Stage : uniquement aux périodes de vacances scolaires et le prix dépend du nombre de jours
- Webhooks pour les notifications
- Gestion des abonnements récurrents
- Remboursements



### Neo4j (Analyse de Données)

**Utilisation :**
- Analyse des relations complexes
- Métriques globales
- Analyse des relations utilisateurs-clubs
- Analyse des enseignants par spécialité

---

## 📱 Applications Mobiles

### Application Flutter

**Plateformes supportées :**
- iOS
- Android

**Fonctionnalités :**
- Authentification
- Consultation du planning
- Réservation de cours
- Gestion du profil
- Notifications push

---

## 🔐 Sécurité

### Authentification

**Méthodes d'authentification :**
- Laravel Sanctum (tokens)
- Sessions sécurisées
- Réinitialisation de mot de passe

**Sécurité des données :**
- Chiffrement des mots de passe
- Validation stricte des données
- Protection CSRF
- Audit logs

### Permissions

**Système de rôles :**
- Vérification des rôles via middleware
- Permissions granulaires par fonctionnalité
- Isolation des données par club

---

## 📊 Statistiques et Rapports

### Dashboard Club

**Métriques principales :**
- Cours planifiés/complétés
- Revenus totaux
- Nombre d'enseignants/étudiants actifs
- Taux d'occupation

### Analyse Prédictive IA

**Fonctionnalités :**
- Alertes critiques
- Suggestions d'optimisation
- Prévisions de revenus
- Analyse des tendances

---

## 🎨 Interface Utilisateur

### Frontend Web (Nuxt.js 3)

**Technologies :**
- Nuxt.js 3
- Vue.js 3
- Tailwind CSS
- Composants réutilisables

**Fonctionnalités :**
- Interface responsive
- Dark mode (à venir)
- Navigation intuitive
- Tableaux de bord personnalisés

### Design System

**Composants :**
- Boutons standardisés
- Formulaires cohérents
- Modales et dialogues
- Tableaux de données


---

## 🚀 Évolutions Futures

### Fonctionnalités Planifiées

- **Application mobile complète** : Développement des fonctionnalités manquantes
- **Système de messagerie** : Communication directe entre utilisateurs
- **Évaluations et avis** : Système de notation des cours
- **Gestion des équipements** : Réservation d'équipements sportifs
- **Compétitions** : Gestion des événements et compétitions
- **Rapports avancés** : Analytics détaillés avec graphiques

---

**Dernière mise à jour :** Janvier 2025  
**Version de la documentation :** 1.5.0
