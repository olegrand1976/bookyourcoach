class Availability {
  final int id;
  final int teacherId;
  final DateTime startTime;
  final DateTime endTime;
  final String dayOfWeek; // 'monday', 'tuesday', etc.
  final bool isAvailable;
  final String? notes;
  final DateTime createdAt;
  final DateTime updatedAt;

  Availability({
    required this.id,
    required this.teacherId,
    required this.startTime,
    required this.endTime,
    required this.dayOfWeek,
    required this.isAvailable,
    this.notes,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Availability.fromJson(Map<String, dynamic> json) {
    return Availability(
      id: json['id'],
      teacherId: json['teacher_id'],
      startTime: DateTime.parse(json['start_time']),
      endTime: DateTime.parse(json['end_time']),
      dayOfWeek: json['day_of_week'],
      isAvailable: json['is_available'] ?? true,
      notes: json['notes'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'teacher_id': teacherId,
      'start_time': startTime.toIso8601String(),
      'end_time': endTime.toIso8601String(),
      'day_of_week': dayOfWeek,
      'is_available': isAvailable,
      'notes': notes,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // Méthodes utilitaires
  String get dayOfWeekDisplay {
    switch (dayOfWeek.toLowerCase()) {
      case 'monday':
        return 'Lundi';
      case 'tuesday':
        return 'Mardi';
      case 'wednesday':
        return 'Mercredi';
      case 'thursday':
        return 'Jeudi';
      case 'friday':
        return 'Vendredi';
      case 'saturday':
        return 'Samedi';
      case 'sunday':
        return 'Dimanche';
      default:
        return dayOfWeek;
    }
  }

  String get formattedTime {
    return '${startTime.hour.toString().padLeft(2, '0')}:${startTime.minute.toString().padLeft(2, '0')} - ${endTime.hour.toString().padLeft(2, '0')}:${endTime.minute.toString().padLeft(2, '0')}';
  }

  Duration get duration => endTime.difference(startTime);

  @override
  String toString() {
    return 'Availability(id: $id, day: $dayOfWeekDisplay, time: $formattedTime)';
  }
}

