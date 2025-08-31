import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../models/student_preferences.dart';

class StudentPreferencesService {
  final Dio _dio;
  final FlutterSecureStorage _storage;
  static const String _baseUrl = 'http://localhost:8000/api';

  StudentPreferencesService()
      : _dio = Dio(),
        _storage = const FlutterSecureStorage() {
    _dio.options.baseUrl = _baseUrl;
    _dio.options.connectTimeout = const Duration(seconds: 10);
    _dio.options.receiveTimeout = const Duration(seconds: 10);
  }

  Future<String?> _getAuthToken() async {
    return await _storage.read(key: 'auth_token');
  }

  Future<void> _addAuthHeader() async {
    final token = await _getAuthToken();
    if (token != null) {
      _dio.options.headers['Authorization'] = 'Bearer $token';
    }
  }

  /// Récupérer les préférences de l'étudiant
  Future<StudentPreferences> getStudentPreferences() async {
    await _addAuthHeader();
    
    try {
      final response = await _dio.get('/student/preferences');
      
      if (response.statusCode == 200) {
        return StudentPreferences.fromJson(response.data['data']);
      } else {
        throw Exception('Erreur lors de la récupération des préférences');
      }
    } on DioException catch (e) {
      if (e.response?.statusCode == 404) {
        // Aucune préférence trouvée, retourner des préférences par défaut
        return StudentPreferences(
          id: 0,
          studentId: 0,
          preferredDisciplines: [],
          preferredLevels: [],
          preferredFormats: [],
          createdAt: DateTime.now(),
          updatedAt: DateTime.now(),
        );
      }
      throw Exception('Erreur réseau: ${e.message}');
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  /// Sauvegarder les préférences de l'étudiant
  Future<StudentPreferences> saveStudentPreferences({
    required List<String> disciplines,
    required List<String> levels,
    required List<String> formats,
    String? location,
    double? maxPrice,
    int? maxDistance,
    bool? notificationsEnabled,
  }) async {
    await _addAuthHeader();
    
    try {
      final data = {
        'preferred_disciplines': disciplines,
        'preferred_levels': levels,
        'preferred_formats': formats,
        if (location != null) 'location': location,
        if (maxPrice != null) 'max_price': maxPrice,
        if (maxDistance != null) 'max_distance': maxDistance,
        if (notificationsEnabled != null) 'notifications_enabled': notificationsEnabled,
      };

      final response = await _dio.post('/student/preferences', data: data);
      
      if (response.statusCode == 200 || response.statusCode == 201) {
        return StudentPreferences.fromJson(response.data['data']);
      } else {
        throw Exception('Erreur lors de la sauvegarde des préférences');
      }
    } on DioException catch (e) {
      throw Exception('Erreur réseau: ${e.message}');
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  /// Mettre à jour les préférences de l'étudiant
  Future<StudentPreferences> updateStudentPreferences({
    required int preferencesId,
    List<String>? disciplines,
    List<String>? levels,
    List<String>? formats,
    String? location,
    double? maxPrice,
    int? maxDistance,
    bool? notificationsEnabled,
  }) async {
    await _addAuthHeader();
    
    try {
      final data = <String, dynamic>{};
      if (disciplines != null) data['preferred_disciplines'] = disciplines;
      if (levels != null) data['preferred_levels'] = levels;
      if (formats != null) data['preferred_formats'] = formats;
      if (location != null) data['location'] = location;
      if (maxPrice != null) data['max_price'] = maxPrice;
      if (maxDistance != null) data['max_distance'] = maxDistance;
      if (notificationsEnabled != null) data['notifications_enabled'] = notificationsEnabled;

      final response = await _dio.put('/student/preferences/$preferencesId', data: data);
      
      if (response.statusCode == 200) {
        return StudentPreferences.fromJson(response.data['data']);
      } else {
        throw Exception('Erreur lors de la mise à jour des préférences');
      }
    } on DioException catch (e) {
      throw Exception('Erreur réseau: ${e.message}');
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  /// Supprimer les préférences de l'étudiant
  Future<bool> deleteStudentPreferences(int preferencesId) async {
    await _addAuthHeader();
    
    try {
      final response = await _dio.delete('/student/preferences/$preferencesId');
      
      return response.statusCode == 200 || response.statusCode == 204;
    } on DioException catch (e) {
      throw Exception('Erreur réseau: ${e.message}');
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  /// Obtenir des recommandations basées sur les préférences
  Future<List<Map<String, dynamic>>> getRecommendations() async {
    await _addAuthHeader();
    
    try {
      final response = await _dio.get('/student/recommendations');
      
      if (response.statusCode == 200) {
        return List<Map<String, dynamic>>.from(response.data['data']);
      } else {
        throw Exception('Erreur lors de la récupération des recommandations');
      }
    } on DioException catch (e) {
      throw Exception('Erreur réseau: ${e.message}');
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  /// Filtrer les enseignants selon les préférences
  Future<List<Map<String, dynamic>>> filterTeachersByPreferences({
    String? discipline,
    String? level,
    String? format,
    String? location,
    double? maxPrice,
  }) async {
    await _addAuthHeader();
    
    try {
      final queryParameters = <String, dynamic>{};
      if (discipline != null) queryParameters['discipline'] = discipline;
      if (level != null) queryParameters['level'] = level;
      if (format != null) queryParameters['format'] = format;
      if (location != null) queryParameters['location'] = location;
      if (maxPrice != null) queryParameters['max_price'] = maxPrice;

      final response = await _dio.get('/teachers/filter', queryParameters: queryParameters);
      
      if (response.statusCode == 200) {
        return List<Map<String, dynamic>>.from(response.data['data']);
      } else {
        throw Exception('Erreur lors du filtrage des enseignants');
      }
    } on DioException catch (e) {
      throw Exception('Erreur réseau: ${e.message}');
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  /// Filtrer les leçons selon les préférences
  Future<List<Map<String, dynamic>>> filterLessonsByPreferences({
    String? discipline,
    String? level,
    String? format,
    String? location,
    double? maxPrice,
  }) async {
    await _addAuthHeader();
    
    try {
      final queryParameters = <String, dynamic>{};
      if (discipline != null) queryParameters['discipline'] = discipline;
      if (level != null) queryParameters['level'] = level;
      if (format != null) queryParameters['format'] = format;
      if (location != null) queryParameters['location'] = location;
      if (maxPrice != null) queryParameters['max_price'] = maxPrice;

      final response = await _dio.get('/lessons/filter', queryParameters: queryParameters);
      
      if (response.statusCode == 200) {
        return List<Map<String, dynamic>>.from(response.data['data']);
      } else {
        throw Exception('Erreur lors du filtrage des leçons');
      }
    } on DioException catch (e) {
      throw Exception('Erreur réseau: ${e.message}');
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }
}
