class AppConfig {
  static const String apiBase = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'http://localhost:8081/api',
  );
}