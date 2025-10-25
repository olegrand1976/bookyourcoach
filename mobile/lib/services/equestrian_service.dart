import 'package:dio/dio.dart';
import '../config/api_config.dart';
import '../models/equestrian_models.dart';

class EquestrianService {
  final Dio _dio;
  
  EquestrianService() : _dio = Dio(BaseOptions(
    baseUrl: ApiConfig.baseUrl,
    connectTimeout: const Duration(seconds: 30),
    receiveTimeout: const Duration(seconds: 30),
  ));

  // Gestion des disciplines
  Future<List<EquestrianDiscipline>> getDisciplines() async {
    try {
      final response = await _dio.get('/api/equestrian/disciplines');
      return (response.data['data'] as List)
          .map((json) => EquestrianDiscipline.fromJson(json))
          .toList();
    } catch (e) {
      throw Exception('Erreur lors du chargement des disciplines: $e');
    }
  }

  Future<EquestrianDiscipline> getDiscipline(int id) async {
    try {
      final response = await _dio.get('/api/equestrian/disciplines/$id');
      return EquestrianDiscipline.fromJson(response.data['data']);
    } catch (e) {
      throw Exception('Erreur lors du chargement de la discipline: $e');
    }
  }

  // Gestion des disciplines des étudiants
  Future<List<StudentDiscipline>> getStudentDisciplines(int studentId) async {
    try {
      final response = await _dio.get('/api/equestrian/students/$studentId/disciplines');
      return (response.data['data'] as List)
          .map((json) => StudentDiscipline.fromJson(json))
          .toList();
    } catch (e) {
      throw Exception('Erreur lors du chargement des disciplines de l\'étudiant: $e');
    }
  }

  Future<StudentDiscipline> addStudentDiscipline(int studentId, Map<String, dynamic> data) async {
    try {
      final response = await _dio.post('/api/equestrian/students/$studentId/disciplines', data: data);
      return StudentDiscipline.fromJson(response.data['data']);
    } catch (e) {
      throw Exception('Erreur lors de l\'ajout de la discipline: $e');
    }
  }

  Future<StudentDiscipline> updateStudentDiscipline(int studentId, int disciplineId, Map<String, dynamic> data) async {
    try {
      final response = await _dio.put('/api/equestrian/students/$studentId/disciplines/$disciplineId', data: data);
      return StudentDiscipline.fromJson(response.data['data']);
    } catch (e) {
      throw Exception('Erreur lors de la mise à jour de la discipline: $e');
    }
  }

  Future<void> removeStudentDiscipline(int studentId, int disciplineId) async {
    try {
      await _dio.delete('/api/equestrian/students/$studentId/disciplines/$disciplineId');
    } catch (e) {
      throw Exception('Erreur lors de la suppression de la discipline: $e');
    }
  }

  // Métriques de performance
  Future<List<PerformanceMetrics>> getPerformanceMetrics({
    int? studentId,
    int? disciplineId,
    DateTime? startDate,
    DateTime? endDate,
    int? limit,
    int? offset,
  }) async {
    try {
      final queryParams = <String, dynamic>{};
      if (studentId != null) queryParams['student_id'] = studentId;
      if (disciplineId != null) queryParams['discipline_id'] = disciplineId;
      if (startDate != null) queryParams['start_date'] = startDate.toIso8601String();
      if (endDate != null) queryParams['end_date'] = endDate.toIso8601String();
      if (limit != null) queryParams['limit'] = limit;
      if (offset != null) queryParams['offset'] = offset;

      final response = await _dio.get('/api/equestrian/performance-metrics', queryParameters: queryParams);
      return (response.data['data'] as List)
          .map((json) => PerformanceMetrics.fromJson(json))
          .toList();
    } catch (e) {
      throw Exception('Erreur lors du chargement des métriques: $e');
    }
  }

  Future<PerformanceMetrics> addPerformanceMetrics(Map<String, dynamic> data) async {
    try {
      final response = await _dio.post('/api/equestrian/performance-metrics', data: data);
      return PerformanceMetrics.fromJson(response.data['data']);
    } catch (e) {
      throw Exception('Erreur lors de l\'ajout des métriques: $e');
    }
  }

