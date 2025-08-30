class UserProfile {
  final String id;
  final String email;
  final String name;
  final bool isTeacher;
  final bool isStudent;
  final bool isAdmin;

  const UserProfile({
    required this.id,
    required this.email,
    required this.name,
    required this.isTeacher,
    required this.isStudent,
    required this.isAdmin,
  });

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    return UserProfile(
      id: json['id']?.toString() ?? '',
      email: json['email'] ?? '',
      name: json['name'] ?? '',
      isTeacher: json['is_teacher'] == true || json['role'] == 'teacher',
      isStudent: json['is_student'] == true || json['role'] == 'student',
      isAdmin: json['is_admin'] == true || json['role'] == 'admin',
    );
  }
}

class StudentProfile {
  final String userId;
  final String level; // beginner/intermediate/advanced
  final String objectives;

  const StudentProfile({
    required this.userId,
    required this.level,
    required this.objectives,
  });

  factory StudentProfile.fromJson(Map<String, dynamic> json) {
    return StudentProfile(
      userId: json['user_id']?.toString() ?? '',
      level: json['level'] ?? 'beginner',
      objectives: json['objectives'] ?? '',
    );
  }
}

class TeacherProfile {
  final String userId;
  final List<String> disciplines;
  final int yearsExperience;
  final String bio;

  const TeacherProfile({
    required this.userId,
    required this.disciplines,
    required this.yearsExperience,
    required this.bio,
  });

  factory TeacherProfile.fromJson(Map<String, dynamic> json) {
    final disc = (json['disciplines'] as List?)?.map((e) => e.toString()).toList() ?? <String>[];
    return TeacherProfile(
      userId: json['user_id']?.toString() ?? '',
      disciplines: disc,
      yearsExperience: (json['years_experience'] as num?)?.toInt() ?? 0,
      bio: json['bio'] ?? '',
    );
  }
}