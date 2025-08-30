import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config.dart';

class ApiClient {
  final Dio _dio;

  ApiClient._(this._dio);

  factory ApiClient({String? baseUrl, String? token}) {
    final dio = Dio(
      BaseOptions(
        baseUrl: baseUrl ?? AppConfig.apiBase,
        connectTimeout: const Duration(seconds: 10),
        receiveTimeout: const Duration(seconds: 15),
        headers: token != null ? {'Authorization': 'Bearer $token'} : {},
      ),
    );
    return ApiClient._(dio);
  }

  Dio get dio => _dio;
}

class ApiFactory {
  static const _tokenKey = 'auth_token';

  static Future<ApiClient> authed({String? baseUrl}) async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString(_tokenKey);
    return ApiClient(baseUrl: baseUrl, token: token);
  }
}