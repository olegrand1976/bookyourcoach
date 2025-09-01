class User {
  final int id;
  final String name;
  final String email;
  final String? emailVerifiedAt;
  final String role;
  final Map<String, dynamic>? profile;
  final String? avatar;
  final String? phone;
  final String status;
  final bool isActive;
  final bool canActAsTeacher;
  final bool canActAsStudent;
  final bool isAdmin;
  final DateTime createdAt;
  final DateTime updatedAt;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.emailVerifiedAt,
    required this.role,
    this.profile,
    this.avatar,
    this.phone,
    required this.status,
    required this.isActive,
    required this.canActAsTeacher,
    required this.canActAsStudent,
    required this.isAdmin,
    required this.createdAt,
    required this.updatedAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      emailVerifiedAt: json['email_verified_at'],
      role: json['role'] ?? 'student',
      profile: json['profile'],
      avatar: json['avatar'],
      phone: json['phone'],
      status: json['status'] ?? 'active',
      isActive: json['is_active'] ?? true,
      canActAsTeacher: json['can_act_as_teacher'] ?? false,
      canActAsStudent: json['can_act_as_student'] ?? true,
      isAdmin: json['is_admin'] ?? false,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'email_verified_at': emailVerifiedAt,
      'role': role,
      'profile': profile,
      'avatar': avatar,
      'phone': phone,
      'status': status,
      'is_active': isActive,
      'can_act_as_teacher': canActAsTeacher,
      'can_act_as_student': canActAsStudent,
      'is_admin': isAdmin,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // Méthodes utilitaires pour vérifier les rôles
  bool get isTeacher => role == 'teacher' || isAdmin;
  bool get isStudent => role == 'student' || isTeacher;

  // Méthode pour obtenir le nom d'affichage
  String get displayName {
    if (profile != null && profile!['first_name'] != null && profile!['last_name'] != null) {
      return '${profile!['first_name']} ${profile!['last_name']}';
    }
    return name;
  }

  // Méthode pour obtenir l'avatar ou une image par défaut
  String get avatarUrl {
    if (avatar != null && avatar!.isNotEmpty && avatar!.startsWith('http')) {
      return avatar!;
    }
    // Utiliser un service d'avatar par défaut avec le nom de l'utilisateur
    return 'https://ui-avatars.com/api/?name=${Uri.encodeComponent(displayName)}&background=2563eb&color=ffffff&size=128';
  }

  // Copie avec modifications
  User copyWith({
    int? id,
    String? name,
    String? email,
    String? emailVerifiedAt,
    String? role,
    Map<String, dynamic>? profile,
    String? avatar,
    String? phone,
    String? status,
    bool? isActive,
    bool? canActAsTeacher,
    bool? canActAsStudent,
    bool? isAdmin,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return User(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      emailVerifiedAt: emailVerifiedAt ?? this.emailVerifiedAt,
      role: role ?? this.role,
      profile: profile ?? this.profile,
      avatar: avatar ?? this.avatar,
      phone: phone ?? this.phone,
      status: status ?? this.status,
      isActive: isActive ?? this.isActive,
      canActAsTeacher: canActAsTeacher ?? this.canActAsTeacher,
      canActAsStudent: canActAsStudent ?? this.canActAsStudent,
      isAdmin: isAdmin ?? this.isAdmin,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  String toString() {
    return 'User(id: $id, name: $name, email: $email, role: $role)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is User && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}
