<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private $client;
    private $calendarService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName(config('services.google.calendar.application_name', 'Activibe Calendar'));
        $this->client->setScopes(Calendar::CALENDAR);
        
        // Configuration OAuth2
        $this->client->setClientId(config('services.google.calendar.client_id'));
        $this->client->setClientSecret(config('services.google.calendar.client_secret'));
        $this->client->setRedirectUri(config('services.google.calendar.redirect_uri'));
        
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        
        $this->calendarService = new Calendar($this->client);
    }

    /**
     * Obtenir l'URL d'autorisation Google
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Échanger le code d'autorisation contre un token d'accès
     */
    public function exchangeCodeForToken(string $code): array
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
        
        if (array_key_exists('error', $accessToken)) {
            throw new \Exception('Erreur lors de l\'échange du code: ' . $accessToken['error']);
        }
        
        return $accessToken;
    }

    /**
     * Définir le token d'accès
     */
    public function setAccessToken(array $token): void
    {
        $this->client->setAccessToken($token);
    }

    /**
     * Vérifier si le token est valide
     */
    public function isTokenValid(): bool
    {
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Obtenir la liste des calendriers de l'utilisateur
     */
    public function getCalendars(): array
    {
        $calendarList = $this->calendarService->calendarList->listCalendarList();
        $calendars = [];
        
        foreach ($calendarList->getItems() as $calendar) {
            $calendars[] = [
                'id' => $calendar->getId(),
                'name' => $calendar->getSummary(),
                'description' => $calendar->getDescription(),
                'primary' => $calendar->getPrimary(),
                'access_role' => $calendar->getAccessRole(),
                'time_zone' => $calendar->getTimeZone()
            ];
        }
        
        return $calendars;
    }

    /**
     * Créer un événement dans Google Calendar
     */
    public function createEvent(string $calendarId, array $eventData): Event
    {
        $event = new Event([
            'summary' => $eventData['title'],
            'description' => $eventData['description'] ?? '',
            'start' => new EventDateTime([
                'dateTime' => $eventData['start_time'],
                'timeZone' => 'Europe/Paris',
            ]),
            'end' => new EventDateTime([
                'dateTime' => $eventData['end_time'],
                'timeZone' => 'Europe/Paris',
            ]),
            'attendees' => $eventData['attendees'] ?? [],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60], // 1 jour avant
                    ['method' => 'popup', 'minutes' => 30], // 30 minutes avant
                ],
            ],
        ]);

        return $this->calendarService->events->insert($calendarId, $event);
    }

    /**
     * Mettre à jour un événement dans Google Calendar
     */
    public function updateEvent(string $calendarId, string $eventId, array $eventData): Event
    {
        $event = new Event([
            'summary' => $eventData['title'],
            'description' => $eventData['description'] ?? '',
            'start' => new EventDateTime([
                'dateTime' => $eventData['start_time'],
                'timeZone' => 'Europe/Paris',
            ]),
            'end' => new EventDateTime([
                'dateTime' => $eventData['end_time'],
                'timeZone' => 'Europe/Paris',
            ]),
            'attendees' => $eventData['attendees'] ?? [],
        ]);

        return $this->calendarService->events->update($calendarId, $eventId, $event);
    }

    /**
     * Supprimer un événement de Google Calendar
     */
    public function deleteEvent(string $calendarId, string $eventId): bool
    {
        try {
            $this->calendarService->events->delete($calendarId, $eventId);
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'événement Google Calendar: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir les événements d'un calendrier
     */
    public function getEvents(string $calendarId, string $timeMin = null, string $timeMax = null): array
    {
        $optParams = [
            'orderBy' => 'startTime',
            'singleEvents' => true,
        ];

        if ($timeMin) {
            $optParams['timeMin'] = $timeMin;
        }
        if ($timeMax) {
            $optParams['timeMax'] = $timeMax;
        }

        $results = $this->calendarService->events->listEvents($calendarId, $optParams);
        $events = [];

        foreach ($results->getItems() as $event) {
            $start = $event->getStart();
            $end = $event->getEnd();
            
            $events[] = [
                'id' => $event->getId(),
                'title' => $event->getSummary(),
                'description' => $event->getDescription(),
                'start_time' => $start->getDateTime() ?: $start->getDate(),
                'end_time' => $end->getDateTime() ?: $end->getDate(),
                'attendees' => $event->getAttendees(),
                'status' => $event->getStatus(),
                'html_link' => $event->getHtmlLink(),
            ];
        }

        return $events;
    }

    /**
     * Synchroniser les événements d'un calendrier local avec Google Calendar
     */
    public function syncEvents(string $calendarId, array $localEvents): array
    {
        $syncedEvents = [];
        $googleEvents = $this->getEvents($calendarId);
        
        // Créer un index des événements Google par ID
        $googleEventsIndex = [];
        foreach ($googleEvents as $event) {
            $googleEventsIndex[$event['id']] = $event;
        }
        
        // Synchroniser les événements locaux
        foreach ($localEvents as $localEvent) {
            try {
                if (isset($localEvent['google_event_id']) && isset($googleEventsIndex[$localEvent['google_event_id']])) {
                    // Mettre à jour l'événement existant
                    $updatedEvent = $this->updateEvent($calendarId, $localEvent['google_event_id'], $localEvent);
                    $syncedEvents[] = [
                        'local_id' => $localEvent['id'],
                        'google_id' => $updatedEvent->getId(),
                        'action' => 'updated'
                    ];
                } else {
                    // Créer un nouvel événement
                    $newEvent = $this->createEvent($calendarId, $localEvent);
                    $syncedEvents[] = [
                        'local_id' => $localEvent['id'],
                        'google_id' => $newEvent->getId(),
                        'action' => 'created'
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la synchronisation de l\'événement: ' . $e->getMessage());
                $syncedEvents[] = [
                    'local_id' => $localEvent['id'],
                    'error' => $e->getMessage(),
                    'action' => 'failed'
                ];
            }
        }
        
        return $syncedEvents;
    }

    /**
     * Obtenir les informations de l'utilisateur connecté
     */
    public function getUserInfo(): array
    {
        $userInfo = $this->client->verifyIdToken();
        return [
            'id' => $userInfo['sub'],
            'email' => $userInfo['email'],
            'name' => $userInfo['name'],
            'picture' => $userInfo['picture'] ?? null,
        ];
    }
}
