import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../utils/api_config.dart';
import '../models/lesson.dart';
import '../models/booking.dart';
import '../models/user.dart';

class StudentService {
  final Dio _dio = Dio();
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  
  StudentService() {
    _dio.options.baseUrl = ApiConfig.apiUrl;
    _dio.options.connectTimeout = const Duration(milliseconds: ApiConfig.connectTimeout);
    _dio.options.receiveTimeout = const Duration(milliseconds: ApiConfig.receiveTimeout);
    _dio.options.headers = ApiConfig.defaultHeaders;
  }

  // Récupérer le token d'authentification
  Future<String?> _getAuthToken() async {
    return await _storage.read(key: 'auth_token');
  }

  // Récupérer les cours disponibles
  Future<List<Lesson>> getAvailableLessons({String? subject, DateTime? date}) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/student/available-lessons',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        queryParameters: {
          if (subject != null) 'subject': subject,
          if (date != null) 'date': date.toIso8601String(),
        },
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'] ?? response.data;
        return data.map((json) => Lesson.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la récupération des cours disponibles');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer les réservations de l'élève
  Future<List<Booking>> getStudentBookings({String? status}) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/student/bookings',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        queryParameters: {
          if (status != null) 'status': status,
        },
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'] ?? response.data;
        return data.map((json) => Booking.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la récupération des réservations');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Réserver un cours
  Future<Booking> bookLesson({
    required int lessonId,
    String? notes,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.post(
        '/student/bookings',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: {
          'lesson_id': lessonId,
          'notes': notes,
        },
      );

      if (response.statusCode == 201) {
        return Booking.fromJson(response.data['data'] ?? response.data);
      }

      throw Exception('Erreur lors de la réservation');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Annuler une réservation
  Future<bool> cancelBooking(int bookingId) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.put(
        '/student/bookings/$bookingId/cancel',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      return response.statusCode == 200 || response.statusCode == 204;
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer les enseignants disponibles
  Future<List<User>> getAvailableTeachers({String? subject}) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/student/available-teachers',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        queryParameters: {
          if (subject != null) 'subject': subject,
        },
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'] ?? response.data;
        return data.map((json) => User.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la récupération des enseignants');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer les cours d'un enseignant spécifique
  Future<List<Lesson>> getTeacherLessons(int teacherId) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/student/teachers/$teacherId/lessons',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'] ?? response.data;
        return data.map((json) => Lesson.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la récupération des cours de l\'enseignant');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer les statistiques de l'élève
  Future<Map<String, dynamic>> getStudentStats() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/student/stats',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        return response.data['data'] ?? response.data;
      }

      throw Exception('Erreur lors de la récupération des statistiques');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Rechercher des cours
  Future<List<Lesson>> searchLessons({
    String? query,
    String? subject,
    DateTime? startDate,
    DateTime? endDate,
    double? maxPrice,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/student/search-lessons',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        queryParameters: {
          if (query != null) 'q': query,
          if (subject != null) 'subject': subject,
          if (startDate != null) 'start_date': startDate.toIso8601String(),
          if (endDate != null) 'end_date': endDate.toIso8601String(),
          if (maxPrice != null) 'max_price': maxPrice,
        },
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'] ?? response.data;
        return data.map((json) => Lesson.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la recherche de cours');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer l'historique des cours
  Future<List<Booking>> getLessonHistory() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/student/lesson-history',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'] ?? response.data;
        return data.map((json) => Booking.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la récupération de l\'historique');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Noter un cours terminé
  Future<bool> rateLesson({
    required int bookingId,
    required int rating,
    String? review,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.post(
        '/student/bookings/$bookingId/rate',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: {
          'rating': rating,
          'review': review,
        },
      );

      return response.statusCode == 200 || response.statusCode == 201;
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer les favoris de l'élève
  Future<List<User>> getFavoriteTeachers() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/student/favorite-teachers',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'] ?? response.data;
        return data.map((json) => User.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la récupération des enseignants favoris');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Ajouter/Retirer un enseignant des favoris
  Future<bool> toggleFavoriteTeacher(int teacherId) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.post(
        '/student/favorite-teachers/$teacherId/toggle',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      return response.statusCode == 200 || response.statusCode == 201;
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer tous les enseignants
  Future<List<User>> getTeachers() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/student/teachers',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'] ?? response.data;
        return data.map((json) => User.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la récupération des enseignants');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer les préférences de l'étudiant
  Future<Map<String, dynamic>> getStudentPreferences() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/student/preferences',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        return response.data['data'] ?? response.data;
      }

      throw Exception('Erreur lors de la récupération des préférences');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Sauvegarder les préférences de l'étudiant
  Future<Map<String, dynamic>> saveStudentPreferences({
    required List<String> disciplines,
    required List<String> levels,
    required List<String> formats,
    String? location,
    double? maxPrice,
    int? maxDistance,
    bool? notificationsEnabled,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.post(
        '/student/preferences',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: {
          'preferred_disciplines': disciplines,
          'preferred_levels': levels,
          'preferred_formats': formats,
          if (location != null) 'location': location,
          if (maxPrice != null) 'max_price': maxPrice,
          if (maxDistance != null) 'max_distance': maxDistance,
          if (notificationsEnabled != null) 'notifications_enabled': notificationsEnabled,
        },
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        return response.data['data'] ?? response.data;
      }

      throw Exception('Erreur lors de la sauvegarde des préférences');
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
        throw Exception(ApiConfig.networkErrorMessage);
      case DioExceptionType.badResponse:
        final statusCode = e.response?.statusCode;
        final data = e.response?.data;
        
        if (statusCode == 401) {
          throw Exception(ApiConfig.unauthorizedMessage);
        } else if (statusCode == 422) {
          // Erreurs de validation
          final errors = data['errors'] as Map<String, dynamic>?;
          if (errors != null) {
            final firstError = errors.values.first;
            throw Exception(firstError is List ? firstError.first : firstError.toString());
          }
        }
        
        throw Exception(data['message'] ?? ApiConfig.serverErrorMessage);
      default:
        throw Exception(ApiConfig.networkErrorMessage);
    }
  }
}
