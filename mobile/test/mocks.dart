import 'dart:convert';
import 'dart:typed_data';
import 'package:dio/dio.dart';

class TestMockAdapter implements HttpClientAdapter {
  String token = 't-mock';
  Map<String, dynamic> me = {
    'id': '1',
    'email': 'test@example.com',
    'name': 'Test User',
    'is_student': true,
    'is_teacher': true,
    'is_admin': true,
  };
  List<Map<String, dynamic>> lessons = [
    {
      'id': 'L1',
      'teacher_id': '1',
      'start': DateTime.now().add(const Duration(days: 1)).toIso8601String(),
      'duration_minutes': 60,
      'discipline': 'Dressage',
      'location': 'Manège A',
      'capacity': 4,
      'booked_count': 0,
    }
  ];
  List<Map<String, dynamic>> bookings = [];
  List<Map<String, dynamic>> disciplines = [];
  List<Map<String, dynamic>> users = [];
  List<Map<String, dynamic>> approvals = [];

  @override
  void close({bool force = false}) {}

  @override
  Future<ResponseBody> fetch(RequestOptions options, Stream<List<int>>? requestStream, Future? cancelFuture) async {
    final path = options.path;
    final method = options.method.toUpperCase();
    dynamic body = options.data;
    if (body is FormData) {
      body = {for (final f in body.fields) f.key: f.value};
    }
    Map<String, dynamic> jsonBody = {};
    if (body is Map) {
      jsonBody = (body as Map).cast<String, dynamic>();
    } else if (body is String && body.isNotEmpty) {
      try { jsonBody = json.decode(body) as Map<String, dynamic>; } catch (_) {}
    }

    dynamic result;
    int status = 200;

    try {
      if (method == 'POST' && path == '/auth/login') {
        result = {'token': token};
      } else if (method == 'GET' && path == '/auth/me') {
        result = me;
      } else if (method == 'POST' && path == '/profiles/init-roles') {
        me['is_student'] = jsonBody['student'] == true;
        me['is_teacher'] = jsonBody['teacher'] == true;
        result = {'ok': true};
      } else if (method == 'GET' && RegExp(r'^/teachers/[^/]+/lessons$').hasMatch(path)) {
        result = lessons;
      } else if (method == 'POST' && RegExp(r'^/teachers/[^/]+/lessons$').hasMatch(path)) {
        final newLesson = {
          'id': 'L${lessons.length + 1}',
          'teacher_id': me['id'],
          'start': jsonBody['start'] ?? DateTime.now().add(const Duration(days: 2)).toIso8601String(),
          'duration_minutes': jsonBody['duration_minutes'] ?? 60,
          'discipline': jsonBody['discipline'] ?? 'Dressage',
          'location': jsonBody['location'] ?? 'Manège B',
          'capacity': jsonBody['capacity'] ?? 1,
          'booked_count': 0,
        };
        lessons.add(newLesson);
        result = newLesson;
      } else if (method == 'GET' && path == '/lessons/available') {
        result = lessons;
      } else if (method == 'GET' && RegExp(r'^/students/[^/]+/bookings$').hasMatch(path)) {
        result = bookings;
      } else if (method == 'POST' && RegExp(r'^/students/[^/]+/bookings$').hasMatch(path)) {
        final booking = {
          'id': 'B${bookings.length + 1}',
          'lesson_id': jsonBody['lesson_id'] ?? lessons.first['id'],
          'student_id': me['id'],
          'status': 'confirmed',
        };
        bookings.add(booking);
        result = booking;
      } else if (method == 'DELETE' && RegExp(r'^/students/[^/]+/bookings/[^/]+$').hasMatch(path)) {
        final bookingId = path.split('/').last;
        bookings.removeWhere((b) => b['id'] == bookingId);
        result = {'ok': true};
      } else if (method == 'GET' && path == '/admin/disciplines') {
        result = disciplines;
      } else if (method == 'POST' && path == '/admin/disciplines') {
        final item = {'id': 'D${disciplines.length + 1}', 'name': jsonBody['name'] ?? ''};
        disciplines.add(item);
        result = item;
      } else if (method == 'PUT' && RegExp(r'^/admin/disciplines/[^/]+$').hasMatch(path)) {
        final id = path.split('/').last;
        final idx = disciplines.indexWhere((d) => d['id'] == id);
        if (idx >= 0) disciplines[idx]['name'] = jsonBody['name'] ?? disciplines[idx]['name'];
        result = {'ok': true};
      } else if (method == 'DELETE' && RegExp(r'^/admin/disciplines/[^/]+$').hasMatch(path)) {
        final id = path.split('/').last;
        disciplines.removeWhere((d) => d['id'] == id);
        result = {'ok': true};
      } else if (method == 'GET' && path == '/admin/users') {
        result = users;
      } else if (method == 'PUT' && RegExp(r'^/admin/users/[^/]+$').hasMatch(path)) {
        final id = path.split('/').last;
        final idx = users.indexWhere((u) => u['id'].toString() == id);
        if (idx >= 0) users[idx]['active'] = jsonBody['active'] == true;
        result = {'ok': true};
      } else if (method == 'GET' && path == '/admin/teacher-approvals') {
        result = approvals;
      } else if (method == 'POST' && RegExp(r'^/admin/teacher-approvals/[^/]+$').hasMatch(path)) {
        result = {'ok': true};
      } else {
        status = 404;
        result = {'error': 'not_found'};
      }
    } catch (e) {
      status = 500;
      result = {'error': e.toString()};
    }

    final data = utf8.encode(json.encode(result));
    return ResponseBody.fromBytes(Uint8List.fromList(data), status, headers: {
      Headers.contentTypeHeader: ['application/json']
    });
  }
}

