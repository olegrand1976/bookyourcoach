import 'package:shared_preferences/shared_preferences.dart';
import 'api_client.dart';

class AuthService {
  static const _tokenKey = 'auth_token';

  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  Future<bool> login(String email, String password) async {
    try {
      final client = ApiClient();
      final res = await client.dio.post('/auth/login', data: {
        'email': email,
        'password': password,
      });
      final data = res.data is Map<String, dynamic> ? res.data as Map<String, dynamic> : <String, dynamic>{};
      final token = data['token'] as String?;
      if (token == null || token.isEmpty) return false;
      await saveToken(token);
      return true;
    } catch (_) {
      return false;
    }
  }

  Future<bool> register({
    required String name,
    required String email,
    required String password,
  }) async {
    try {
      final client = ApiClient();
      final res = await client.dio.post('/auth/register', data: {
        'name': name,
        'email': email,
        'password': password,
      });
      final data = res.data is Map<String, dynamic> ? res.data as Map<String, dynamic> : <String, dynamic>{};
      final token = data['token'] as String?;
      if (token == null || token.isEmpty) return false;
      await saveToken(token);
      return true;
    } catch (_) {
      return false;
    }
  }

  Future<bool> requestPasswordReset(String email) async {
    try {
      final client = ApiClient();
      await client.dio.post('/auth/forgot-password', data: {
        'email': email,
      });
      return true;
    } catch (_) {
      return false;
    }
  }
}