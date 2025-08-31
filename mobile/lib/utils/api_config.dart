class ApiConfig {
  // Configuration de base de l'API
  static const String baseUrl = 'http://localhost:8081/api';
  static const String authUrl = 'http://localhost:8081/api/auth';
  
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
  
  // Configuration pour le développement
  static bool get isDevelopment => true;
  static String get apiUrl => isDevelopment ? baseUrl : 'https://api.bookyourcoach.com/api';
  
  // Messages d'erreur
  static const String networkErrorMessage = 'Erreur de connexion réseau';
  static const String serverErrorMessage = 'Erreur du serveur';
  static const String unauthorizedMessage = 'Non autorisé';
  static const String notFoundMessage = 'Ressource non trouvée';
}
