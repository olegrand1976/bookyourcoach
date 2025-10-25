import 'package:dio/dio.dart';
import '../models/discipline.dart';
import 'auth_service.dart';

class PreferencesService {
  final Dio _dio;
  final AuthService _authService;

  PreferencesService(this._dio, this._authService);

  Future<List<Discipline>> getDisciplines() async {
    try {
      final token = await _authService.getToken();
      final response = await _dio.get(
        '/api/student/disciplines',
        options: Options(
          headers: {'Authorization': 'Bearer $token'},
        ),
      );

      if (response.data['success']) {
        return (response.data['data'] as List)
            .map((json) => Discipline.fromJson(json))
            .toList();
      }
      throw Exception('Erreur lors du chargement des disciplines');
    } catch (e) {
      throw Exception('Erreur lors du chargement des disciplines: $e');
    }
  }

  Future<List<StudentPreference>> getPreferences() async {
    try {
      final token = await _authService.getToken();
      final response = await _dio.get(
        '/api/student/preferences/advanced',
        options: Options(
          headers: {'Authorization': 'Bearer $token'},
        ),
      );

      if (response.data['success']) {
        final data = response.data['data'];
        if (data is Map) {
          // Si les préférences sont groupées par discipline
          final List<StudentPreference> preferences = [];
          data.forEach((key, value) {
            if (value is List) {
              preferences.addAll(
                value.map((json) => StudentPreference.fromJson(json)).toList(),
              );
            }
          });
          return preferences;
        } else if (data is List) {
          return data.map((json) => StudentPreference.fromJson(json)).toList();
        }
      }
      throw Exception('Erreur lors du chargement des préférences');
    } catch (e) {
      throw Exception('Erreur lors du chargement des préférences: $e');
    }
  }

  Future<void> updatePreferences(List<StudentPreference> preferences) async {
    try {
      final token = await _authService.getToken();
      final response = await _dio.put(
        '/api/student/preferences/advanced',
        data: {
          'preferences': preferences.map((p) => p.toJson()).toList(),
        },
        options: Options(
          headers: {'Authorization': 'Bearer $token'},
        ),
      );

      if (!response.data['success']) {
        throw Exception(response.data['message'] ?? 'Erreur lors de la mise à jour');
      }
    } catch (e) {
      throw Exception('Erreur lors de la mise à jour des préférences: $e');
    }
  }

  Future<StudentPreference> addPreference({
    required int disciplineId,
    int? courseTypeId,
    bool isPreferred = true,
    int priorityLevel = 1,
  }) async {
    try {
      final token = await _authService.getToken();
      final response = await _dio.post(
        '/api/student/preferences/advanced',
        data: {
          'discipline_id': disciplineId,
          'course_type_id': courseTypeId,
          'is_preferred': isPreferred,
          'priority_level': priorityLevel,
        },
        options: Options(
          headers: {'Authorization': 'Bearer $token'},
        ),
      );

      if (response.data['success']) {
        return StudentPreference.fromJson(response.data['data']);
      }
      throw Exception(response.data['message'] ?? 'Erreur lors de l\'ajout');
    } catch (e) {
      throw Exception('Erreur lors de l\'ajout de la préférence: $e');
    }
  }

  Future<void> removePreference({
    required int disciplineId,
    int? courseTypeId,
  }) async {
    try {
      final token = await _authService.getToken();
      final response = await _dio.delete(
        '/api/student/preferences/advanced',
        data: {
          'discipline_id': disciplineId,
          'course_type_id': courseTypeId,
        },
        options: Options(
          headers: {'Authorization': 'Bearer $token'},
        ),
      );

      if (!response.data['success']) {
        throw Exception(response.data['message'] ?? 'Erreur lors de la suppression');
      }
    } catch (e) {
      throw Exception('Erreur lors de la suppression de la préférence: $e');
    }
  }

  Future<List<CourseType>> getCourseTypesByDiscipline(int disciplineId) async {
    try {
      final token = await _authService.getToken();
      final response = await _dio.get(
        '/api/student/disciplines/$disciplineId/course-types',
        options: Options(
          headers: {'Authorization': 'Bearer $token'},
        ),
      );

      if (response.data['success']) {
        return (response.data['data'] as List)
            .map((json) => CourseType.fromJson(json))
            .toList();
      }
      throw Exception('Erreur lors du chargement des types de cours');
    } catch (e) {
      throw Exception('Erreur lors du chargement des types de cours: $e');
    }
  }
}
