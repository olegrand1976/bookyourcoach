import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../models/user.dart';
import '../models/club.dart';
import '../utils/api_config.dart';

class AdminService {
  static final String _baseUrl = ApiConfig.apiUrl;
  static const String _tokenKey = 'auth_token';

  final Dio _dio = Dio();
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  
  AdminService() {
    _dio.options.baseUrl = _baseUrl;
    _dio.options.connectTimeout = const Duration(seconds: 10);
    _dio.options.receiveTimeout = const Duration(seconds: 10);

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
          _storage.delete(key: _tokenKey);
        }
        handler.next(error);
      },
    ));
  }

  // Récupérer le token d'authentification
  Future<String?> _getAuthToken() async {
    return await _storage.read(key: _tokenKey);
  }

  // Récupérer les statistiques de la plateforme
  Future<Map<String, dynamic>> getStats() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/admin/stats',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        return response.data;
      }

      throw Exception('Erreur lors de la récupération des statistiques');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer les activités récentes
  Future<List<Map<String, dynamic>>> getActivities({int? limit}) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/admin/activities',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        queryParameters: {
          if (limit != null) 'limit': limit,
        },
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'] ?? response.data;
        return data.cast<Map<String, dynamic>>();
      }

      throw Exception('Erreur lors de la récupération des activités');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer la liste des utilisateurs
  Future<Map<String, dynamic>> getUsers({
    int? page,
    int? perPage,
    String? search,
    String? role,
    String? status,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/admin/users',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        queryParameters: {
          if (page != null) 'page': page,
          if (perPage != null) 'per_page': perPage,
          if (search != null) 'search': search,
          if (role != null) 'role': role,
          if (status != null) 'status': status,
        },
      );

      if (response.statusCode == 200) {
        return response.data;
      }

      throw Exception('Erreur lors de la récupération des utilisateurs');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Créer un nouvel utilisateur
  Future<User> createUser({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String role,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.post(
        '/admin/users',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: {
          'name': name,
          'email': email,
          'password': password,
          'password_confirmation': passwordConfirmation,
          'role': role,
        },
      );

      if (response.statusCode == 201) {
        return User.fromJson(response.data);
      }

      throw Exception('Erreur lors de la création de l\'utilisateur');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Mettre à jour un utilisateur
  Future<User> updateUser({
    required int userId,
    String? name,
    String? email,
    String? role,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final data = <String, dynamic>{};
      if (name != null) data['name'] = name;
      if (email != null) data['email'] = email;
      if (role != null) data['role'] = role;

      final response = await _dio.put(
        '/admin/users/$userId',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: data,
      );

      if (response.statusCode == 200) {
        return User.fromJson(response.data);
      }

      throw Exception('Erreur lors de la mise à jour de l\'utilisateur');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Basculer le statut d'un utilisateur
  Future<User> toggleUserStatus(int userId) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.patch(
        '/admin/users/$userId/toggle-status',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        return User.fromJson(response.data);
      }

      throw Exception('Erreur lors du changement de statut');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer tous les paramètres
  Future<Map<String, dynamic>> getAllSettings() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/admin/settings',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        return response.data;
      }

      throw Exception('Erreur lors de la récupération des paramètres');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer les paramètres par type
  Future<Map<String, dynamic>> getSettings(String type) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/admin/settings/$type',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        return response.data;
      }

      throw Exception('Erreur lors de la récupération des paramètres');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Mettre à jour les paramètres
  Future<Map<String, dynamic>> updateSettings({
    required String type,
    required Map<String, dynamic> settings,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.put(
        '/admin/settings/$type',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: settings,
      );

      if (response.statusCode == 200) {
        return response.data;
      }

      throw Exception('Erreur lors de la mise à jour des paramètres');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer la liste des clubs
  Future<List<Club>> getClubs() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/admin/clubs',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'] ?? response.data;
        return data.map((json) => Club.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la récupération des clubs');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Créer un nouveau club
  Future<Club> createClub({
    required String name,
    required String address,
    required String city,
    required String postalCode,
    required String country,
    String? description,
    List<String>? facilities,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.post(
        '/admin/clubs',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: {
          'name': name,
          'address': address,
          'city': city,
          'postal_code': postalCode,
          'country': country,
          if (description != null) 'description': description,
          if (facilities != null) 'facilities': facilities,
        },
      );

      if (response.statusCode == 201) {
        return Club.fromJson(response.data);
      }

      throw Exception('Erreur lors de la création du club');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Mettre à jour un club
  Future<Club> updateClub({
    required int clubId,
    String? name,
    String? address,
    String? city,
    String? postalCode,
    String? country,
    String? description,
    List<String>? facilities,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final data = <String, dynamic>{};
      if (name != null) data['name'] = name;
      if (address != null) data['address'] = address;
      if (city != null) data['city'] = city;
      if (postalCode != null) data['postal_code'] = postalCode;
      if (country != null) data['country'] = country;
      if (description != null) data['description'] = description;
      if (facilities != null) data['facilities'] = facilities;

      final response = await _dio.put(
        '/admin/clubs/$clubId',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: data,
      );

      if (response.statusCode == 200) {
        return Club.fromJson(response.data);
      }

      throw Exception('Erreur lors de la mise à jour du club');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Supprimer un club
  Future<bool> deleteClub(int clubId) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.delete(
        '/admin/clubs/$clubId',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      return response.statusCode == 200 || response.statusCode == 204;
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Basculer le statut d'un club
  Future<Club> toggleClubStatus(int clubId) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.post(
        '/admin/clubs/$clubId/toggle-status',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        return Club.fromJson(response.data);
      }

      throw Exception('Erreur lors du changement de statut du club');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer le statut du système
  Future<Map<String, dynamic>> getSystemStatus() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/admin/system/status',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        return response.data;
      }

      throw Exception('Erreur lors de la récupération du statut système');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Vider le cache
  Future<bool> clearCache() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.post(
        '/admin/system/clear-cache',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      return response.statusCode == 200 || response.statusCode == 204;
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Gestion des erreurs Dio
  dynamic _handleDioError(DioException e) {
    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        throw Exception('Erreur de réseau: Veuillez vérifier votre connexion internet.');
      case DioExceptionType.badResponse:
        final statusCode = e.response?.statusCode;
        final data = e.response?.data;
        
        if (statusCode == 401) {
          throw Exception('Votre session a expiré. Veuillez vous reconnecter.');
        } else if (statusCode == 403) {
          throw Exception('Accès refusé - Droits administrateur requis.');
        } else if (statusCode == 422) {
          // Erreurs de validation
          final errors = data['errors'] as Map<String, dynamic>?;
          if (errors != null) {
            final firstError = errors.values.first;
            throw Exception(firstError is List ? firstError.first : firstError.toString());
          }
        }
        
        throw Exception(data['message'] ?? 'Une erreur est survenue lors de la requête.');
      default:
        throw Exception('Une erreur inattendue est survenue.');
    }
  }
}
