<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClubSettings;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClubSettingsController extends Controller
{
    /**
     * Récupérer tous les paramètres du club
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $club = $user->club;
        
        if (!$club) {
            return response()->json(['message' => 'Club non trouvé'], 404);
        }

        $settings = ClubSettings::getAllFeatures($club->id);

        return response()->json([
            'club' => $club,
            'settings' => $settings,
            'enabled_features' => ClubSettings::getEnabledFeatures($club->id)
        ]);
    }

    /**
     * Récupérer les paramètres par catégorie
     */
    public function getByCategory(Request $request, $category)
    {
        $user = $request->user();
        $club = $user->club;
        
        if (!$club) {
            return response()->json(['message' => 'Club non trouvé'], 404);
        }

        $settings = ClubSettings::getFeaturesByCategory($club->id, $category);

        return response()->json([
            'category' => $category,
            'settings' => $settings
        ]);
    }

    /**
     * Mettre à jour un paramètre spécifique
     */
    public function update(Request $request, $featureKey)
    {
        $user = $request->user();
        $club = $user->club;
        
        if (!$club) {
            return response()->json(['message' => 'Club non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_enabled' => 'sometimes|boolean',
            'configuration' => 'sometimes|array',
            'configuration.*' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $setting = ClubSettings::where('club_id', $club->id)
            ->where('feature_key', $featureKey)
            ->first();

        if (!$setting) {
            return response()->json(['message' => 'Paramètre non trouvé'], 404);
        }

        $setting->update($request->only(['is_enabled', 'configuration']));

        return response()->json([
            'message' => 'Paramètre mis à jour avec succès',
            'setting' => $setting
        ]);
    }

    /**
     * Mise à jour en lot de plusieurs paramètres
     */
    public function bulkUpdate(Request $request)
    {
        $user = $request->user();
        $club = $user->club;
        
        if (!$club) {
            return response()->json(['message' => 'Club non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.feature_key' => 'required|string',
            'settings.*.is_enabled' => 'sometimes|boolean',
            'settings.*.configuration' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updatedSettings = [];

        foreach ($request->settings as $settingData) {
            $setting = ClubSettings::where('club_id', $club->id)
                ->where('feature_key', $settingData['feature_key'])
                ->first();

            if ($setting) {
                $setting->update([
                    'is_enabled' => $settingData['is_enabled'] ?? $setting->is_enabled,
                    'configuration' => $settingData['configuration'] ?? $setting->configuration
                ]);
                $updatedSettings[] = $setting;
            }
        }

        return response()->json([
            'message' => 'Paramètres mis à jour avec succès',
            'updated_count' => count($updatedSettings),
            'settings' => $updatedSettings
        ]);
    }

    /**
     * Récupérer toutes les fonctionnalités disponibles
     */
    public function getAvailableFeatures()
    {
        $features = [
            'financial' => [
                [
                    'key' => 'financial_dashboard',
                    'name' => 'Dashboard Financier',
                    'description' => 'Affichage des statistiques financières et du CA',
                    'icon' => '📊',
                    'default_enabled' => true
                ],
                [
                    'key' => 'cash_register',
                    'name' => 'Système de Caisse',
                    'description' => 'Gestion de la caisse en ligne et des transactions',
                    'icon' => '💰',
                    'default_enabled' => true
                ],
                [
                    'key' => 'product_management',
                    'name' => 'Gestion des Produits',
                    'description' => 'Vente de produits et gestion des stocks',
                    'icon' => '🛍️',
                    'default_enabled' => false
                ],
                [
                    'key' => 'financial_reports',
                    'name' => 'Rapports Financiers',
                    'description' => 'Génération de rapports détaillés',
                    'icon' => '📈',
                    'default_enabled' => false
                ],
                [
                    'key' => 'profitability_analysis',
                    'name' => 'Analyse de Rentabilité',
                    'description' => 'Calculs de marge et analyse de rentabilité',
                    'icon' => '💹',
                    'default_enabled' => false
                ]
            ],
            'management' => [
                [
                    'key' => 'teacher_management',
                    'name' => 'Gestion des Enseignants',
                    'description' => 'Ajout, modification et gestion des enseignants',
                    'icon' => '👨‍🏫',
                    'default_enabled' => true
                ],
                [
                    'key' => 'student_management',
                    'name' => 'Gestion des Étudiants',
                    'description' => 'Inscription et gestion des étudiants',
                    'icon' => '👨‍🎓',
                    'default_enabled' => true
                ],
                [
                    'key' => 'lesson_planning',
                    'name' => 'Planning des Cours',
                    'description' => 'Gestion des créneaux et réservations',
                    'icon' => '📅',
                    'default_enabled' => true
                ],
                [
                    'key' => 'facility_management',
                    'name' => 'Gestion des Installations',
                    'description' => 'Gestion des manèges, bassins et équipements',
                    'icon' => '🏢',
                    'default_enabled' => true
                ],
                [
                    'key' => 'notifications',
                    'name' => 'Notifications',
                    'description' => 'Alertes et rappels automatiques',
                    'icon' => '🔔',
                    'default_enabled' => true
                ]
            ],
            'communication' => [
                [
                    'key' => 'internal_messaging',
                    'name' => 'Messagerie Interne',
                    'description' => 'Communication entre membres du club',
                    'icon' => '💬',
                    'default_enabled' => false
                ],
                [
                    'key' => 'push_notifications',
                    'name' => 'Notifications Push',
                    'description' => 'Alertes en temps réel sur mobile',
                    'icon' => '📱',
                    'default_enabled' => false
                ],
                [
                    'key' => 'email_automation',
                    'name' => 'Emails Automatiques',
                    'description' => 'Confirmations et rappels par email',
                    'icon' => '📧',
                    'default_enabled' => true
                ],
                [
                    'key' => 'sms_notifications',
                    'name' => 'Notifications SMS',
                    'description' => 'Alertes par SMS',
                    'icon' => '📲',
                    'default_enabled' => false
                ],
                [
                    'key' => 'social_media',
                    'name' => 'Réseaux Sociaux',
                    'description' => 'Intégration Facebook et Instagram',
                    'icon' => '📱',
                    'default_enabled' => false
                ]
            ],
            'advanced' => [
                [
                    'key' => 'loyalty_system',
                    'name' => 'Système de Fidélité',
                    'description' => 'Points et récompenses pour les membres',
                    'icon' => '⭐',
                    'default_enabled' => false
                ],
                [
                    'key' => 'referral_system',
                    'name' => 'Système de Parrainage',
                    'description' => 'Récompenses pour les recommandations',
                    'icon' => '🤝',
                    'default_enabled' => false
                ],
                [
                    'key' => 'events_management',
                    'name' => 'Gestion d\'Événements',
                    'description' => 'Organisation d\'événements spéciaux',
                    'icon' => '🎉',
                    'default_enabled' => false
                ],
                [
                    'key' => 'competitions',
                    'name' => 'Compétitions',
                    'description' => 'Gestion des compétitions et tournois',
                    'icon' => '🏆',
                    'default_enabled' => false
                ],
                [
                    'key' => 'online_training',
                    'name' => 'Formations en Ligne',
                    'description' => 'Modules de formation en ligne',
                    'icon' => '🎓',
                    'default_enabled' => false
                ]
            ],
            'integration' => [
                [
                    'key' => 'external_apis',
                    'name' => 'API Externes',
                    'description' => 'Intégration avec systèmes tiers',
                    'icon' => '🔗',
                    'default_enabled' => false
                ],
                [
                    'key' => 'payment_gateways',
                    'name' => 'Passerelles de Paiement',
                    'description' => 'Stripe, PayPal, virements',
                    'icon' => '💳',
                    'default_enabled' => true
                ],
                [
                    'key' => 'accounting_export',
                    'name' => 'Export Comptable',
                    'description' => 'Export vers logiciels comptables',
                    'icon' => '📊',
                    'default_enabled' => false
                ],
                [
                    'key' => 'calendar_sync',
                    'name' => 'Synchronisation Calendrier',
                    'description' => 'Google Calendar, Outlook',
                    'icon' => '📅',
                    'default_enabled' => false
                ],
                [
                    'key' => 'gps_tracking',
                    'name' => 'Géolocalisation',
                    'description' => 'Suivi GPS des cours',
                    'icon' => '📍',
                    'default_enabled' => false
                ]
            ]
        ];

        return response()->json([
            'features' => $features,
            'categories' => array_keys($features)
        ]);
    }

    /**
     * Réinitialiser les paramètres par défaut
     */
    public function resetToDefaults(Request $request)
    {
        $user = $request->user();
        $club = $user->club;
        
        if (!$club) {
            return response()->json(['message' => 'Club non trouvé'], 404);
        }

        // Supprimer tous les paramètres existants
        ClubSettings::where('club_id', $club->id)->delete();

        // Recréer avec les valeurs par défaut
        $this->createDefaultSettings($club);

        return response()->json([
            'message' => 'Paramètres réinitialisés avec succès',
            'settings' => ClubSettings::getAllFeatures($club->id)
        ]);
    }

    /**
     * Créer les paramètres par défaut pour un club
     */
    private function createDefaultSettings($club)
    {
        $availableFeatures = $this->getAvailableFeatures()->getData(true)['features'];
        $sortOrder = 0;

        foreach ($availableFeatures as $category => $features) {
            foreach ($features as $feature) {
                ClubSettings::create([
                    'club_id' => $club->id,
                    'feature_key' => $feature['key'],
                    'feature_name' => $feature['name'],
                    'feature_category' => $category,
                    'is_enabled' => $feature['default_enabled'],
                    'description' => $feature['description'],
                    'icon' => $feature['icon'],
                    'sort_order' => $sortOrder++
                ]);
            }
        }
    }
}