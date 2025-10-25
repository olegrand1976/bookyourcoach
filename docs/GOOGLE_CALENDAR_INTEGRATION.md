# Configuration Google Calendar

L'intégration Google Calendar est maintenant **ACTIVE** et configurée dans l'application.

## ✅ État de l'intégration

- ✅ Librairie Google API Client installée (`google/apiclient v2.18.3`)
- ✅ Service GoogleCalendarService configuré
- ✅ Contrôleur GoogleCalendarController opérationnel
- ✅ Routes API disponibles
- ✅ Migration de la table `google_calendar_tokens` exécutée
- ✅ Variables d'environnement configurées

## 1. Configuration Google Cloud Console (Déjà fait)

✅ **Client ID** : `81947935268-qqk9p60v8mm6p8rd3prif96ffhvc3fm0.apps.googleusercontent.com`
✅ **Client Secret** : `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`
✅ **URI de redirection** : `https://activibe.be/api/google-calendar/callback`

## 2. Configuration de l'application (Déjà fait)

Les variables sont définies dans votre fichier `.env` :

```env
GOOGLE_CALENDAR_CLIENT_ID=81947935268-qqk9p60v8mm6p8rd3prif96ffhvc3fm0.apps.googleusercontent.com
GOOGLE_CALENDAR_CLIENT_SECRET=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
GOOGLE_CALENDAR_REDIRECT_URI=https://activibe.be/api/google-calendar/callback
GOOGLE_CALENDAR_APPLICATION_NAME=BOOKYOURCOACH
```

## 3. Installation des dépendances (Déjà fait)

✅ La librairie Google API Client est installée et opérationnelle.

## 4. Test de l'intégration

L'URL d'autorisation peut être générée avec succès :
```
https://accounts.google.com/o/oauth2/v2/auth?response_type=code&access_type=offline&client_id=81947935268-qqk9p60v8mm6p8rd3prif96ffhvc3fm0.apps.googleusercontent.com&redirect_uri=https%3A%2F%2Factivibe.be%2Fapi%2Fgoogle-calendar%2Fcallback&state&scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fcalendar&prompt=select_account%20consent
```

## 5. Utilisation

### Routes API disponibles

| Méthode | Route | Description |
|---------|-------|-------------|
| `GET` | `/api/google-calendar/auth-url` | Obtenir l'URL d'autorisation |
| `POST` | `/api/google-calendar/callback` | Traiter le callback d'autorisation |
| `GET` | `/api/google-calendar/calendars` | Obtenir la liste des calendriers |
| `GET` | `/api/google-calendar/events` | Obtenir les événements d'un calendrier |
| `POST` | `/api/google-calendar/events` | Créer un nouvel événement |
| `PUT` | `/api/google-calendar/events/{eventId}` | Mettre à jour un événement |
| `DELETE` | `/api/google-calendar/events/{eventId}` | Supprimer un événement |
| `POST` | `/api/google-calendar/sync-events` | Synchroniser les événements locaux |

### Processus d'autorisation

1. **Obtenir l'URL d'autorisation** :
   ```javascript
   const response = await $api.get('/google-calendar/auth-url')
   const authUrl = response.data.auth_url
   ```

2. **Rediriger l'utilisateur** vers l'URL d'autorisation

3. **Google redirige vers** `/api/google-calendar/callback` avec le code d'autorisation

4. **Le token est stocké** automatiquement dans la table `google_calendar_tokens`

### Gestion des calendriers

```javascript
// Obtenir les calendriers de l'utilisateur
const calendars = await $api.get('/google-calendar/calendars')

// Obtenir les événements d'un calendrier
const events = await $api.get('/google-calendar/events', {
  params: { calendar_id: 'primary' }
})

// Créer un événement
const newEvent = await $api.post('/google-calendar/events', {
  calendar_id: 'primary',
  event_data: {
    title: 'Cours d\'équitation',
    description: 'Cours particulier avec Sophie',
    start_time: '2025-09-25T14:00:00+02:00',
    end_time: '2025-09-25T15:00:00+02:00'
  }
})
```

### Synchronisation

```javascript
// Synchroniser les cours locaux avec Google Calendar
const sync = await $api.post('/google-calendar/sync-events', {
  calendar_id: 'primary'
})
```

## 6. Composants Frontend

Les composants suivants sont disponibles :

- **`TeacherCalendar.vue`** : Calendrier principal de l'enseignant
- **`GoogleCalendarIntegration.vue`** : Interface d'intégration Google Calendar

## 7. Sécurité

- ✅ Authentification requise pour toutes les routes
- ✅ Tokens stockés de manière sécurisée en base de données
- ✅ Gestion des tokens expirés avec refresh automatique
- ✅ Validation des données d'entrée

## 🎉 Prêt à utiliser !

L'intégration Google Calendar est maintenant entièrement fonctionnelle et prête à être utilisée dans l'application.