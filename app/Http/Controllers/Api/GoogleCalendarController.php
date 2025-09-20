<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GoogleCalendarController extends Controller
{
    private $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Obtenir l'URL d'autorisation Google
     */
    public function getAuthUrl(Request $request): JsonResponse
    {
        try {
            $authUrl = $this->googleCalendarService->getAuthUrl();
            
            return response()->json([
                'success' => true,
                'auth_url' => $authUrl
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération de l\'URL d\'autorisation Google: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }

    /**
     * Traiter le callback d'autorisation Google
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $code = $request->input('code');
            if (!$code) {
                return response()->json(['error' => 'Code d\'autorisation manquant'], 400);
            }

            $token = $this->googleCalendarService->exchangeCodeForToken($code);
            $this->googleCalendarService->setAccessToken($token);
            
            $userInfo = $this->googleCalendarService->getUserInfo();
            $calendars = $this->googleCalendarService->getCalendars();

            // Sauvegarder les informations de l'utilisateur
            $userId = $request->user()->id;
            DB::table('google_calendar_tokens')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'access_token' => json_encode($token),
                    'user_info' => json_encode($userInfo),
                    'calendars' => json_encode($calendars),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Connexion Google Calendar réussie',
                'user_info' => $userInfo,
                'calendars' => $calendars
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement du callback Google: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }

    /**
     * Obtenir les calendriers de l'utilisateur
     */
    public function getCalendars(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $tokenData = DB::table('google_calendar_tokens')->where('user_id', $userId)->first();
            
            if (!$tokenData) {
                return response()->json(['error' => 'Aucun token Google Calendar trouvé'], 404);
            }

            $token = json_decode($tokenData->access_token, true);
            $this->googleCalendarService->setAccessToken($token);

            if (!$this->googleCalendarService->isTokenValid()) {
                return response()->json(['error' => 'Token Google Calendar expiré'], 401);
            }

            $calendars = $this->googleCalendarService->getCalendars();

            return response()->json([
                'success' => true,
                'calendars' => $calendars
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des calendriers: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }

    /**
     * Synchroniser les événements avec Google Calendar
     */
    public function syncEvents(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $calendarId = $request->input('calendar_id');
            
            if (!$calendarId) {
                return response()->json(['error' => 'ID de calendrier manquant'], 400);
            }

            $tokenData = DB::table('google_calendar_tokens')->where('user_id', $userId)->first();
            
            if (!$tokenData) {
                return response()->json(['error' => 'Aucun token Google Calendar trouvé'], 404);
            }

            $token = json_decode($tokenData->access_token, true);
            $this->googleCalendarService->setAccessToken($token);

            if (!$this->googleCalendarService->isTokenValid()) {
                return response()->json(['error' => 'Token Google Calendar expiré'], 401);
            }

            // Récupérer les événements locaux
            $localEvents = DB::table('lessons')
                ->where('teacher_id', $userId)
                ->where('club_id', $calendarId === 'personal' ? null : $calendarId)
                ->get()
                ->toArray();

            // Synchroniser avec Google Calendar
            $syncedEvents = $this->googleCalendarService->syncEvents($calendarId, $localEvents);

            return response()->json([
                'success' => true,
                'message' => 'Synchronisation terminée',
                'synced_events' => $syncedEvents
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation des événements: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }

    /**
     * Créer un événement dans Google Calendar
     */
    public function createEvent(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $calendarId = $request->input('calendar_id');
            $eventData = $request->input('event_data');

            if (!$calendarId || !$eventData) {
                return response()->json(['error' => 'Données manquantes'], 400);
            }

            $tokenData = DB::table('google_calendar_tokens')->where('user_id', $userId)->first();
            
            if (!$tokenData) {
                return response()->json(['error' => 'Aucun token Google Calendar trouvé'], 404);
            }

            $token = json_decode($tokenData->access_token, true);
            $this->googleCalendarService->setAccessToken($token);

            if (!$this->googleCalendarService->isTokenValid()) {
                return response()->json(['error' => 'Token Google Calendar expiré'], 401);
            }

            $event = $this->googleCalendarService->createEvent($calendarId, $eventData);

            return response()->json([
                'success' => true,
                'message' => 'Événement créé avec succès',
                'event' => [
                    'id' => $event->getId(),
                    'title' => $event->getSummary(),
                    'start_time' => $event->getStart()->getDateTime(),
                    'end_time' => $event->getEnd()->getDateTime(),
                    'html_link' => $event->getHtmlLink()
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'événement: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }

    /**
     * Mettre à jour un événement dans Google Calendar
     */
    public function updateEvent(Request $request, string $eventId): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $calendarId = $request->input('calendar_id');
            $eventData = $request->input('event_data');

            if (!$calendarId || !$eventData) {
                return response()->json(['error' => 'Données manquantes'], 400);
            }

            $tokenData = DB::table('google_calendar_tokens')->where('user_id', $userId)->first();
            
            if (!$tokenData) {
                return response()->json(['error' => 'Aucun token Google Calendar trouvé'], 404);
            }

            $token = json_decode($tokenData->access_token, true);
            $this->googleCalendarService->setAccessToken($token);

            if (!$this->googleCalendarService->isTokenValid()) {
                return response()->json(['error' => 'Token Google Calendar expiré'], 401);
            }

            $event = $this->googleCalendarService->updateEvent($calendarId, $eventId, $eventData);

            return response()->json([
                'success' => true,
                'message' => 'Événement mis à jour avec succès',
                'event' => [
                    'id' => $event->getId(),
                    'title' => $event->getSummary(),
                    'start_time' => $event->getStart()->getDateTime(),
                    'end_time' => $event->getEnd()->getDateTime(),
                    'html_link' => $event->getHtmlLink()
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de l\'événement: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }

    /**
     * Supprimer un événement de Google Calendar
     */
    public function deleteEvent(Request $request, string $eventId): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $calendarId = $request->input('calendar_id');

            if (!$calendarId) {
                return response()->json(['error' => 'ID de calendrier manquant'], 400);
            }

            $tokenData = DB::table('google_calendar_tokens')->where('user_id', $userId)->first();
            
            if (!$tokenData) {
                return response()->json(['error' => 'Aucun token Google Calendar trouvé'], 404);
            }

            $token = json_decode($tokenData->access_token, true);
            $this->googleCalendarService->setAccessToken($token);

            if (!$this->googleCalendarService->isTokenValid()) {
                return response()->json(['error' => 'Token Google Calendar expiré'], 401);
            }

            $success = $this->googleCalendarService->deleteEvent($calendarId, $eventId);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Événement supprimé avec succès'
                ], 200);
            } else {
                return response()->json(['error' => 'Erreur lors de la suppression'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'événement: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }

    /**
     * Obtenir les événements d'un calendrier
     */
    public function getEvents(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $calendarId = $request->input('calendar_id');
            $timeMin = $request->input('time_min');
            $timeMax = $request->input('time_max');

            if (!$calendarId) {
                return response()->json(['error' => 'ID de calendrier manquant'], 400);
            }

            $tokenData = DB::table('google_calendar_tokens')->where('user_id', $userId)->first();
            
            if (!$tokenData) {
                return response()->json(['error' => 'Aucun token Google Calendar trouvé'], 404);
            }

            $token = json_decode($tokenData->access_token, true);
            $this->googleCalendarService->setAccessToken($token);

            if (!$this->googleCalendarService->isTokenValid()) {
                return response()->json(['error' => 'Token Google Calendar expiré'], 401);
            }

            $events = $this->googleCalendarService->getEvents($calendarId, $timeMin, $timeMax);

            return response()->json([
                'success' => true,
                'events' => $events
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des événements: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }
}
