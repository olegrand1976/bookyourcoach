<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Récupérer toutes les notifications de l'utilisateur
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $notifications = $this->notificationService->getUserNotifications($user, 50);

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des notifications'
            ], 500);
        }
    }

    /**
     * Compter les notifications non lues
     */
    public function unreadCount(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non authentifié'
                ], 401);
            }
            
            $count = $this->notificationService->getUnreadCount($user);

            return response()->json([
                'success' => true,
                'count' => $count
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur comptage notifications: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Retourner 0 au lieu d'une erreur pour ne pas bloquer l'interface
            return response()->json([
                'success' => true,
                'count' => 0,
                'message' => 'Erreur lors du comptage des notifications, valeur par défaut: 0'
            ]);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur marquage notification: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage de la notification'
            ], 500);
        }
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $user = $request->user();
            
            $this->notificationService->markAllAsRead($user);

            return response()->json([
                'success' => true,
                'message' => 'Toutes les notifications ont été marquées comme lues'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur marquage toutes notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage des notifications'
            ], 500);
        }
    }
}

