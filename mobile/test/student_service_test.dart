import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:mockito/annotations.dart';
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:bookyourcoach_mobile/services/student_service.dart';
import 'package:bookyourcoach_mobile/models/lesson.dart';
import 'package:bookyourcoach_mobile/models/booking.dart';
import 'package:bookyourcoach_mobile/models/user.dart';

import 'student_service_test.mocks.dart';

@GenerateMocks([Dio, FlutterSecureStorage])
void main() {
  group('StudentService', () {
    late StudentService studentService;
    late MockDio mockDio;
    late MockFlutterSecureStorage mockStorage;

    setUp(() {
      mockDio = MockDio();
      mockStorage = MockFlutterSecureStorage();
      studentService = StudentService();
    });

    group('getAvailableLessons', () {
      test('should return list of lessons when API call is successful', () async {
        // Arrange
        const token = 'test_token';
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => token);
        
        final response = Response(
          data: {
            'data': [
              {
                'id': 1,
                'title': 'Cours de Mathématiques',
                'description': 'Cours de mathématiques avancées',
                'start_time': '2024-01-15T10:00:00.000000Z',
                'end_time': '2024-01-15T11:00:00.000000Z',
                'status': 'scheduled',
                'teacher_id': 1,
                'price': 50.0,
                'created_at': '2024-01-01T00:00:00.000000Z',
                'updated_at': '2024-01-01T00:00:00.000000Z',
              }
            ]
          },
          statusCode: 200,
          requestOptions: RequestOptions(path: '/student/available-lessons'),
        );
        
        when(mockDio.get(
          '/student/available-lessons',
          options: anyNamed('options'),
          queryParameters: anyNamed('queryParameters'),
        )).thenAnswer((_) async => response);

        // Act
        final result = await studentService.getAvailableLessons();

        // Assert
        expect(result, isA<List<Lesson>>());
        expect(result.length, 1);
        expect(result.first.title, 'Cours de Mathématiques');
        expect(result.first.price, 50.0);
      });

      test('should throw exception when API call fails', () async {
        // Arrange
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => 'test_token');
        when(mockDio.get(
          '/student/available-lessons',
          options: anyNamed('options'),
          queryParameters: anyNamed('queryParameters'),
        )).thenThrow(DioException(
          requestOptions: RequestOptions(path: '/student/available-lessons'),
          response: Response(
            statusCode: 500,
            requestOptions: RequestOptions(path: '/student/available-lessons'),
          ),
        ));

        // Act & Assert
        expect(
          () => studentService.getAvailableLessons(),
          throwsA(isA<Exception>()),
        );
      });
    });

    group('getStudentBookings', () {
      test('should return list of bookings when API call is successful', () async {
        // Arrange
        const token = 'test_token';
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => token);
        
        final response = Response(
          data: {
            'data': [
              {
                'id': 1,
                'student_id': 1,
                'lesson_id': 1,
                'status': 'confirmed',
                'booked_at': '2024-01-10T09:00:00.000000Z',
                'created_at': '2024-01-01T00:00:00.000000Z',
                'updated_at': '2024-01-01T00:00:00.000000Z',
                'lesson': {
                  'id': 1,
                  'title': 'Cours de Mathématiques',
                  'description': 'Cours de mathématiques avancées',
                  'start_time': '2024-01-15T10:00:00.000000Z',
                  'end_time': '2024-01-15T11:00:00.000000Z',
                  'status': 'scheduled',
                  'teacher_id': 1,
                  'price': 50.0,
                  'created_at': '2024-01-01T00:00:00.000000Z',
                  'updated_at': '2024-01-01T00:00:00.000000Z',
                }
              }
            ]
          },
          statusCode: 200,
          requestOptions: RequestOptions(path: '/student/bookings'),
        );
        
        when(mockDio.get(
          '/student/bookings',
          options: anyNamed('options'),
          queryParameters: anyNamed('queryParameters'),
        )).thenAnswer((_) async => response);

        // Act
        final result = await studentService.getStudentBookings();

        // Assert
        expect(result, isA<List<Booking>>());
        expect(result.length, 1);
        expect(result.first.status, 'confirmed');
        expect(result.first.lesson?.title, 'Cours de Mathématiques');
      });
    });

    group('bookLesson', () {
      test('should return booking when lesson booking is successful', () async {
        // Arrange
        const token = 'test_token';
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => token);
        
        final response = Response(
          data: {
            'data': {
              'id': 1,
              'student_id': 1,
              'lesson_id': 1,
              'status': 'pending',
              'booked_at': '2024-01-10T09:00:00.000000Z',
              'created_at': '2024-01-01T00:00:00.000000Z',
              'updated_at': '2024-01-01T00:00:00.000000Z',
            }
          },
          statusCode: 201,
          requestOptions: RequestOptions(path: '/student/bookings'),
        );
        
        when(mockDio.post(
          '/student/bookings',
          options: anyNamed('options'),
          data: anyNamed('data'),
        )).thenAnswer((_) async => response);

        // Act
        final result = await studentService.bookLesson(lessonId: 1, notes: 'Test notes');

        // Assert
        expect(result, isA<Booking>());
        expect(result.status, 'pending');
        expect(result.lessonId, 1);
      });
    });

    group('cancelBooking', () {
      test('should return true when booking cancellation is successful', () async {
        // Arrange
        const token = 'test_token';
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => token);
        
        final response = Response(
          statusCode: 200,
          requestOptions: RequestOptions(path: '/student/bookings/1/cancel'),
        );
        
        when(mockDio.put(
          '/student/bookings/1/cancel',
          options: anyNamed('options'),
        )).thenAnswer((_) async => response);

        // Act
        final result = await studentService.cancelBooking(1);

        // Assert
        expect(result, true);
      });
    });

    group('getAvailableTeachers', () {
      test('should return list of teachers when API call is successful', () async {
        // Arrange
        const token = 'test_token';
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => token);
        
        final response = Response(
          data: {
            'data': [
              {
                'id': 1,
                'name': 'Sophie Martin',
                'email': 'sophie.martin@bookyourcoach.com',
                'roles': ['teacher'],
                'created_at': '2024-01-01T00:00:00.000000Z',
                'updated_at': '2024-01-01T00:00:00.000000Z',
              }
            ]
          },
          statusCode: 200,
          requestOptions: RequestOptions(path: '/student/available-teachers'),
        );
        
        when(mockDio.get(
          '/student/available-teachers',
          options: anyNamed('options'),
          queryParameters: anyNamed('queryParameters'),
        )).thenAnswer((_) async => response);

        // Act
        final result = await studentService.getAvailableTeachers();

        // Assert
        expect(result, isA<List<User>>());
        expect(result.length, 1);
        expect(result.first.name, 'Sophie Martin');
        expect(result.first.isTeacher, true);
      });
    });

    group('getStudentStats', () {
      test('should return stats when API call is successful', () async {
        // Arrange
        const token = 'test_token';
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => token);
        
        final response = Response(
          data: {
            'data': {
              'lessons_taken': 5,
              'active_bookings': 2,
              'hours_learned': 10,
              'teachers_count': 3,
            }
          },
          statusCode: 200,
          requestOptions: RequestOptions(path: '/student/stats'),
        );
        
        when(mockDio.get(
          '/student/stats',
          options: anyNamed('options'),
        )).thenAnswer((_) async => response);

        // Act
        final result = await studentService.getStudentStats();

        // Assert
        expect(result, isA<Map<String, dynamic>>());
        expect(result['lessons_taken'], 5);
        expect(result['active_bookings'], 2);
        expect(result['hours_learned'], 10);
        expect(result['teachers_count'], 3);
      });
    });

    group('searchLessons', () {
      test('should return filtered lessons when search is successful', () async {
        // Arrange
        const token = 'test_token';
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => token);
        
        final response = Response(
          data: {
            'data': [
              {
                'id': 1,
                'title': 'Cours de Mathématiques',
                'description': 'Cours de mathématiques avancées',
                'start_time': '2024-01-15T10:00:00.000000Z',
                'end_time': '2024-01-15T11:00:00.000000Z',
                'status': 'scheduled',
                'teacher_id': 1,
                'price': 50.0,
                'created_at': '2024-01-01T00:00:00.000000Z',
                'updated_at': '2024-01-01T00:00:00.000000Z',
              }
            ]
          },
          statusCode: 200,
          requestOptions: RequestOptions(path: '/student/search-lessons'),
        );
        
        when(mockDio.get(
          '/student/search-lessons',
          options: anyNamed('options'),
          queryParameters: anyNamed('queryParameters'),
        )).thenAnswer((_) async => response);

        // Act
        final result = await studentService.searchLessons(
          query: 'mathématiques',
          subject: 'math',
          maxPrice: 100.0,
        );

        // Assert
        expect(result, isA<List<Lesson>>());
        expect(result.length, 1);
        expect(result.first.title, 'Cours de Mathématiques');
      });
    });

    group('getLessonHistory', () {
      test('should return lesson history when API call is successful', () async {
        // Arrange
        const token = 'test_token';
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => token);
        
        final response = Response(
          data: {
            'data': [
              {
                'id': 1,
                'student_id': 1,
                'lesson_id': 1,
                'status': 'completed',
                'booked_at': '2024-01-10T09:00:00.000000Z',
                'created_at': '2024-01-01T00:00:00.000000Z',
                'updated_at': '2024-01-01T00:00:00.000000Z',
                'lesson': {
                  'id': 1,
                  'title': 'Cours de Mathématiques',
                  'description': 'Cours de mathématiques avancées',
                  'start_time': '2024-01-15T10:00:00.000000Z',
                  'end_time': '2024-01-15T11:00:00.000000Z',
                  'status': 'completed',
                  'teacher_id': 1,
                  'price': 50.0,
                  'created_at': '2024-01-01T00:00:00.000000Z',
                  'updated_at': '2024-01-01T00:00:00.000000Z',
                }
              }
            ]
          },
          statusCode: 200,
          requestOptions: RequestOptions(path: '/student/lesson-history'),
        );
        
        when(mockDio.get(
          '/student/lesson-history',
          options: anyNamed('options'),
        )).thenAnswer((_) async => response);

        // Act
        final result = await studentService.getLessonHistory();

        // Assert
        expect(result, isA<List<Booking>>());
        expect(result.length, 1);
        expect(result.first.status, 'completed');
      });
    });

    group('rateLesson', () {
      test('should return true when lesson rating is successful', () async {
        // Arrange
        const token = 'test_token';
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => token);
        
        final response = Response(
          statusCode: 200,
          requestOptions: RequestOptions(path: '/student/bookings/1/rate'),
        );
        
        when(mockDio.post(
          '/student/bookings/1/rate',
          options: anyNamed('options'),
          data: anyNamed('data'),
        )).thenAnswer((_) async => response);

        // Act
        final result = await studentService.rateLesson(
          bookingId: 1,
          rating: 5,
          review: 'Excellent cours !',
        );

        // Assert
        expect(result, true);
      });
    });

    group('getFavoriteTeachers', () {
      test('should return favorite teachers when API call is successful', () async {
        // Arrange
        const token = 'test_token';
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => token);
        
        final response = Response(
          data: {
            'data': [
              {
                'id': 1,
                'name': 'Sophie Martin',
                'email': 'sophie.martin@bookyourcoach.com',
                'roles': ['teacher'],
                'created_at': '2024-01-01T00:00:00.000000Z',
                'updated_at': '2024-01-01T00:00:00.000000Z',
              }
            ]
          },
          statusCode: 200,
          requestOptions: RequestOptions(path: '/student/favorite-teachers'),
        );
        
        when(mockDio.get(
          '/student/favorite-teachers',
          options: anyNamed('options'),
        )).thenAnswer((_) async => response);

        // Act
        final result = await studentService.getFavoriteTeachers();

        // Assert
        expect(result, isA<List<User>>());
        expect(result.length, 1);
        expect(result.first.name, 'Sophie Martin');
      });
    });

    group('toggleFavoriteTeacher', () {
      test('should return true when toggling favorite teacher is successful', () async {
        // Arrange
        const token = 'test_token';
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => token);
        
        final response = Response(
          statusCode: 200,
          requestOptions: RequestOptions(path: '/student/favorite-teachers/1/toggle'),
        );
        
        when(mockDio.post(
          '/student/favorite-teachers/1/toggle',
          options: anyNamed('options'),
        )).thenAnswer((_) async => response);

        // Act
        final result = await studentService.toggleFavoriteTeacher(1);

        // Assert
        expect(result, true);
      });
    });

    group('Error handling', () {
      test('should handle 401 unauthorized error', () async {
        // Arrange
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => 'test_token');
        when(mockDio.get(
          '/student/available-lessons',
          options: anyNamed('options'),
          queryParameters: anyNamed('queryParameters'),
        )).thenThrow(DioException(
          requestOptions: RequestOptions(path: '/student/available-lessons'),
          response: Response(
            statusCode: 401,
            requestOptions: RequestOptions(path: '/student/available-lessons'),
          ),
        ));

        // Act & Assert
        expect(
          () => studentService.getAvailableLessons(),
          throwsA(isA<Exception>()),
        );
      });

      test('should handle 422 validation error', () async {
        // Arrange
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => 'test_token');
        when(mockDio.post(
          '/student/bookings',
          options: anyNamed('options'),
          data: anyNamed('data'),
        )).thenThrow(DioException(
          requestOptions: RequestOptions(path: '/student/bookings'),
          response: Response(
            statusCode: 422,
            data: {
              'errors': {
                'lesson_id': ['Le cours n\'est pas disponible']
              }
            },
            requestOptions: RequestOptions(path: '/student/bookings'),
          ),
        ));

        // Act & Assert
        expect(
          () => studentService.bookLesson(lessonId: 1),
          throwsA(isA<Exception>()),
        );
      });

      test('should handle network timeout error', () async {
        // Arrange
        when(mockStorage.read(key: 'auth_token')).thenAnswer((_) async => 'test_token');
        when(mockDio.get(
          '/student/available-lessons',
          options: anyNamed('options'),
          queryParameters: anyNamed('queryParameters'),
        )).thenThrow(DioException(
          type: DioExceptionType.connectionTimeout,
          requestOptions: RequestOptions(path: '/student/available-lessons'),
        ));

        // Act & Assert
        expect(
          () => studentService.getAvailableLessons(),
          throwsA(isA<Exception>()),
        );
      });
    });
  });
}

