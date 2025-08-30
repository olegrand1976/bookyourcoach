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
    // TODO: implémenter l'appel API /auth/login (selon backend)
    // final client = ApiClient();
    // final res = await client.dio.post('/auth/login', data: { 'email': email, 'password': password });
    // final token = res.data['token'];
    // await saveToken(token);
    return false;
  }
}