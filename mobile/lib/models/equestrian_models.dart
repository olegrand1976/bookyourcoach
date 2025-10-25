// Modèles pour les disciplines équestres et tableaux de bord avancés

class EquestrianDiscipline {
  final int id;
  final String name;
  final String code; // 'dressage', 'jumping', 'eventing', 'western', etc.
  final String description;
  final String? iconPath;
  final List<String> levels; // ['debutant', 'intermediaire', 'avance', 'expert']
  final Map<String, dynamic>? rules;
  final bool isActive;

  EquestrianDiscipline({
    required this.id,
    required this.name,
    required this.code,
    required this.description,
    this.iconPath,
    required this.levels,
    this.rules,
    required this.isActive,
  });

  factory EquestrianDiscipline.fromJson(Map<String, dynamic> json) {
    return EquestrianDiscipline(
      id: json['id'] ?? 0,
      name: json['name']?.toString() ?? '',
      code: json['code']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      iconPath: json['icon_path']?.toString(),
      levels: List<String>.from(json['levels'] ?? []),
      rules: json['rules'],
      isActive: json['is_active'] ?? true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'code': code,
      'description': description,
      'icon_path': iconPath,
      'levels': levels,
      'rules': rules,
      'is_active': isActive,
    };
  }
}

class StudentDiscipline {
  final int id;
  final int studentId;
  final int disciplineId;
  final String currentLevel;
  final String? targetLevel;
  final DateTime startDate;
  final DateTime? lastAssessmentDate;
  final double? currentScore;
  final double? targetScore;
  final String? notes;
  final bool isActive;
  final DateTime createdAt;
  final DateTime updatedAt;
  final EquestrianDiscipline? discipline;

  StudentDiscipline({
    required this.id,
    required this.studentId,
    required this.disciplineId,
    required this.currentLevel,
    this.targetLevel,
    required this.startDate,
    this.lastAssessmentDate,
    this.currentScore,
    this.targetScore,
    this.notes,
    required this.isActive,
    required this.createdAt,
    required this.updatedAt,
    this.discipline,
  });

  factory StudentDiscipline.fromJson(Map<String, dynamic> json) {
    return StudentDiscipline(
      id: json['id'] ?? 0,
      studentId: json['student_id'] ?? 0,
      disciplineId: json['discipline_id'] ?? 0,
      currentLevel: json['current_level']?.toString() ?? 'debutant',
      targetLevel: json['target_level']?.toString(),
      startDate: DateTime.parse(json['start_date'] ?? DateTime.now().toIso8601String()),
      lastAssessmentDate: json['last_assessment_date'] != null 
          ? DateTime.parse(json['last_assessment_date']) 
          : null,
      currentScore: json['current_score']?.toDouble(),
      targetScore: json['target_score']?.toDouble(),
      notes: json['notes']?.toString(),
      isActive: json['is_active'] ?? true,
      createdAt: DateTime.parse(json['created_at'] ?? DateTime.now().toIso8601String()),
      updatedAt: DateTime.parse(json['updated_at'] ?? DateTime.now().toIso8601String()),
      discipline: json['discipline'] != null 
          ? EquestrianDiscipline.fromJson(json['discipline']) 
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'student_id': studentId,
      'discipline_id': disciplineId,
      'current_level': currentLevel,
      'target_level': targetLevel,
      'start_date': startDate.toIso8601String(),
      'last_assessment_date': lastAssessmentDate?.toIso8601String(),
      'current_score': currentScore,
      'target_score': targetScore,
      'notes': notes,
      'is_active': isActive,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }
}

class PerformanceMetrics {
  final int id;
  final int lessonId;
  final int studentId;
  final int disciplineId;
  final DateTime date;
  final Map<String, dynamic> gpsData; // Données GPS
  final Map<String, dynamic> sensorData; // Données capteurs
  final Map<String, dynamic> videoData; // Données vidéo
  final Map<String, dynamic> assessmentData; // Notations officielles
  final double? overallScore;
  final String? notes;
  final DateTime createdAt;

  PerformanceMetrics({
    required this.id,
    required this.lessonId,
    required this.studentId,
    required this.disciplineId,
    required this.date,
    required this.gpsData,
    required this.sensorData,
    required this.videoData,
    required this.assessmentData,
    this.overallScore,
    this.notes,
    required this.createdAt,
  });

  factory PerformanceMetrics.fromJson(Map<String, dynamic> json) {
    return PerformanceMetrics(
      id: json['id'] ?? 0,
      lessonId: json['lesson_id'] ?? 0,
      studentId: json['student_id'] ?? 0,
      disciplineId: json['discipline_id'] ?? 0,
      date: DateTime.parse(json['date'] ?? DateTime.now().toIso8601String()),
      gpsData: Map<String, dynamic>.from(json['gps_data'] ?? {}),
      sensorData: Map<String, dynamic>.from(json['sensor_data'] ?? {}),
      videoData: Map<String, dynamic>.from(json['video_data'] ?? {}),
      assessmentData: Map<String, dynamic>.from(json['assessment_data'] ?? {}),
      overallScore: json['overall_score']?.toDouble(),
      notes: json['notes']?.toString(),
      createdAt: DateTime.parse(json['created_at'] ?? DateTime.now().toIso8601String()),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'lesson_id': lessonId,
      'student_id': studentId,
      'discipline_id': disciplineId,
      'date': date.toIso8601String(),
      'gps_data': gpsData,
      'sensor_data': sensorData,
      'video_data': videoData,
      'assessment_data': assessmentData,
      'overall_score': overallScore,
      'notes': notes,
      'created_at': createdAt.toIso8601String(),
    };
  }

