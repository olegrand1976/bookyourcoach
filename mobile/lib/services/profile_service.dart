import 'api_client.dart';
import '../models/user.dart';

class ProfileService {
  final ApiClient _client;

  ProfileService(this._client);

  Future<UserProfile> fetchMe() async {
    final res = await _client.dio.get('/auth/me');
    return UserProfile.fromJson(res.data);
  }

  Future<StudentProfile> fetchStudent(String userId) async {
    final res = await _client.dio.get('/students/$userId');
    return StudentProfile.fromJson(res.data);
  }

  Future<TeacherProfile> fetchTeacher(String userId) async {
    final res = await _client.dio.get('/teachers/$userId');
    return TeacherProfile.fromJson(res.data);
  }

  Future<void> updateStudent(String userId, Map<String, dynamic> payload) async {
    await _client.dio.put('/students/$userId', data: payload);
  }

  Future<void> updateTeacher(String userId, Map<String, dynamic> payload) async {
    await _client.dio.put('/teachers/$userId', data: payload);
  }

  Future<void> initRoles({required bool asStudent, required bool asTeacher}) async {
    await _client.dio.post('/profiles/init-roles', data: {
      'student': asStudent,
      'teacher': asTeacher,
    });
  }
}