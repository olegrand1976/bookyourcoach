class Discipline {
  final int id;
  final String name;
  final String description;
  final bool isActive;
  final List<CourseType> courseTypes;

  Discipline({
    required this.id,
    required this.name,
    required this.description,
    required this.isActive,
    required this.courseTypes,
  });

  factory Discipline.fromJson(Map<String, dynamic> json) {
    return Discipline(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      description: json['description'] ?? '',
      isActive: json['is_active'] ?? true,
      courseTypes: (json['course_types'] as List<dynamic>?)
          ?.map((courseType) => CourseType.fromJson(courseType))
          .toList() ?? [],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'is_active': isActive,
      'course_types': courseTypes.map((ct) => ct.toJson()).toList(),
    };
  }
}

class CourseType {
  final int id;
  final int disciplineId;
  final String name;
  final String description;
  final int? durationMinutes;
  final bool isIndividual;
  final int? maxParticipants;
  final bool isActive;

  CourseType({
    required this.id,
    required this.disciplineId,
    required this.name,
    required this.description,
    this.durationMinutes,
    required this.isIndividual,
    this.maxParticipants,
    required this.isActive,
  });

  factory CourseType.fromJson(Map<String, dynamic> json) {
    return CourseType(
      id: json['id'] ?? 0,
      disciplineId: json['discipline_id'] ?? 0,
      name: json['name'] ?? '',
      description: json['description'] ?? '',
      durationMinutes: json['duration_minutes'],
      isIndividual: json['is_individual'] ?? false,
      maxParticipants: json['max_participants'],
      isActive: json['is_active'] ?? true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'discipline_id': disciplineId,
      'name': name,
      'description': description,
      'duration_minutes': durationMinutes,
      'is_individual': isIndividual,
      'max_participants': maxParticipants,
      'is_active': isActive,
    };
  }

  String get durationDisplay {
    if (durationMinutes == null) return 'Variable';
    if (durationMinutes! < 60) return '${durationMinutes} min';
    final hours = durationMinutes! ~/ 60;
    final minutes = durationMinutes! % 60;
    if (minutes == 0) return '${hours}h';
    return '${hours}h ${minutes}min';
  }

  String get typeDisplay {
    return isIndividual ? 'Individuel' : 'Collectif';
  }
}

class StudentPreference {
  final int id;
  final int studentId;
  final int disciplineId;
  final int? courseTypeId;
  final bool isPreferred;
  final int priorityLevel;
  final Discipline? discipline;
  final CourseType? courseType;

  StudentPreference({
    required this.id,
    required this.studentId,
    required this.disciplineId,
    this.courseTypeId,
    required this.isPreferred,
    required this.priorityLevel,
    this.discipline,
    this.courseType,
  });

  factory StudentPreference.fromJson(Map<String, dynamic> json) {
    return StudentPreference(
      id: json['id'] ?? 0,
      studentId: json['student_id'] ?? 0,
      disciplineId: json['discipline_id'] ?? 0,
      courseTypeId: json['course_type_id'],
      isPreferred: json['is_preferred'] ?? true,
      priorityLevel: json['priority_level'] ?? 1,
      discipline: json['discipline'] != null ? Discipline.fromJson(json['discipline']) : null,
      courseType: json['course_type'] != null ? CourseType.fromJson(json['course_type']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'student_id': studentId,
      'discipline_id': disciplineId,
      'course_type_id': courseTypeId,
      'is_preferred': isPreferred,
      'priority_level': priorityLevel,
      'discipline': discipline?.toJson(),
      'course_type': courseType?.toJson(),
    };
  }
}
