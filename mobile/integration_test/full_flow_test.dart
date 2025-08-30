import 'dart:convert';
import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:bookyourcoach_mobile/services/api_client.dart';
import 'package:bookyourcoach_mobile/main.dart' as app;

class InMemoryMockAdapter implements HttpClientAdapter {
  String token = 't123';
  Map<String, dynamic> me = {
    'id': '1',
    'email': 'test@example.com',
    'name': 'Test User',
    'is_student': false,
    'is_teacher': false,
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
      if (method == 'POST' && path == '/auth/register') {
        me['email'] = jsonBody['email'] ?? me['email'];
        me['name'] = jsonBody['name'] ?? me['name'];
        result = {'token': token};
      } else if (method == 'POST' && path == '/auth/login') {
        result = {'token': token};
      } else if (method == 'GET' && path == '/auth/me') {
        result = {
          'id': me['id'],
          'email': me['email'],
          'name': me['name'],
          'is_student': me['is_student'],
          'is_teacher': me['is_teacher'],
        };
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
        // increment booked_count
        final idx = lessons.indexWhere((l) => l['id'] == booking['lesson_id']);
        if (idx >= 0) {
          lessons[idx] = Map<String, dynamic>.from(lessons[idx])..update('booked_count', (v) => (v as int) + 1, ifAbsent: () => 1);
        }
        result = booking;
      } else if (method == 'DELETE' && RegExp(r'^/students/[^/]+/bookings/[^/]+$').hasMatch(path)) {
        final bookingId = path.split('/').last;
        final idx = bookings.indexWhere((b) => b['id'] == bookingId);
        if (idx >= 0) {
          final lessonId = bookings[idx]['lesson_id'];
          bookings.removeAt(idx);
          final lidx = lessons.indexWhere((l) => l['id'] == lessonId);
          if (lidx >= 0) {
            lessons[lidx] = Map<String, dynamic>.from(lessons[lidx])..update('booked_count', (v) => (v as int) - 1, ifAbsent: () => 0);
          }
        }
        result = {'ok': true};
      } else if (method == 'GET' && RegExp(r'^/teachers/[^/]+/lessons/[^/]+/attendees$').hasMatch(path)) {
        final lessonId = path.split('/')[4];
        final attendees = bookings.where((b) => b['lesson_id'] == lessonId).map((b) => {
          'name': me['name'],
          'email': me['email'],
        }).toList();
        result = attendees;
      } else if (method == 'GET' && RegExp(r'^/students/[^/]+$').hasMatch(path)) {
        result = {'user_id': me['id'], 'level': 'beginner'};
      } else if (method == 'GET' && RegExp(r'^/teachers/[^/]+$').hasMatch(path)) {
        result = {'user_id': me['id'], 'bio': 'Coach'};
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

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('Full flow with mocked API: register, login, select roles, book, cancel', (tester) async {
    SharedPreferences.setMockInitialValues({});
    final mock = InMemoryMockAdapter();
    ApiFactory.setClientOverride(ApiClient(adapter: mock));

    app.main();
    await tester.pumpAndSettle();

    // Login screen -> go to Register
    expect(find.text("S'inscrire"), findsOneWidget);
    await tester.tap(find.text("S'inscrire"));
    await tester.pumpAndSettle();

    // Fill register
    await tester.enterText(find.byType(TextFormField).at(0), 'Test User');
    await tester.enterText(find.byType(TextFormField).at(1), 'test@example.com');
    await tester.enterText(find.byType(TextFormField).at(2), 'Password1');
    await tester.tap(find.text("S'inscrire"));
    await tester.pumpAndSettle();

    // Back on Login
    await tester.enterText(find.byType(TextFormField).at(0), 'test@example.com');
    await tester.enterText(find.byType(TextFormField).at(1), 'Password1');
    await tester.tap(find.text('Se connecter'));
    await tester.pumpAndSettle();

    // Role selection -> choose student + teacher
    expect(find.text('Sélection du rôle'), findsOneWidget);
    await tester.tap(find.text('Élève'));
    await tester.tap(find.text('Enseignant'));
    await tester.tap(find.text('Continuer'));
    await tester.pumpAndSettle();

    // Home should appear
    expect(find.textContaining('Bienvenue'), findsOneWidget);

    // Student bookings -> Explorer available lessons
    await tester.tap(find.text('Mes Réservations'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Explorer'));
    await tester.pumpAndSettle();

    // Book first available lesson
    await tester.tap(find.text('Réserver').first);
    await tester.pumpAndSettle();

    // Go back to bookings and see booking
    await tester.pageBack();
    await tester.pumpAndSettle();
    expect(find.textContaining('Leçon'), findsWidgets);

    // Cancel the booking
    await tester.tap(find.text('Annuler'));
    await tester.pumpAndSettle();
    // List might be empty now
  });
}


