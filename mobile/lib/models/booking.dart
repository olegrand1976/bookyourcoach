import 'lesson.dart';
import 'user.dart';

class Booking {
  final int id;
  final int studentId;
  final int lessonId;
  final String status; // 'pending', 'confirmed', 'cancelled', 'completed'
  final DateTime? bookedAt;
  final String? notes;
  final DateTime createdAt;
  final DateTime updatedAt;
  final Lesson? lesson;
  final User? student;

  Booking({
    required this.id,
    required this.studentId,
    required this.lessonId,
    required this.status,
    this.bookedAt,
    this.notes,
    required this.createdAt,
    required this.updatedAt,
    this.lesson,
    this.student,
  });

  factory Booking.fromJson(Map<String, dynamic> json) {
    return Booking(
      id: json['id'],
      studentId: json['student_id'],
      lessonId: json['lesson_id'],
      status: json['status'],
      bookedAt: json['booked_at'] != null ? DateTime.parse(json['booked_at']) : null,
      notes: json['notes'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      lesson: json['lesson'] != null ? Lesson.fromJson(json['lesson']) : null,
      student: json['student'] != null ? User.fromJson(json['student']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'student_id': studentId,
      'lesson_id': lessonId,
      'status': status,
      'booked_at': bookedAt?.toIso8601String(),
      'notes': notes,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // Méthodes utilitaires
  bool get isPending => status == 'pending';
  bool get isConfirmed => status == 'confirmed';
  bool get isCancelled => status == 'cancelled';
  bool get isCompleted => status == 'completed';

  String get statusDisplay {
    switch (status) {
      case 'pending':
        return 'En attente';
      case 'confirmed':
        return 'Confirmé';
      case 'cancelled':
        return 'Annulé';
      case 'completed':
        return 'Terminé';
      default:
        return 'Inconnu';
    }
  }

  // Copie avec modifications
  Booking copyWith({
    int? id,
    int? studentId,
    int? lessonId,
    String? status,
    DateTime? bookedAt,
    String? notes,
    DateTime? createdAt,
    DateTime? updatedAt,
    Lesson? lesson,
    User? student,
  }) {
    return Booking(
      id: id ?? this.id,
      studentId: studentId ?? this.studentId,
      lessonId: lessonId ?? this.lessonId,
      status: status ?? this.status,
      bookedAt: bookedAt ?? this.bookedAt,
      notes: notes ?? this.notes,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      lesson: lesson ?? this.lesson,
      student: student ?? this.student,
    );
  }

  @override
  String toString() {
    return 'Booking(id: $id, status: $status, lessonId: $lessonId)';
  }
}
