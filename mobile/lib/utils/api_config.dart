
import 'platform_base_url_web.dart' if (dart.library.io) 'platform_base_url_io.dart';

class ApiConfig {
  // Timeouts
  static const int connectTimeout = 30000; // 30 secondes
  static const int receiveTimeout = 30000; // 30 secondes

  // Headers par défaut
  static const Map<String, String> defaultHeaders = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  // Endpoints de l'API
  static const String loginEndpoint = '/auth/login';
  static const String registerEndpoint = '/auth/register';
  static const String logoutEndpoint = '/auth/logout';
  static const String userEndpoint = '/user';
  static const String profileEndpoint = '/profile';
  static const String teachersEndpoint = '/teachers';
  static const String lessonsEndpoint = '/lessons';
  static const String bookingsEndpoint = '/bookings';

  // Configuration d'environnement (simple)
  static bool get isDevelopment => true;

  // URL de base selon la plateforme (web/desktop: localhost, Android émulateur: 10.0.2.2)
  static String get apiUrl => isDevelopment
      ? platformBaseApiUrl()
      : 'https://api.activibe.com/api';

  // URL d'auth
  static String get authUrl => '${apiUrl}/auth';

  // Messages d'erreur
  static const String networkErrorMessage = 'Erreur de connexion réseau';
  static const String serverErrorMessage = 'Erreur du serveur';
  static const String unauthorizedMessage = 'Non autorisé';
  static const String notFoundMessage = 'Ressource non trouvée';
}
