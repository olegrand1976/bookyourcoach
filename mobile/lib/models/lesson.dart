class Lesson {
  final String id;
  final String teacherId;
  final DateTime start;
  final int durationMinutes;
  final String discipline;
  final String location;
  final int capacity;
  final int bookedCount;

  const Lesson({
    required this.id,
    required this.teacherId,
    required this.start,
    required this.durationMinutes,
    required this.discipline,
    required this.location,
    required this.capacity,
    required this.bookedCount,
  });

  factory Lesson.fromJson(Map<String, dynamic> json) {
    return Lesson(
      id: json['id']?.toString() ?? '',
      teacherId: json['teacher_id']?.toString() ?? '',
      start: DateTime.tryParse(json['start'] ?? '') ?? DateTime.now(),
      durationMinutes: (json['duration_minutes'] as num?)?.toInt() ?? 60,
      discipline: json['discipline'] ?? '',
      location: json['location'] ?? '',
      capacity: (json['capacity'] as num?)?.toInt() ?? 1,
      bookedCount: (json['booked_count'] as num?)?.toInt() ?? 0,
    );
  }
}

class Booking {
  final String id;
  final String lessonId;
  final String studentId;
  final String status; // confirmed/cancelled/pending

  const Booking({
    required this.id,
    required this.lessonId,
    required this.studentId,
    required this.status,
  });

  factory Booking.fromJson(Map<String, dynamic> json) {
    return Booking(
      id: json['id']?.toString() ?? '',
      lessonId: json['lesson_id']?.toString() ?? '',
      studentId: json['student_id']?.toString() ?? '',
      status: json['status'] ?? 'confirmed',
    );
  }
}