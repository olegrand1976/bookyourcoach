import 'user.dart';

class Lesson {
  final int id;
  final String title;
  final String description;
  final DateTime startTime;
  final DateTime endTime;
  final String status; // 'scheduled', 'in_progress', 'completed', 'cancelled'
  final int teacherId;
  final int? studentId;
  final String? location;
  final double? price;
  final String? notes;
  final DateTime createdAt;
  final DateTime updatedAt;
  final User? teacher;
  final User? student;

  Lesson({
    required this.id,
    required this.title,
    required this.description,
    required this.startTime,
    required this.endTime,
    required this.status,
    required this.teacherId,
    this.studentId,
    this.location,
    this.price,
    this.notes,
    required this.createdAt,
    required this.updatedAt,
    this.teacher,
    this.student,
  });

  factory Lesson.fromJson(Map<String, dynamic> json) {
    return Lesson(
      id: json['id'],
      title: json['title'],
      description: json['description'],
      startTime: DateTime.parse(json['start_time']),
      endTime: DateTime.parse(json['end_time']),
      status: json['status'],
      teacherId: json['teacher_id'],
      studentId: json['student_id'],
      location: json['location'],
      price: json['price']?.toDouble(),
      notes: json['notes'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      teacher: json['teacher'] != null ? User.fromJson(json['teacher']) : null,
      student: json['student'] != null ? User.fromJson(json['student']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'description': description,
      'start_time': startTime.toIso8601String(),
      'end_time': endTime.toIso8601String(),
      'status': status,
      'teacher_id': teacherId,
      'student_id': studentId,
      'location': location,
      'price': price,
      'notes': notes,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // Méthodes utilitaires
  bool get isScheduled => status == 'scheduled';
  bool get isInProgress => status == 'in_progress';
  bool get isCompleted => status == 'completed';
  bool get isCancelled => status == 'cancelled';

  Duration get duration => endTime.difference(startTime);
  bool get isToday => startTime.day == DateTime.now().day && 
                     startTime.month == DateTime.now().month && 
                     startTime.year == DateTime.now().year;
  bool get isUpcoming => startTime.isAfter(DateTime.now());

  String get statusDisplay {
    switch (status) {
      case 'scheduled':
        return 'Planifié';
      case 'in_progress':
        return 'En cours';
      case 'completed':
        return 'Terminé';
      case 'cancelled':
        return 'Annulé';
      default:
        return 'Inconnu';
    }
  }

  String get formattedTime {
    return '${startTime.hour.toString().padLeft(2, '0')}:${startTime.minute.toString().padLeft(2, '0')} - ${endTime.hour.toString().padLeft(2, '0')}:${endTime.minute.toString().padLeft(2, '0')}';
  }

  String get formattedDate {
    return '${startTime.day.toString().padLeft(2, '0')}/${startTime.month.toString().padLeft(2, '0')}/${startTime.year}';
  }

  @override
  String toString() {
    return 'Lesson(id: $id, title: $title, status: $status)';
  }
}

