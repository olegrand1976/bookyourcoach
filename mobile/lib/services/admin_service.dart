import 'api_client.dart';

class AdminService {
  final ApiClient _client;
  AdminService(this._client);

  Future<List<Map<String, dynamic>>> listUsers({String? query}) async {
    final res = await _client.dio.get('/admin/users', queryParameters: {'q': query});
    final list = (res.data as List?) ?? [];
    return list.map((e) => (e as Map).cast<String, dynamic>()).toList();
  }

  Future<void> setUserActive(String userId, bool active) async {
    await _client.dio.put('/admin/users/$userId', data: {'active': active});
  }

  Future<List<Map<String, dynamic>>> listTeacherApprovals() async {
    final res = await _client.dio.get('/admin/teacher-approvals');
    final list = (res.data as List?) ?? [];
    return list.map((e) => (e as Map).cast<String, dynamic>()).toList();
  }

  Future<void> approveTeacher(String requestId, bool approved) async {
    await _client.dio.post('/admin/teacher-approvals/$requestId', data: {'approved': approved});
  }

  Future<List<Map<String, dynamic>>> listLessonsModeration() async {
    final res = await _client.dio.get('/admin/lessons');
    final list = (res.data as List?) ?? [];
    return list.map((e) => (e as Map).cast<String, dynamic>()).toList();
  }

  Future<void> deleteLesson(String lessonId) async {
    await _client.dio.delete('/admin/lessons/$lessonId');
  }

  Future<List<Map<String, dynamic>>> listDisciplines() async {
    final res = await _client.dio.get('/admin/disciplines');
    final list = (res.data as List?) ?? [];
    return list.map((e) => (e as Map).cast<String, dynamic>()).toList();
  }

  Future<void> createDiscipline(String name) async {
    await _client.dio.post('/admin/disciplines', data: {'name': name});
  }

  Future<void> updateDiscipline(String id, String name) async {
    await _client.dio.put('/admin/disciplines/$id', data: {'name': name});
  }

  Future<void> deleteDiscipline(String id) async {
    await _client.dio.delete('/admin/disciplines/$id');
  }
}

