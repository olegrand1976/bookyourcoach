import 'lesson.dart';
import 'user.dart';

/// Effet d'une annulation sur l'abonnement.
enum CancellationImpact { deducted, notDeducted, pending }

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
  // Annulation : motif, déduite ou non de l'abonnement, état du certificat médical
  final String? cancellationReason;
  final bool cancellationCountInSubscription;
  final String? cancellationCertificateStatus;

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
    this.cancellationReason,
    this.cancellationCountInSubscription = false,
    this.cancellationCertificateStatus,
  });

  /// Deux formats acceptés :
  /// - API actuelle (/student/bookings, /student/lesson-history) : le cours lui-même
  ///   (StudentLessonCalendarResource) — id = id du cours, pas de lesson_id ni de dates de réservation ;
  /// - ancien format : réservation avec lesson_id et un objet `lesson` imbriqué.
  factory Booking.fromJson(Map<String, dynamic> json) {
    final nested = json['lesson'] is Map<String, dynamic> ? json['lesson'] as Map<String, dynamic> : null;
    final lessonJson = nested ?? json;
    dynamic field(String key) => json[key] ?? lessonJson[key];

    return Booking(
      id: _parseInt(json['id']),
      studentId: _parseInt(json['student_id']),
      lessonId: _parseInt(json['lesson_id'] ?? lessonJson['id']),
      status: json['status']?.toString() ?? 'pending',
      bookedAt: _parseDate(json['booked_at']),
      notes: json['notes']?.toString(),
      createdAt: _parseDate(json['created_at']) ?? DateTime.now(),
      updatedAt: _parseDate(json['updated_at']) ?? DateTime.now(),
      lesson: Lesson.fromJson(lessonJson),
      student: json['student'] is Map<String, dynamic> ? User.fromJson(json['student']) : null,
      cancellationReason: field('cancellation_reason')?.toString(),
      cancellationCountInSubscription: _parseBool(field('cancellation_count_in_subscription')),
      cancellationCertificateStatus: field('cancellation_certificate_status')?.toString(),
    );
  }

  static int _parseInt(dynamic value) => value is int ? value : int.tryParse('${value ?? ''}') ?? 0;

  static DateTime? _parseDate(dynamic value) => value == null ? null : DateTime.tryParse(value.toString());

  /// L'API peut renvoyer true/false, 1/0 ou "1"/"0".
  static bool _parseBool(dynamic value) =>
      value == true || value == 1 || value == '1' || value == 'true';

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

  /// Médical sans certificat accepté → en attente ; sinon déduit / non déduit.
  CancellationImpact get cancellationImpact {
    if (cancellationReason == 'medical' && cancellationCertificateStatus != 'accepted') {
      return CancellationImpact.pending;
    }
    return cancellationCountInSubscription
        ? CancellationImpact.deducted
        : CancellationImpact.notDeducted;
  }

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
      cancellationReason: cancellationReason,
      cancellationCountInSubscription: cancellationCountInSubscription,
      cancellationCertificateStatus: cancellationCertificateStatus,
    );
  }

  @override
  String toString() {
    return 'Booking(id: $id, status: $status, lessonId: $lessonId)';
  }
}
