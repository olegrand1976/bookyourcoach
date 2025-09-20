# Intégration Google Calendar

## Vue d'ensemble

L'intégration Google Calendar permet aux enseignants de synchroniser leurs cours avec Google Calendar, offrant une gestion unifiée de leur planning.

## Fonctionnalités

### 1. Connexion Google Calendar
- Authentification OAuth2 avec Google
- Gestion des tokens d'accès et de rafraîchissement
- Stockage sécurisé des informations de connexion

### 2. Gestion des calendriers
- Affichage de tous les calendriers Google de l'utilisateur
- Sélection du calendrier principal pour la synchronisation
- Support des calendriers personnels et de club

### 3. Synchronisation bidirectionnelle
- **Vers Google Calendar** : Les cours créés dans l'application sont automatiquement ajoutés à Google Calendar
- **Depuis Google Calendar** : Les événements modifiés dans Google Calendar sont synchronisés dans l'application
- **Synchronisation automatique** : Option pour synchroniser automatiquement à intervalles réguliers

### 4. Types de cours supportés
- Cours particuliers
- Cours de groupe
- Entraînements
- Compétitions

## Configuration

### 1. Variables d'environnement

```env
# Google Calendar API
GOOGLE_CALENDAR_CREDENTIALS_PATH=/path/to/credentials.json
GOOGLE_CALENDAR_REDIRECT_URI=https://activibe.be/api/google-calendar/callback
```

### 2. Configuration Google Cloud Console

1. Créer un projet dans Google Cloud Console
2. Activer l'API Google Calendar
3. Créer des identifiants OAuth2
4. Configurer les URI de redirection autorisées
5. Télécharger le fichier de credentials JSON

### 3. Installation des dépendances

```bash
composer require google/apiclient
```

## Architecture

### 1. Service GoogleCalendarService
- Gestion de l'authentification OAuth2
- Opérations CRUD sur les événements
- Synchronisation bidirectionnelle
- Gestion des tokens d'accès

### 2. Contrôleur GoogleCalendarController
- Endpoints API pour l'intégration
- Gestion des callbacks OAuth2
- Validation et sécurisation des requêtes

### 3. Composants Vue.js
- `GoogleCalendarIntegration.vue` : Interface de configuration
- `TeacherCalendar.vue` : Calendrier principal avec intégration

## API Endpoints

### Authentification
- `GET /api/google-calendar/auth-url` : Obtenir l'URL d'autorisation
- `POST /api/google-calendar/callback` : Traiter le callback OAuth2

### Gestion des calendriers
- `GET /api/google-calendar/calendars` : Lister les calendriers
- `GET /api/google-calendar/events` : Obtenir les événements

### Synchronisation
- `POST /api/google-calendar/sync-events` : Synchroniser les événements
- `POST /api/google-calendar/events` : Créer un événement
- `PUT /api/google-calendar/events/{id}` : Mettre à jour un événement
- `DELETE /api/google-calendar/events/{id}` : Supprimer un événement

## Base de données

### Table `google_calendar_tokens`
```sql
CREATE TABLE google_calendar_tokens (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    access_token TEXT NOT NULL,
    user_info TEXT NOT NULL,
    calendars TEXT NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id)
);
```

## Utilisation

### 1. Connexion initiale
1. L'enseignant clique sur "Connecter Google Calendar"
2. Redirection vers Google pour autorisation
3. Retour automatique avec les calendriers disponibles
4. Sélection du calendrier principal

### 2. Synchronisation
- **Automatique** : Les nouveaux cours sont automatiquement ajoutés à Google Calendar
- **Manuelle** : Bouton "Synchroniser maintenant" pour forcer la synchronisation
- **Bidirectionnelle** : Les modifications dans Google Calendar sont répercutées dans l'application

### 3. Gestion des conflits
- Les événements modifiés dans Google Calendar ont priorité
- Les cours créés dans l'application sont ajoutés comme nouveaux événements
- Historique des synchronisations pour traçabilité

## Sécurité

### 1. Authentification
- OAuth2 avec Google
- Tokens d'accès avec expiration
- Tokens de rafraîchissement automatiques

### 2. Autorisation
- Vérification des permissions utilisateur
- Validation des accès aux calendriers
- Isolation des données par utilisateur

### 3. Données sensibles
- Chiffrement des tokens stockés
- Validation des requêtes API
- Logs de sécurité pour audit

## Limitations

### 1. Quotas Google Calendar API
- 1,000,000 requêtes par jour
- 100 requêtes par 100 secondes par utilisateur
- 1,000 requêtes par 100 secondes globales

### 2. Types d'événements
- Support des événements simples (titre, date, durée)
- Pas de support des événements récurrents complexes
- Limitation aux événements de cours

### 3. Synchronisation
- Délai de synchronisation : 5-15 minutes
- Pas de synchronisation en temps réel
- Gestion des conflits basique

## Dépannage

### 1. Erreurs d'authentification
- Vérifier les credentials Google
- Vérifier les URI de redirection
- Vérifier les permissions OAuth2

### 2. Erreurs de synchronisation
- Vérifier les quotas API
- Vérifier la validité des tokens
- Consulter les logs d'erreur

### 3. Problèmes de performance
- Optimiser les requêtes API
- Implémenter la mise en cache
- Gérer les limites de taux

## Roadmap

### Phase 1 (Actuelle)
- ✅ Connexion Google Calendar
- ✅ Synchronisation basique
- ✅ Interface de configuration

### Phase 2
- 🔄 Synchronisation bidirectionnelle avancée
- 🔄 Gestion des conflits
- 🔄 Notifications de synchronisation

### Phase 3
- ⏳ Support des événements récurrents
- ⏳ Intégration avec d'autres calendriers
- ⏳ Synchronisation en temps réel

## Support

Pour toute question ou problème lié à l'intégration Google Calendar, consultez :
- Documentation Google Calendar API
- Logs d'application
- Support technique Activibe
