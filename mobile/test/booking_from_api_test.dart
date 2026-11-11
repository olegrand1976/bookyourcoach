import 'package:flutter_test/flutter_test.dart';
import 'package:activibe_mobile/models/booking.dart';

void main() {
  test('lit un cours au format de l\'API actuelle (StudentLessonCalendarResource)', () {
    final booking = Booking.fromJson({
      'id': 42,
      'club_id': 1,
      'teacher_id': 5,
      'student_id': 7,
      'course_type_id': 3,
      'start_time': '2026-10-08T10:00:00+02:00',
      'end_time': '2026-10-08T11:00:00+02:00',
      'status': 'cancelled',
      'price': '25.00',
      'course_type': {'id': 3, 'name': 'Cours particulier'},
      'teacher': {'id': 5, 'user': {'name': 'Julie Coach'}},
      'student': {'id': 7, 'user': {'name': 'Léo'}},
      'cancellation_reason': null,
      'cancellation_count_in_subscription': 1,
      'cancellation_certificate_status': null,
    });

    expect(booking.id, 42);
    expect(booking.lessonId, 42);
    expect(booking.studentId, 7);
    expect(booking.isCancelled, isTrue);
    expect(booking.lesson, isNotNull);
    expect(booking.lesson!.title, 'Cours particulier');
    expect(booking.cancellationImpact, CancellationImpact.deducted);
  });

  test('garde l\'ancien format réservation + lesson imbriqué', () {
    final booking = Booking.fromJson({
      'id': 1,
      'student_id': 2,
      'lesson_id': 3,
      'status': 'confirmed',
      'created_at': '2026-10-01T10:00:00Z',
      'updated_at': '2026-10-01T10:00:00Z',
      'lesson': {'id': 3, 'start_time': '2026-10-08T10:00:00Z', 'status': 'confirmed'},
    });

    expect(booking.lessonId, 3);
    expect(booking.lesson!.id, 3);
    expect(booking.createdAt, DateTime.parse('2026-10-01T10:00:00Z'));
  });
}
