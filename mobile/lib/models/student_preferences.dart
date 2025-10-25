class StudentPreferences {
  final int id;
  final int studentId;
  final List<String> preferredDisciplines;
  final List<String> preferredLevels;
  final List<String> preferredFormats;
  final String? location;
  final double? maxPrice;
  final int? maxDistance;
  final bool notificationsEnabled;
  final DateTime createdAt;
  final DateTime updatedAt;

  StudentPreferences({
    required this.id,
    required this.studentId,
    required this.preferredDisciplines,
    required this.preferredLevels,
    required this.preferredFormats,
    this.location,
    this.maxPrice,
    this.maxDistance,
    this.notificationsEnabled = true,
    required this.createdAt,
    required this.updatedAt,
  });

  factory StudentPreferences.fromJson(Map<String, dynamic> json) {
    return StudentPreferences(
      id: json['id'],
      studentId: json['student_id'],
      preferredDisciplines: List<String>.from(json['preferred_disciplines'] ?? []),
      preferredLevels: List<String>.from(json['preferred_levels'] ?? []),
      preferredFormats: List<String>.from(json['preferred_formats'] ?? []),
      location: json['location'],
      maxPrice: json['max_price']?.toDouble(),
      maxDistance: json['max_distance'],
      notificationsEnabled: json['notifications_enabled'] ?? true,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'student_id': studentId,
      'preferred_disciplines': preferredDisciplines,
      'preferred_levels': preferredLevels,
      'preferred_formats': preferredFormats,
      'location': location,
      'max_price': maxPrice,
      'max_distance': maxDistance,
      'notifications_enabled': notificationsEnabled,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // Copie avec modifications
  StudentPreferences copyWith({
    int? id,
    int? studentId,
    List<String>? preferredDisciplines,
    List<String>? preferredLevels,
    List<String>? preferredFormats,
    String? location,
    double? maxPrice,
    int? maxDistance,
    bool? notificationsEnabled,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return StudentPreferences(
      id: id ?? this.id,
      studentId: studentId ?? this.studentId,
      preferredDisciplines: preferredDisciplines ?? this.preferredDisciplines,
      preferredLevels: preferredLevels ?? this.preferredLevels,
      preferredFormats: preferredFormats ?? this.preferredFormats,
      location: location ?? this.location,
      maxPrice: maxPrice ?? this.maxPrice,
      maxDistance: maxDistance ?? this.maxDistance,
      notificationsEnabled: notificationsEnabled ?? this.notificationsEnabled,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  // Méthodes utilitaires
  bool hasDiscipline(String discipline) {
    return preferredDisciplines.contains(discipline);
  }

  bool hasLevel(String level) {
    return preferredLevels.contains(level);
  }

  bool hasFormat(String format) {
    return preferredFormats.contains(format);
  }

  bool get hasPreferences {
    return preferredDisciplines.isNotEmpty || 
           preferredLevels.isNotEmpty || 
           preferredFormats.isNotEmpty;
  }

  @override
  String toString() {
    return 'StudentPreferences(id: $id, disciplines: $preferredDisciplines, levels: $preferredLevels, formats: $preferredFormats)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is StudentPreferences && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}
