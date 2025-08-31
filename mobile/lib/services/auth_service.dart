import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../utils/api_config.dart';
import '../models/user.dart';

class AuthService {
  final Dio _dio = Dio();
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  
  AuthService() {
    _dio.options.baseUrl = ApiConfig.apiUrl;
    _dio.options.connectTimeout = const Duration(milliseconds: ApiConfig.connectTimeout);
    _dio.options.receiveTimeout = const Duration(milliseconds: ApiConfig.receiveTimeout);
    _dio.options.headers = ApiConfig.defaultHeaders;
  }
  
  // Connexion utilisateur
  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await _dio.post(
        ApiConfig.loginEndpoint,
        data: {
          'email': email,
          'password': password,
        },
      );
      
      if (response.statusCode == 200) {
        final data = response.data;
        
        // Sauvegarder le token
        await _storage.write(key: 'auth_token', value: data['token']);
        await _storage.write(key: 'user_data', value: jsonEncode(data['user']));
        
        return {
          'success': true,
          'user': User.fromJson(data['user']),
          'token': data['token'],
        };
      }
      
      return {
        'success': false,
        'message': 'Erreur de connexion',
      };
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur inattendue: $e',
      };
    }
  }
  
  // Déconnexion
  Future<bool> logout() async {
    try {
      final token = await _storage.read(key: 'auth_token');
      if (token != null) {
        await _dio.post(
          ApiConfig.logoutEndpoint,
          options: Options(headers: {'Authorization': 'Bearer $token'}),
        );
      }
      
      // Supprimer les données locales
      await _storage.deleteAll();
      return true;
    } catch (e) {
      // Même en cas d'erreur, on supprime les données locales
      await _storage.deleteAll();
      return true;
    }
  }
  
  // Vérifier si l'utilisateur est connecté
  Future<bool> isLoggedIn() async {
    final token = await _storage.read(key: 'auth_token');
    return token != null;
  }
  
  // Récupérer le token
  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }
  
  // Récupérer les données utilisateur
  Future<User?> getUser() async {
    try {
      final userData = await _storage.read(key: 'user_data');
      if (userData != null) {
        return User.fromJson(jsonDecode(userData));
      }
      return null;
    } catch (e) {
      return null;
    }
  }
  
  // Mettre à jour les données utilisateur
  Future<void> updateUserData(User user) async {
    await _storage.write(key: 'user_data', value: jsonEncode(user.toJson()));
  }
  
  // Gestion des erreurs Dio
  Map<String, dynamic> _handleDioError(DioException e) {
    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return {
          'success': false,
          'message': ApiConfig.networkErrorMessage,
        };
      case DioExceptionType.badResponse:
        final statusCode = e.response?.statusCode;
        final data = e.response?.data;
        
        if (statusCode == 401) {
          return {
            'success': false,
            'message': ApiConfig.unauthorizedMessage,
          };
        } else if (statusCode == 422) {
          // Erreurs de validation
          final errors = data['errors'] as Map<String, dynamic>?;
          if (errors != null) {
            final firstError = errors.values.first;
            return {
              'success': false,
              'message': firstError is List ? firstError.first : firstError.toString(),
            };
          }
        }
        
        return {
          'success': false,
          'message': data['message'] ?? ApiConfig.serverErrorMessage,
        };
      default:
        return {
          'success': false,
          'message': ApiConfig.networkErrorMessage,
        };
    }
  }
}
