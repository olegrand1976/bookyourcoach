import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../models/user.dart';
import '../utils/api_config.dart';

class AuthService {
  static final String _baseUrl = ApiConfig.apiUrl;
  static const String _tokenKey = 'auth_token';
  static const String _userKey = 'user_data';
  static const String _rememberMeKey = 'remember_me';
  static const String _savedEmailKey = 'saved_email';
  
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
  Future<Map<String, dynamic>> login(String email, String password, {bool rememberMe = false}) async {
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

        // Sauvegarder l'option "Se souvenir de moi"
        await _storage.write(key: _rememberMeKey, value: rememberMe.toString());
        
        // Si l'utilisateur veut qu'on se souvienne de lui, sauvegarder l'email
        if (rememberMe) {
          await _storage.write(key: _savedEmailKey, value: email);
        } else {
          // Sinon, supprimer l'email sauvegardé
          await _storage.delete(key: _savedEmailKey);
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
      
      // Vérifier si "Se souvenir de moi" est activé
      final rememberMe = await _storage.read(key: _rememberMeKey);
      
      // Supprimer le token et les données utilisateur
      await _storage.delete(key: _tokenKey);
      await _storage.delete(key: _userKey);
      
      // Si "Se souvenir de moi" n'est pas activé, supprimer aussi l'email
      if (rememberMe != 'true') {
        await _storage.delete(key: _savedEmailKey);
        await _storage.delete(key: _rememberMeKey);
      }
      
      return response.statusCode == 200;
    } catch (e) {
      // Même en cas d'erreur, supprimer les données locales
      await _storage.delete(key: _tokenKey);
      await _storage.delete(key: _userKey);
      
      // Vérifier si "Se souvenir de moi" est activé
      final rememberMe = await _storage.read(key: _rememberMeKey);
      if (rememberMe != 'true') {
        await _storage.delete(key: _savedEmailKey);
        await _storage.delete(key: _rememberMeKey);
      }
      
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

  // Obtenir l'email sauvegardé
  Future<String?> getSavedEmail() async {
    final rememberMe = await _storage.read(key: _rememberMeKey);
    if (rememberMe == 'true') {
      return await _storage.read(key: _savedEmailKey);
    }
    return null;
  }

  // Vérifier si "Se souvenir de moi" est activé
  Future<bool> isRememberMeEnabled() async {
    final rememberMe = await _storage.read(key: _rememberMeKey);
    return rememberMe == 'true';
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
