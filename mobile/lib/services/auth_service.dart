import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../models/user.dart';

class AuthService {
  static const String _baseUrl = 'http://localhost:8081/api';
  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'user_data';
  
  final Dio _dio = Dio();
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  AuthService() {
    _dio.options.baseUrl = _baseUrl;
    _dio.options.connectTimeout = const Duration(seconds: 10);
    _dio.options.receiveTimeout = const Duration(seconds: 10);
    
    // Ajouter un intercepteur pour inclure le token dans les requêtes
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read(key: _tokenKey);
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) {
          // Token expiré ou invalide
          _storage.delete(key: _tokenKey);
          _storage.delete(key: _userKey);
        }
        handler.next(error);
      },
    ));
  }

  // Connexion
  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await _dio.post('/auth/login', data: {
        'email': email,
        'password': password,
      });

      if (response.statusCode == 200) {
        final data = response.data;
        
        // Sauvegarder le token
        if (data['token'] != null) {
          await _storage.write(key: _tokenKey, value: data['token']);
        }
        
        // Sauvegarder les données utilisateur
        if (data['user'] != null) {
          await _storage.write(
            key: _userKey, 
            value: jsonEncode(data['user'])
          );
        }

        return {
          'success': true,
          'user': User.fromJson(data['user']),
          'token': data['token'],
        };
      } else {
        return {
          'success': false,
          'message': 'Erreur de connexion',
        };
      }
    } on DioException catch (e) {
      String message = 'Erreur de connexion';
      
      if (e.response?.statusCode == 422) {
        // Erreurs de validation
        final errors = e.response?.data['errors'];
        if (errors != null) {
          message = errors.values.first.toString();
        }
      } else if (e.response?.statusCode == 401) {
        message = 'Email ou mot de passe incorrect';
      } else if (e.type == DioExceptionType.connectionTimeout) {
        message = 'Délai de connexion dépassé';
      } else if (e.type == DioExceptionType.receiveTimeout) {
        message = 'Délai de réception dépassé';
      } else if (e.type == DioExceptionType.connectionError) {
        message = 'Erreur de connexion au serveur';
      }

      return {
        'success': false,
        'message': message,
      };
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
      final response = await _dio.post('/auth/logout');
      
      // Supprimer le token et les données utilisateur
      await _storage.delete(key: _tokenKey);
      await _storage.delete(key: _userKey);
      
      return response.statusCode == 200;
    } catch (e) {
      // Même en cas d'erreur, supprimer les données locales
      await _storage.delete(key: _tokenKey);
      await _storage.delete(key: _userKey);
      return true;
    }
  }

  // Vérifier si l'utilisateur est connecté
  Future<bool> isLoggedIn() async {
    final token = await _storage.read(key: _tokenKey);
    return token != null;
  }

  // Obtenir les données utilisateur
  Future<User?> getUser() async {
    try {
      final userData = await _storage.read(key: _userKey);
      if (userData != null) {
        return User.fromJson(jsonDecode(userData));
      }
      
      // Si pas de données locales, essayer de récupérer depuis l'API
      final response = await _dio.get('/auth/user');
      if (response.statusCode == 200) {
        final user = User.fromJson(response.data);
        await _storage.write(
          key: _userKey, 
          value: jsonEncode(response.data)
        );
        return user;
      }
      
      return null;
    } catch (e) {
      return null;
    }
  }

  // Mettre à jour les données utilisateur
  Future<bool> updateUserData(User user) async {
    try {
      await _storage.write(
        key: _userKey, 
        value: jsonEncode(user.toJson())
      );
      return true;
    } catch (e) {
      return false;
    }
  }

  // Obtenir le token
  Future<String?> getToken() async {
    return await _storage.read(key: _tokenKey);
  }

  // Tester la connexion à l'API
  Future<bool> testConnection() async {
    try {
      final response = await _dio.get('/app-settings/public');
      return response.statusCode == 200;
    } catch (e) {
      return false;
    }
  }
}