  // Méthodes utilitaires pour extraire des métriques spécifiques
  double? get averageSpeed => gpsData['average_speed']?.toDouble();
  double? get maxSpeed => gpsData['max_speed']?.toDouble();
  double? get distance => gpsData['distance']?.toDouble();
  double? get rhythmScore => sensorData['rhythm_score']?.toDouble();
  double? get balanceScore => sensorData['balance_score']?.toDouble();
  double? get techniqueScore => assessmentData['technique_score']?.toDouble();
  double? get artisticScore => assessmentData['artistic_score']?.toDouble();
}

class DashboardStats {
  final int totalLessons;
  final int completedLessons;
  final int upcomingLessons;
  final double averageScore;
  final double totalHours;
  final double totalDistance;
  final Map<String, int> lessonsByDiscipline;
  final Map<String, double> progressByDiscipline;
  final List<PerformanceMetrics> recentMetrics;
  final Map<String, dynamic> alerts;

  DashboardStats({
    required this.totalLessons,
    required this.completedLessons,
    required this.upcomingLessons,
    required this.averageScore,
    required this.totalHours,
    required this.totalDistance,
    required this.lessonsByDiscipline,
    required this.progressByDiscipline,
    required this.recentMetrics,
    required this.alerts,
  });

  factory DashboardStats.fromJson(Map<String, dynamic> json) {
    return DashboardStats(
      totalLessons: json['total_lessons'] ?? 0,
      completedLessons: json['completed_lessons'] ?? 0,
      upcomingLessons: json['upcoming_lessons'] ?? 0,
      averageScore: json['average_score']?.toDouble() ?? 0.0,
      totalHours: json['total_hours']?.toDouble() ?? 0.0,
      totalDistance: json['total_distance']?.toDouble() ?? 0.0,
      lessonsByDiscipline: Map<String, int>.from(json['lessons_by_discipline'] ?? {}),
      progressByDiscipline: Map<String, double>.from(json['progress_by_discipline'] ?? {}),
      recentMetrics: (json['recent_metrics'] as List? ?? [])
          .map((metric) => PerformanceMetrics.fromJson(metric))
          .toList(),
      alerts: Map<String, dynamic>.from(json['alerts'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'total_lessons': totalLessons,
      'completed_lessons': completedLessons,
      'upcoming_lessons': upcomingLessons,
      'average_score': averageScore,
      'total_hours': totalHours,
      'total_distance': totalDistance,
      'lessons_by_discipline': lessonsByDiscipline,
      'progress_by_discipline': progressByDiscipline,
      'recent_metrics': recentMetrics.map((m) => m.toJson()).toList(),
      'alerts': alerts,
    };
  }
}

class TrainingGoal {
  final int id;
  final int studentId;
  final int disciplineId;
  final String title;
  final String description;
  final String targetLevel;
  final double targetScore;
  final DateTime targetDate;
  final DateTime? completedDate;
  final String status; // 'active', 'completed', 'cancelled'
  final List<String> milestones;
  final Map<String, bool> completedMilestones;
  final DateTime createdAt;
  final DateTime updatedAt;

  TrainingGoal({
    required this.id,
    required this.studentId,
    required this.disciplineId,
    required this.title,
    required this.description,
    required this.targetLevel,
    required this.targetScore,
    required this.targetDate,
    this.completedDate,
    required this.status,
    required this.milestones,
    required this.completedMilestones,
    required this.createdAt,
    required this.updatedAt,
  });

  factory TrainingGoal.fromJson(Map<String, dynamic> json) {
    return TrainingGoal(
      id: json['id'] ?? 0,
      studentId: json['student_id'] ?? 0,
      disciplineId: json['discipline_id'] ?? 0,
      title: json['title']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      targetLevel: json['target_level']?.toString() ?? '',
      targetScore: json['target_score']?.toDouble() ?? 0.0,
      targetDate: DateTime.parse(json['target_date'] ?? DateTime.now().toIso8601String()),
      completedDate: json['completed_date'] != null 
          ? DateTime.parse(json['completed_date']) 
          : null,
      status: json['status']?.toString() ?? 'active',
      milestones: List<String>.from(json['milestones'] ?? []),
      completedMilestones: Map<String, bool>.from(json['completed_milestones'] ?? {}),
      createdAt: DateTime.parse(json['created_at'] ?? DateTime.now().toIso8601String()),
      updatedAt: DateTime.parse(json['updated_at'] ?? DateTime.now().toIso8601String()),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'student_id': studentId,
      'discipline_id': disciplineId,
      'title': title,
      'description': description,
      'target_level': targetLevel,
      'target_score': targetScore,
      'target_date': targetDate.toIso8601String(),
      'completed_date': completedDate?.toIso8601String(),
      'status': status,
      'milestones': milestones,
      'completed_milestones': completedMilestones,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  double get progressPercentage {
    if (milestones.isEmpty) return 0.0;
    int completed = completedMilestones.values.where((completed) => completed).length;
    return (completed / milestones.length) * 100;
  }

  bool get isOverdue => DateTime.now().isAfter(targetDate) && status == 'active';
  bool get isCompleted => status == 'completed';
}
