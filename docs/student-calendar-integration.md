# Intégration du Calendrier Étudiant

## Vue d'ensemble

L'intégration du calendrier étudiant permet aux étudiants de visualiser et gérer leurs cours réservés et de découvrir les leçons disponibles, avec une synchronisation Google Calendar.

## Fonctionnalités

### 1. Calendrier Étudiant (`StudentCalendar.vue`)

- **Vues multiples** : Mois, Semaine, Jour
- **Événements** : Cours réservés (vert) et leçons disponibles (bleu)
- **Navigation** : Précédent/Suivant, retour à aujourd'hui
- **Détails** : Modal avec informations complètes de l'événement
- **Réservation** : Bouton pour réserver directement depuis le calendrier

### 2. Page Planning (`/student/schedule`)

- **Interface** : Page dédiée au planning étudiant
- **Intégration Google Calendar** : Synchronisation bidirectionnelle
- **Navigation** : Retour au dashboard étudiant

### 3. Intégration Google Calendar

- **Connexion** : Authentification OAuth2 avec Google
- **Synchronisation** : Import/export des événements
- **Paramètres** : Configuration des options de sync
- **Historique** : Suivi des synchronisations

## Structure des Données

### Table `bookings`

```sql
CREATE TABLE bookings (
    id BIGINT PRIMARY KEY,
    student_id BIGINT REFERENCES users(id),
    lesson_id BIGINT REFERENCES lessons(id),
    status ENUM('pending', 'confirmed', 'cancelled', 'completed'),
    booked_at TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Types d'événements

- **`booking`** : Cours réservé par l'étudiant (affiché en vert)
- **`lesson`** : Leçon disponible à la réservation (affiché en bleu)

## API Endpoints

### Calendrier Étudiant

- `GET /student/calendar` - Récupérer les événements du calendrier
- `GET /student/clubs` - Récupérer les clubs de l'étudiant
- `POST /student/calendar/sync-google` - Synchroniser avec Google Calendar

### Google Calendar

- `GET /student/google-calendar/auth-url` - URL d'authentification
- `POST /student/google-calendar/callback` - Callback OAuth
- `GET /student/google-calendar/calendars` - Liste des calendriers
- `POST /student/google-calendar/sync-events` - Synchroniser les événements

## Utilisation

### 1. Accès au Planning

```vue
<NuxtLink to="/student/schedule">
  Mon Planning
</NuxtLink>
```

### 2. Intégration dans une Page

```vue
<template>
  <div>
    <GoogleCalendarIntegration :student-id="authStore.user?.id" />
    <StudentCalendar :student-id="authStore.user?.id" />
  </div>
</template>
```

### 3. Réservation d'une Leçon

```javascript
const bookLesson = async (lesson) => {
  try {
    await $api.post('/student/bookings', {
      lesson_id: lesson.id,
      student_id: props.studentId
    })
    await loadCalendarEvents()
  } catch (error) {
    console.error('Erreur lors de la réservation:', error)
  }
}
```

## Configuration

### Variables d'Environnement

```env
GOOGLE_CALENDAR_CLIENT_ID=your_client_id
GOOGLE_CALENDAR_CLIENT_SECRET=your_client_secret
GOOGLE_CALENDAR_REDIRECT_URI=your_redirect_uri
```

### Permissions Requises

- `https://www.googleapis.com/auth/calendar.readonly`
- `https://www.googleapis.com/auth/calendar.events`

## Sécurité

- **Authentification** : Token Sanctum requis pour toutes les API
- **Autorisation** : Vérification des droits étudiant
- **Validation** : Validation des données d'entrée
- **Logs** : Traçabilité des actions importantes

## Développement

### Ajout de Nouvelles Fonctionnalités

1. **Événements personnalisés** : Étendre le type d'événements
2. **Notifications** : Ajouter des rappels automatiques
3. **Export** : Permettre l'export en différents formats
4. **Thèmes** : Personnalisation de l'apparence

### Tests

```bash
# Tests unitaires
npm run test:unit

# Tests d'intégration
npm run test:integration

# Tests E2E
npm run test:e2e
```

## Dépannage

### Problèmes Courants

1. **Synchronisation Google Calendar** : Vérifier les credentials OAuth
2. **Événements manquants** : Vérifier les permissions de la base de données
3. **Performance** : Optimiser les requêtes avec des index appropriés

### Logs

```bash
# Logs de l'application
tail -f storage/logs/laravel.log

# Logs de synchronisation
grep "Google Calendar" storage/logs/laravel.log
```

## Roadmap

- [ ] Notifications push pour les rappels
- [ ] Export PDF du planning
- [ ] Intégration avec d'autres calendriers (Outlook, Apple)
- [ ] Mode hors ligne
- [ ] Partage de calendrier entre étudiants