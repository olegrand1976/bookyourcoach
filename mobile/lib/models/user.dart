class User {
  final int id;
  final String name;
  final String email;
  final String? emailVerifiedAt;
  final List<String> roles;
  final Map<String, dynamic>? profile;
  final String? avatar;
  final DateTime createdAt;
  final DateTime updatedAt;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.emailVerifiedAt,
    required this.roles,
    this.profile,
    this.avatar,
    required this.createdAt,
    required this.updatedAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      emailVerifiedAt: json['email_verified_at'],
      roles: List<String>.from(json['roles'] ?? []),
      profile: json['profile'],
      avatar: json['avatar'],
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
      'roles': roles,
      'profile': profile,
      'avatar': avatar,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // Méthodes utilitaires pour vérifier les rôles
  bool get isAdmin => roles.contains('admin');
  bool get isTeacher => roles.contains('teacher') || isAdmin;
  bool get isStudent => roles.contains('student') || isTeacher;

  bool canActAsTeacher() => isTeacher;
  bool canActAsStudent() => isStudent;

  // Méthode pour obtenir le nom d'affichage
  String get displayName {
    if (profile != null && profile!['first_name'] != null && profile!['last_name'] != null) {
      return '${profile!['first_name']} ${profile!['last_name']}';
    }
    return name;
  }

  // Méthode pour obtenir l'avatar ou une image par défaut
  String get avatarUrl {
    if (avatar != null && avatar!.isNotEmpty) {
      return avatar!;
    }
    return 'https://ui-avatars.com/api/?name=${Uri.encodeComponent(displayName)}&background=random';
  }

  // Copie avec modifications
  User copyWith({
    int? id,
    String? name,
    String? email,
    String? emailVerifiedAt,
    List<String>? roles,
    Map<String, dynamic>? profile,
    String? avatar,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return User(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      emailVerifiedAt: emailVerifiedAt ?? this.emailVerifiedAt,
      roles: roles ?? this.roles,
      profile: profile ?? this.profile,
      avatar: avatar ?? this.avatar,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  String toString() {
    return 'User(id: $id, name: $name, email: $email, roles: $roles)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) return true;
    return other is User && other.id == id;
  }

  @override
  int get hashCode => id.hashCode;
}
