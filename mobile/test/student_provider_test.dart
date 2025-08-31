import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:mockito/annotations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:bookyourcoach_mobile/providers/student_provider.dart';
import 'package:bookyourcoach_mobile/services/student_service.dart';
import 'package:bookyourcoach_mobile/models/lesson.dart';
import 'package:bookyourcoach_mobile/models/booking.dart';
import 'package:bookyourcoach_mobile/models/user.dart';

import 'student_provider_test.mocks.dart';

@GenerateMocks([StudentService])
void main() {
  group('Student Providers', () {
    late ProviderContainer container;
    late MockStudentService mockStudentService;

    setUp(() {
      mockStudentService = MockStudentService();
      container = ProviderContainer(
        overrides: [
          studentServiceProvider.overrideWithValue(mockStudentService),
        ],
      );
    });

    tearDown(() {
      container.dispose();
    });

    group('AvailableLessonsProvider', () {
      test('should load available lessons successfully', () async {
        // Arrange
        final lessons = [
          Lesson(
            id: 1,
            title: 'Cours de Mathématiques',
            description: 'Cours de mathématiques avancées',
            startTime: DateTime.parse('2024-01-15T10:00:00.000000Z'),
            endTime: DateTime.parse('2024-01-15T11:00:00.000000Z'),
            status: 'scheduled',
            teacherId: 1,
            price: 50.0,
            createdAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
            updatedAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
          ),
        ];

        when(mockStudentService.getAvailableLessons(
          subject: anyNamed('subject'),
          date: anyNamed('date'),
        )).thenAnswer((_) async => lessons);

        // Act
        final notifier = container.read(availableLessonsProvider.notifier);
        await notifier.loadAvailableLessons();

        // Assert
        final state = container.read(availableLessonsProvider);
        expect(state.isLoading, false);
        expect(state.lessons, lessons);
        expect(state.error, null);
      });

      test('should handle error when loading available lessons fails', () async {
        // Arrange
        when(mockStudentService.getAvailableLessons(
          subject: anyNamed('subject'),
          date: anyNamed('date'),
        )).thenThrow(Exception('Erreur de chargement'));

        // Act
        final notifier = container.read(availableLessonsProvider.notifier);
        await notifier.loadAvailableLessons();

        // Assert
        final state = container.read(availableLessonsProvider);
        expect(state.isLoading, false);
        expect(state.lessons, isEmpty);
        expect(state.error, 'Exception: Erreur de chargement');
      });

      test('should clear error when clearError is called', () async {
        // Arrange
        when(mockStudentService.getAvailableLessons(
          subject: anyNamed('subject'),
          date: anyNamed('date'),
        )).thenThrow(Exception('Erreur de chargement'));

        final notifier = container.read(availableLessonsProvider.notifier);
        await notifier.loadAvailableLessons();

        // Act
        notifier.clearError();

        // Assert
        final state = container.read(availableLessonsProvider);
        expect(state.error, null);
      });
    });

    group('StudentBookingsProvider', () {
      test('should load bookings successfully', () async {
        // Arrange
        final bookings = [
          Booking(
            id: 1,
            studentId: 1,
            lessonId: 1,
            status: 'confirmed',
            bookedAt: DateTime.parse('2024-01-10T09:00:00.000000Z'),
            createdAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
            updatedAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
            lesson: Lesson(
              id: 1,
              title: 'Cours de Mathématiques',
              description: 'Cours de mathématiques avancées',
              startTime: DateTime.parse('2024-01-15T10:00:00.000000Z'),
              endTime: DateTime.parse('2024-01-15T11:00:00.000000Z'),
              status: 'scheduled',
              teacherId: 1,
              price: 50.0,
              createdAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
              updatedAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
            ),
          ),
        ];

        when(mockStudentService.getStudentBookings(
          status: anyNamed('status'),
        )).thenAnswer((_) async => bookings);

        // Act
        final notifier = container.read(studentBookingsProvider.notifier);
        await notifier.loadBookings();

        // Assert
        final state = container.read(studentBookingsProvider);
        expect(state.isLoading, false);
        expect(state.bookings, bookings);
        expect(state.error, null);
      });

      test('should book lesson successfully', () async {
        // Arrange
        final newBooking = Booking(
          id: 1,
          studentId: 1,
          lessonId: 1,
          status: 'pending',
          bookedAt: DateTime.parse('2024-01-10T09:00:00.000000Z'),
          createdAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
          updatedAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
        );

        when(mockStudentService.bookLesson(
          lessonId: anyNamed('lessonId'),
          notes: anyNamed('notes'),
        )).thenAnswer((_) async => newBooking);

        // Act
        final notifier = container.read(studentBookingsProvider.notifier);
        await notifier.bookLesson(lessonId: 1, notes: 'Test notes');

        // Assert
        final state = container.read(studentBookingsProvider);
        expect(state.isLoading, false);
        expect(state.bookings.length, 1);
        expect(state.bookings.first, newBooking);
        expect(state.error, null);
      });

      test('should cancel booking successfully', () async {
        // Arrange
        final existingBooking = Booking(
          id: 1,
          studentId: 1,
          lessonId: 1,
          status: 'confirmed',
          bookedAt: DateTime.parse('2024-01-10T09:00:00.000000Z'),
          createdAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
          updatedAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
        );

        when(mockStudentService.cancelBooking(1)).thenAnswer((_) async => true);

        // Setup initial state
        final notifier = container.read(studentBookingsProvider.notifier);
        container.read(studentBookingsProvider.notifier).state = StudentBookingsState(
          bookings: [existingBooking],
        );

        // Act
        await notifier.cancelBooking(1);

        // Assert
        final state = container.read(studentBookingsProvider);
        expect(state.isLoading, false);
        expect(state.bookings.length, 1);
        expect(state.bookings.first.status, 'cancelled');
        expect(state.error, null);
      });
    });

    group('AvailableTeachersProvider', () {
      test('should load available teachers successfully', () async {
        // Arrange
        final teachers = [
          User(
            id: 1,
            name: 'Sophie Martin',
            email: 'sophie.martin@bookyourcoach.com',
            roles: ['teacher'],
            createdAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
            updatedAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
          ),
        ];

        when(mockStudentService.getAvailableTeachers(
          subject: anyNamed('subject'),
        )).thenAnswer((_) async => teachers);

        // Act
        final notifier = container.read(availableTeachersProvider.notifier);
        await notifier.loadAvailableTeachers();

        // Assert
        final state = container.read(availableTeachersProvider);
        expect(state.isLoading, false);
        expect(state.teachers, teachers);
        expect(state.error, null);
      });
    });

    group('StudentStatsProvider', () {
      test('should load stats successfully', () async {
        // Arrange
        final stats = {
          'lessons_taken': 5,
          'active_bookings': 2,
          'hours_learned': 10,
          'teachers_count': 3,
        };

        when(mockStudentService.getStudentStats()).thenAnswer((_) async => stats);

        // Act
        final notifier = container.read(studentStatsProvider.notifier);
        await notifier.loadStats();

        // Assert
        final state = container.read(studentStatsProvider);
        expect(state.isLoading, false);
        expect(state.stats, stats);
        expect(state.error, null);
      });
    });

    group('FavoriteTeachersProvider', () {
      test('should load favorite teachers successfully', () async {
        // Arrange
        final teachers = [
          User(
            id: 1,
            name: 'Sophie Martin',
            email: 'sophie.martin@bookyourcoach.com',
            roles: ['teacher'],
            createdAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
            updatedAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
          ),
        ];

        when(mockStudentService.getFavoriteTeachers()).thenAnswer((_) async => teachers);

        // Act
        final notifier = container.read(favoriteTeachersProvider.notifier);
        await notifier.loadFavoriteTeachers();

        // Assert
        final state = container.read(favoriteTeachersProvider);
        expect(state.isLoading, false);
        expect(state.teachers, teachers);
        expect(state.error, null);
      });

      test('should toggle favorite teacher successfully', () async {
        // Arrange
        final teachers = [
          User(
            id: 1,
            name: 'Sophie Martin',
            email: 'sophie.martin@bookyourcoach.com',
            roles: ['teacher'],
            createdAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
            updatedAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
          ),
        ];

        when(mockStudentService.toggleFavoriteTeacher(1)).thenAnswer((_) async => true);
        when(mockStudentService.getFavoriteTeachers()).thenAnswer((_) async => teachers);

        // Act
        final notifier = container.read(favoriteTeachersProvider.notifier);
        await notifier.toggleFavoriteTeacher(1);

        // Assert
        verify(mockStudentService.toggleFavoriteTeacher(1)).called(1);
        verify(mockStudentService.getFavoriteTeachers()).called(1);
      });
    });

    group('LessonHistoryProvider', () {
      test('should load lesson history successfully', () async {
        // Arrange
        final history = [
          Booking(
            id: 1,
            studentId: 1,
            lessonId: 1,
            status: 'completed',
            bookedAt: DateTime.parse('2024-01-10T09:00:00.000000Z'),
            createdAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
            updatedAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
            lesson: Lesson(
              id: 1,
              title: 'Cours de Mathématiques',
              description: 'Cours de mathématiques avancées',
              startTime: DateTime.parse('2024-01-15T10:00:00.000000Z'),
              endTime: DateTime.parse('2024-01-15T11:00:00.000000Z'),
              status: 'completed',
              teacherId: 1,
              price: 50.0,
              createdAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
              updatedAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
            ),
          ),
        ];

        when(mockStudentService.getLessonHistory()).thenAnswer((_) async => history);

        // Act
        final notifier = container.read(lessonHistoryProvider.notifier);
        await notifier.loadHistory();

        // Assert
        final state = container.read(lessonHistoryProvider);
        expect(state.isLoading, false);
        expect(state.history, history);
        expect(state.error, null);
      });

      test('should rate lesson successfully', () async {
        // Arrange
        when(mockStudentService.rateLesson(
          bookingId: anyNamed('bookingId'),
          rating: anyNamed('rating'),
          review: anyNamed('review'),
        )).thenAnswer((_) async => true);

        when(mockStudentService.getLessonHistory()).thenAnswer((_) async => []);

        // Act
        final notifier = container.read(lessonHistoryProvider.notifier);
        await notifier.rateLesson(
          bookingId: 1,
          rating: 5,
          review: 'Excellent cours !',
        );

        // Assert
        verify(mockStudentService.rateLesson(
          bookingId: 1,
          rating: 5,
          review: 'Excellent cours !',
        )).called(1);
        verify(mockStudentService.getLessonHistory()).called(1);
      });
    });

    group('Provider State Management', () {
      test('should maintain loading state during API calls', () async {
        // Arrange
        final completer = Completer<List<Lesson>>();
        when(mockStudentService.getAvailableLessons(
          subject: anyNamed('subject'),
          date: anyNamed('date'),
        )).thenAnswer((_) => completer.future);

        // Act
        final notifier = container.read(availableLessonsProvider.notifier);
        final future = notifier.loadAvailableLessons();

        // Assert
        final state = container.read(availableLessonsProvider);
        expect(state.isLoading, true);

        // Complete the future
        completer.complete([]);
        await future;

        final finalState = container.read(availableLessonsProvider);
        expect(finalState.isLoading, false);
      });

      test('should handle multiple concurrent requests', () async {
        // Arrange
        final lessons = [
          Lesson(
            id: 1,
            title: 'Cours de Mathématiques',
            description: 'Cours de mathématiques avancées',
            startTime: DateTime.parse('2024-01-15T10:00:00.000000Z'),
            endTime: DateTime.parse('2024-01-15T11:00:00.000000Z'),
            status: 'scheduled',
            teacherId: 1,
            price: 50.0,
            createdAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
            updatedAt: DateTime.parse('2024-01-01T00:00:00.000000Z'),
          ),
        ];

        when(mockStudentService.getAvailableLessons(
          subject: anyNamed('subject'),
          date: anyNamed('date'),
        )).thenAnswer((_) async => lessons);

        // Act
        final notifier = container.read(availableLessonsProvider.notifier);
        final futures = [
          notifier.loadAvailableLessons(),
          notifier.loadAvailableLessons(),
          notifier.loadAvailableLessons(),
        ];

        await Future.wait(futures);

        // Assert
        final state = container.read(availableLessonsProvider);
        expect(state.isLoading, false);
        expect(state.lessons, lessons);
        expect(state.error, null);
      });
    });
  });
}

