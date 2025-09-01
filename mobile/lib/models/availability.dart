class Availability {
  final int id;
  final int teacherId;
  final int? locationId;
  final DateTime startTime;
  final DateTime endTime;
  final bool isAvailable;
  final String? notes;
  final DateTime createdAt;
  final DateTime updatedAt;
  final Map<String, dynamic>? location;

  Availability({
    required this.id,
    required this.teacherId,
    this.locationId,
    required this.startTime,
    required this.endTime,
    required this.isAvailable,
    this.notes,
    required this.createdAt,
    required this.updatedAt,
    this.location,
  });

  factory Availability.fromJson(Map<String, dynamic> json) {
    return Availability(
      id: json['id'],
      teacherId: json['teacher_id'],
      locationId: json['location_id'],
      startTime: DateTime.parse(json['start_time']),
      endTime: DateTime.parse(json['end_time']),
      isAvailable: json['is_available'] ?? true,
      notes: json['notes'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
      location: json['location'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'teacher_id': teacherId,
      'location_id': locationId,
      'start_time': startTime.toIso8601String(),
      'end_time': endTime.toIso8601String(),
      'is_available': isAvailable,
      'notes': notes,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // Méthodes utilitaires
  String get dayOfWeekDisplay {
    final dayNames = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    return dayNames[startTime.weekday % 7];
  }

  String get formattedTime {
    return '${startTime.hour.toString().padLeft(2, '0')}:${startTime.minute.toString().padLeft(2, '0')} - ${endTime.hour.toString().padLeft(2, '0')}:${endTime.minute.toString().padLeft(2, '0')}';
  }

  String get formattedDate {
    return '${startTime.day.toString().padLeft(2, '0')}/${startTime.month.toString().padLeft(2, '0')}/${startTime.year}';
  }

  Duration get duration => endTime.difference(startTime);

  @override
  String toString() {
    return 'Availability(id: $id, day: $dayOfWeekDisplay, time: $formattedTime)';
  }
}

