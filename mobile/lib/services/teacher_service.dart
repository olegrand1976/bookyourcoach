
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../models/lesson.dart';
import '../models/availability.dart';
import '../models/user.dart';
import '../utils/api_config.dart';

class TeacherService {
  static const String _tokenKey = 'auth_token';

  final Dio _dio = Dio();
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  
  TeacherService() {
    _dio.options.baseUrl = ApiConfig.apiUrl;
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

  // Récupérer les cours de l'enseignant
  Future<List<Lesson>> getTeacherLessons({String? status, DateTime? date}) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/teacher/lessons',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        queryParameters: {
          if (status != null) 'status': status,
          if (date != null) 'date': date.toIso8601String(),
        },
      );

      if (response.statusCode == 200) {
        try {
          final List<dynamic> data = response.data is List ? response.data : [response.data];
          return data.map((json) => Lesson.fromJson(json)).toList();
        } catch (parseError) {
          print('Erreur de parsing des données des cours: $parseError');
          print('Données reçues: ${response.data}');
          throw Exception('Erreur de format des données reçues du serveur');
        }
      }

      throw Exception('Erreur lors de la récupération des cours');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Créer un nouveau cours
  Future<Lesson> createLesson({
    required String title,
    required String description,
    required DateTime startTime,
    required DateTime endTime,
    String? location,
    double? price,
    String? notes,
    int? studentId, // Étudiant principal (pour compatibilité)
    List<int>? studentIds, // Nouveaux étudiants multiples
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final data = {
        'title': title,
        'description': description,
        'start_time': startTime.toIso8601String(),
        'end_time': endTime.toIso8601String(),
        'location': location,
        'price': price,
        'notes': notes,
        if (studentId != null) 'student_id': studentId,
        if (studentIds != null && studentIds.isNotEmpty) 'student_ids': studentIds,
      };

      final response = await _dio.post(
        '/teacher/lessons',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: data,
      );

      if (response.statusCode == 201) {
        return Lesson.fromJson(response.data);
      }

      throw Exception('Erreur lors de la création du cours');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Mettre à jour un cours
  Future<Lesson> updateLesson({
    required int lessonId,
    String? title,
    String? description,
    DateTime? startTime,
    DateTime? endTime,
    String? location,
    double? price,
    String? notes,
    String? status,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final data = <String, dynamic>{};
      if (title != null) data['title'] = title;
      if (description != null) data['description'] = description;
      if (startTime != null) data['start_time'] = startTime.toIso8601String();
      if (endTime != null) data['end_time'] = endTime.toIso8601String();
      if (location != null) data['location'] = location;
      if (price != null) data['price'] = price;
      if (notes != null) data['notes'] = notes;
      if (status != null) data['status'] = status;

      final response = await _dio.put(
        '/teacher/lessons/$lessonId',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: data,
      );

      if (response.statusCode == 200) {
        return Lesson.fromJson(response.data);
      }

      throw Exception('Erreur lors de la mise à jour du cours');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Supprimer un cours
  Future<bool> deleteLesson(int lessonId) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.delete(
        '/teacher/lessons/$lessonId',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      return response.statusCode == 200 || response.statusCode == 204;
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer la liste des élèves
  Future<List<User>> getStudents() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/teacher/students',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data is List ? response.data : [response.data];
        return data.map((json) => User.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la récupération des élèves');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer les disponibilités de l'enseignant
  Future<List<Availability>> getTeacherAvailabilities() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/teacher/availabilities',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data is List ? response.data : [response.data];
        return data.map((json) => Availability.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la récupération des disponibilités');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Créer une disponibilité
  Future<Availability> createAvailability({
    required DateTime startTime,
    required DateTime endTime,
    required String dayOfWeek,
    String? notes,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.post(
        '/teacher/availabilities',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: {
          'start_time': startTime.toIso8601String(),
          'end_time': endTime.toIso8601String(),
          'day_of_week': dayOfWeek,
          'notes': notes,
        },
      );

      if (response.statusCode == 201) {
        return Availability.fromJson(response.data);
      }

      throw Exception('Erreur lors de la création de la disponibilité');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Mettre à jour une disponibilité
  Future<Availability> updateAvailability({
    required int availabilityId,
    DateTime? startTime,
    DateTime? endTime,
    String? dayOfWeek,
    bool? isAvailable,
    String? notes,
  }) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final data = <String, dynamic>{};
      if (startTime != null) data['start_time'] = startTime.toIso8601String();
      if (endTime != null) data['end_time'] = endTime.toIso8601String();
      if (dayOfWeek != null) data['day_of_week'] = dayOfWeek;
      if (isAvailable != null) data['is_available'] = isAvailable;
      if (notes != null) data['notes'] = notes;

      final response = await _dio.put(
        '/teacher/availabilities/$availabilityId',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
        data: data,
      );

      if (response.statusCode == 200) {
        return Availability.fromJson(response.data);
      }

      throw Exception('Erreur lors de la mise à jour de la disponibilité');
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Supprimer une disponibilité
  Future<bool> deleteAvailability(int availabilityId) async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.delete(
        '/teacher/availabilities/$availabilityId',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      return response.statusCode == 200 || response.statusCode == 204;
    } on DioException catch (e) {
      return _handleDioError(e);
    } catch (e) {
      throw Exception('Erreur inattendue: $e');
    }
  }

  // Récupérer les statistiques de l'enseignant
  Future<Map<String, dynamic>> getTeacherStats() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/teacher/stats',
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

  // Récupérer les étudiants de l'enseignant
  Future<List<User>> getTeacherStudents() async {
    try {
      final token = await _getAuthToken();
      if (token == null) throw Exception('Token non trouvé');

      final response = await _dio.get(
        '/teacher/students',
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      if (response.statusCode == 200) {
        final List<dynamic> data = response.data is List ? response.data : [response.data];
        return data.map((json) => User.fromJson(json)).toList();
      }

      throw Exception('Erreur lors de la récupération des étudiants');
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
        throw Exception('Network error. Please check your internet connection.');
      case DioExceptionType.badResponse:
        final statusCode = e.response?.statusCode;
        final data = e.response?.data;
        
        if (statusCode == 401) {
          throw Exception('Unauthorized. Please log in again.');
        } else if (statusCode == 422) {
          // Erreurs de validation
          final errors = data['errors'] as Map<String, dynamic>?;
          if (errors != null) {
            final firstError = errors.values.first;
            throw Exception(firstError is List ? firstError.first : firstError.toString());
          }
        }
        
        throw Exception(data['message'] ?? 'Server error. Please try again later.');
      default:
        throw Exception('Network error. Please check your internet connection.');
    }
  }
}