  Future<PerformanceMetrics> updatePerformanceMetrics(int id, Map<String, dynamic> data) async {
    try {
      final response = await _dio.put('/api/equestrian/performance-metrics/$id', data: data);
      return PerformanceMetrics.fromJson(response.data['data']);
    } catch (e) {
      throw Exception('Erreur lors de la mise à jour des métriques: $e');
    }
  }

  // Tableaux de bord
  Future<DashboardStats> getDashboardStats({
    required int userId,
    required String userType, // 'student' ou 'teacher'
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'user_type': userType,
      };
      if (startDate != null) queryParams['start_date'] = startDate.toIso8601String();
      if (endDate != null) queryParams['end_date'] = endDate.toIso8601String();

      final response = await _dio.get('/api/equestrian/dashboard/$userId', queryParameters: queryParams);
      return DashboardStats.fromJson(response.data['data']);
    } catch (e) {
      throw Exception('Erreur lors du chargement des statistiques: $e');
    }
  }

  // Objectifs d'entraînement
  Future<List<TrainingGoal>> getTrainingGoals({
    int? studentId,
    int? disciplineId,
    String? status,
  }) async {
    try {
      final queryParams = <String, dynamic>{};
      if (studentId != null) queryParams['student_id'] = studentId;
      if (disciplineId != null) queryParams['discipline_id'] = disciplineId;
      if (status != null) queryParams['status'] = status;

      final response = await _dio.get('/api/equestrian/training-goals', queryParameters: queryParams);
      return (response.data['data'] as List)
          .map((json) => TrainingGoal.fromJson(json))
          .toList();
    } catch (e) {
      throw Exception('Erreur lors du chargement des objectifs: $e');
    }
  }

  Future<TrainingGoal> addTrainingGoal(Map<String, dynamic> data) async {
    try {
      final response = await _dio.post('/api/equestrian/training-goals', data: data);
      return TrainingGoal.fromJson(response.data['data']);
    } catch (e) {
      throw Exception('Erreur lors de l\'ajout de l\'objectif: $e');
    }
  }

  Future<TrainingGoal> updateTrainingGoal(int id, Map<String, dynamic> data) async {
    try {
      final response = await _dio.put('/api/equestrian/training-goals/$id', data: data);
      return TrainingGoal.fromJson(response.data['data']);
    } catch (e) {
      throw Exception('Erreur lors de la mise à jour de l\'objectif: $e');
    }
  }

  Future<void> deleteTrainingGoal(int id) async {
    try {
      await _dio.delete('/api/equestrian/training-goals/$id');
    } catch (e) {
      throw Exception('Erreur lors de la suppression de l\'objectif: $e');
    }
  }

  // Intégrations externes
  Future<Map<String, dynamic>> syncEquilabData(int studentId, String apiKey) async {
    try {
      final response = await _dio.post('/api/equestrian/integrations/equilab/sync', data: {
        'student_id': studentId,
        'api_key': apiKey,
      });
      return response.data['data'];
    } catch (e) {
      throw Exception('Erreur lors de la synchronisation Equilab: $e');
    }
  }

  Future<Map<String, dynamic>> syncStrideraData(int studentId, String apiKey) async {
    try {
      final response = await _dio.post('/api/equestrian/integrations/stridera/sync', data: {
        'student_id': studentId,
        'api_key': apiKey,
      });
      return response.data['data'];
    } catch (e) {
      throw Exception('Erreur lors de la synchronisation Stridera: $e');
    }
  }

  // Export des données
  Future<String> exportData({
    required int userId,
    required String userType,
    required String format, // 'excel', 'pdf', 'csv'
    DateTime? startDate,
    DateTime? endDate,
    List<String>? disciplines,
  }) async {
    try {
      final data = {
        'user_type': userType,
        'format': format,
      };
      if (startDate != null) data['start_date'] = startDate.toIso8601String();
      if (endDate != null) data['end_date'] = endDate.toIso8601String();
      if (disciplines != null) data['disciplines'] = disciplines;

      final response = await _dio.post('/api/equestrian/export/$userId', data: data);
      return response.data['data']['download_url'];
    } catch (e) {
      throw Exception('Erreur lors de l\'export des données: $e');
    }
  }
}
