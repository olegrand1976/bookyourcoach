import 'api_client.dart';
import '../models/lesson.dart';

class LessonService {
  final ApiClient _client;
  LessonService(this._client);

  Future<List<Lesson>> listTeacherLessons(String teacherId) async {
    final res = await _client.dio.get('/teachers/$teacherId/lessons');
    final list = (res.data as List?) ?? [];
    return list.map((e) => Lesson.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<void> createLesson(String teacherId, Map<String, dynamic> payload) async {
    await _client.dio.post('/teachers/$teacherId/lessons', data: payload);
  }

  Future<List<Lesson>> listAvailableLessons() async {
    final res = await _client.dio.get('/lessons/available');
    final list = (res.data as List?) ?? [];
    return list.map((e) => Lesson.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<void> updateLesson(String teacherId, String lessonId, Map<String, dynamic> payload) async {
    await _client.dio.put('/teachers/$teacherId/lessons/$lessonId', data: payload);
  }

  Future<void> deleteLesson(String teacherId, String lessonId) async {
    await _client.dio.delete('/teachers/$teacherId/lessons/$lessonId');
  }

  Future<List<Booking>> listStudentBookings(String studentId) async {
    final res = await _client.dio.get('/students/$studentId/bookings');
    final list = (res.data as List?) ?? [];
    return list.map((e) => Booking.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<void> bookLesson(String studentId, String lessonId) async {
    await _client.dio.post('/students/$studentId/bookings', data: { 'lesson_id': lessonId });
  }
}