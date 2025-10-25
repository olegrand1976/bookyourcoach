import 'user.dart';

class LessonStudent {
  final int studentId;
  final String status;
  final double? price;
  final String? notes;
  final User? student;

  LessonStudent({
    required this.studentId,
    required this.status,
    this.price,
    this.notes,
    this.student,
  });

  factory LessonStudent.fromJson(Map<String, dynamic> json) {
    return LessonStudent(
      studentId: json['student_id'] ?? json['id'] ?? 0,
      status: json['status']?.toString() ?? 'pending',
      price: json['price'] != null ? (json['price'] is String ? double.tryParse(json['price']) ?? 0.0 : (json['price'] is num ? json['price'].toDouble() : 0.0)) : null,
      notes: json['notes']?.toString(),
      student: json['student'] != null ? User.fromJson(json['student']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'student_id': studentId,
      'status': status,
      'price': price,
      'notes': notes,
    };
  }
}

class Lesson {
  final int id;
  final String title;
  final String description;
  final DateTime startTime;
  final DateTime endTime;
  final String status; // 'scheduled', 'in_progress', 'completed', 'cancelled'
  final int teacherId;
  final int? studentId; // Étudiant principal (pour compatibilité)
  final int? courseTypeId;
  final int? locationId;
  final String? location;
  final double? price;
  final String? notes;
  final String? teacherFeedback;
  final double? rating;
  final String? review;
  final String? paymentStatus;
  final DateTime createdAt;
  final DateTime updatedAt;
  final User? teacher;
  final User? student; // Étudiant principal (pour compatibilité)
  final List<LessonStudent>? students; // Nouveaux étudiants multiples
  final Map<String, dynamic>? courseType;
  final Map<String, dynamic>? locationData;

  Lesson({
    required this.id,
    required this.title,
    required this.description,
    required this.startTime,
    required this.endTime,
    required this.status,
    required this.teacherId,
    this.studentId,
    this.courseTypeId,
    this.locationId,
    this.location,
    this.price,
    this.notes,
    this.teacherFeedback,
    this.rating,
    this.review,
    this.paymentStatus,
    required this.createdAt,
    required this.updatedAt,
    this.teacher,
    this.student,
    this.students,
    this.courseType,
    this.locationData,
  });

  factory Lesson.fromJson(Map<String, dynamic> json) {
    try {
      // Traiter les étudiants multiples
      List<LessonStudent>? studentsList;
      if (json['students'] != null && json['students'] is List) {
        studentsList = (json['students'] as List)
            .map((studentJson) => LessonStudent.fromJson(studentJson))
            .toList();
      }

      // Gestion sécurisée du titre
      String safeTitle = 'Cours';
      if (json['title'] != null) {
        safeTitle = json['title'].toString();
      } else if (json['course_type'] != null && json['course_type'] is Map) {
        final courseType = json['course_type'] as Map;
        if (courseType['name'] != null) {
          safeTitle = courseType['name'].toString();
        }
      } else if (json['notes'] != null) {
        safeTitle = json['notes'].toString();
      }

      // Gestion sécurisée de la description
      String safeDescription = 'Description du cours';
      if (json['description'] != null) {
        safeDescription = json['description'].toString();
      } else if (json['course_type'] != null && json['course_type'] is Map) {
        final courseType = json['course_type'] as Map;
        if (courseType['description'] != null) {
          safeDescription = courseType['description'].toString();
        }
      }

      // Gestion sécurisée des dates
      DateTime safeStartTime = DateTime.now();
      if (json['start_time'] != null) {
        try {
          safeStartTime = DateTime.parse(json['start_time'].toString());
        } catch (e) {
          print('Erreur parsing start_time: $e');
        }
      }

      DateTime safeEndTime = safeStartTime.add(const Duration(hours: 1));
      if (json['end_time'] != null) {
        try {
          safeEndTime = DateTime.parse(json['end_time'].toString());
        } catch (e) {
          print('Erreur parsing end_time: $e');
        }
      }

      // Gestion sécurisée du statut
      String safeStatus = 'pending';
      if (json['status'] != null) {
        safeStatus = json['status'].toString();
      }

      // Gestion sécurisée de l'emplacement
      String safeLocation = 'Lieu non spécifié';
      if (json['location'] != null && json['location'] is Map) {
        final location = json['location'] as Map;
        if (location['name'] != null) {
          safeLocation = location['name'].toString();
        }
      } else if (json['location_data'] != null && json['location_data'] is Map) {
        final locationData = json['location_data'] as Map;
        if (locationData['name'] != null) {
          safeLocation = locationData['name'].toString();
        }
      } else if (json['location_id'] != null) {
        safeLocation = json['location_id'].toString();
      }

      // Gestion sécurisée des prix et notes
      double? safePrice;
      if (json['price'] != null) {
        if (json['price'] is String) {
          safePrice = double.tryParse(json['price']);
        } else if (json['price'] is num) {
          safePrice = json['price'].toDouble();
        }
      }

      String? safeNotes;
      if (json['notes'] != null) {
        safeNotes = json['notes'].toString();
      }

      String? safeTeacherFeedback;
      if (json['teacher_feedback'] != null) {
        safeTeacherFeedback = json['teacher_feedback'].toString();
      }

      double? safeRating;
      if (json['rating'] != null) {
        if (json['rating'] is String) {
          safeRating = double.tryParse(json['rating']);
        } else if (json['rating'] is num) {
          safeRating = json['rating'].toDouble();
        }
      }

      String? safeReview;
      if (json['review'] != null) {
        safeReview = json['review'].toString();
      }

      String? safePaymentStatus;
      if (json['payment_status'] != null) {
        safePaymentStatus = json['payment_status'].toString();
      }

      // Gestion sécurisée des dates de création/modification
      DateTime safeCreatedAt = DateTime.now();
      if (json['created_at'] != null) {
        try {
          safeCreatedAt = DateTime.parse(json['created_at'].toString());
        } catch (e) {
          print('Erreur parsing created_at: $e');
        }
      }

      DateTime safeUpdatedAt = DateTime.now();
      if (json['updated_at'] != null) {
        try {
          safeUpdatedAt = DateTime.parse(json['updated_at'].toString());
        } catch (e) {
          print('Erreur parsing updated_at: $e');
        }
      }

      // Gestion sécurisée des objets User
      User? safeTeacher;
      if (json['teacher'] != null && json['teacher'] is Map) {
        try {
          safeTeacher = User.fromJson(json['teacher']);
        } catch (e) {
          print('Erreur parsing teacher: $e');
        }
      }

      User? safeStudent;
      if (json['student'] != null && json['student'] is Map) {
        try {
          safeStudent = User.fromJson(json['student']);
        } catch (e) {
          print('Erreur parsing student: $e');
        }
      }

      return Lesson(
        id: json['id'] ?? 0,
        title: safeTitle,
        description: safeDescription,
        startTime: safeStartTime,
        endTime: safeEndTime,
        status: safeStatus,
        teacherId: json['teacher_id'] ?? 0,
        studentId: json['student_id'],
        courseTypeId: json['course_type_id'],
        locationId: json['location_id'],
        location: safeLocation,
        price: safePrice,
        notes: safeNotes,
        teacherFeedback: safeTeacherFeedback,
        rating: safeRating,
        review: safeReview,
        paymentStatus: safePaymentStatus,
        createdAt: safeCreatedAt,
        updatedAt: safeUpdatedAt,
        teacher: safeTeacher,
        student: safeStudent,
        students: studentsList,
        courseType: json['course_type'] is Map<String, dynamic> ? json['course_type'] : null,
        locationData: json['location'] is Map<String, dynamic> ? json['location'] : null,
      );
    } catch (e) {
      // Log l'erreur pour le débogage
      print('Erreur lors de la création de Lesson depuis JSON: $e');
      print('JSON reçu: $json');
      
      // Retourner un objet Lesson par défaut en cas d'erreur
      return Lesson(
        id: json['id'] ?? 0,
        title: 'Cours',
        description: 'Description du cours',
        startTime: DateTime.now(),
        endTime: DateTime.now().add(const Duration(hours: 1)),
        status: 'pending',
        teacherId: json['teacher_id'] ?? 0,
        createdAt: DateTime.now(),
        updatedAt: DateTime.now(),
      );
    }
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
      'course_type_id': courseTypeId,
      'location_id': locationId,
      'location': location,
      'price': price,
      'notes': notes,
      'teacher_feedback': teacherFeedback,
      'rating': rating,
      'review': review,
      'payment_status': paymentStatus,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
      'students': students?.map((s) => s.toJson()).toList(),
    };
  }

  // Méthodes utilitaires
  bool get isScheduled => status == 'pending' || status == 'confirmed';
  bool get isInProgress => status == 'confirmed' && startTime.isBefore(DateTime.now()) && endTime.isAfter(DateTime.now());
  bool get isCompleted => status == 'completed';
  bool get isCancelled => status == 'cancelled';

  Duration get duration => endTime.difference(startTime);
  bool get isToday => startTime.day == DateTime.now().day && 
                     startTime.month == DateTime.now().month && 
                     startTime.year == DateTime.now().year;
  bool get isUpcoming => startTime.isAfter(DateTime.now());

  // Nouvelles propriétés pour les cours avec plusieurs élèves
  bool get isGroupLesson => (students?.length ?? 0) > 1;
  int get studentCount => students?.length ?? (student != null ? 1 : 0);
  
  // Obtenir tous les étudiants (principal + multiples)
  List<User> get allStudents {
    List<User> allStudents = [];
    if (student != null) {
      allStudents.add(student!);
    }
    if (students != null) {
      for (var lessonStudent in students!) {
        if (lessonStudent.student != null && 
            !allStudents.any((s) => s.id == lessonStudent.student!.id)) {
          allStudents.add(lessonStudent.student!);
        }
      }
    }
    return allStudents;
  }

  // Obtenir le prix total pour tous les étudiants
  double get totalPrice {
    double total = 0;
    if (price != null) {
      total += price!;
    }
    if (students != null) {
      for (var lessonStudent in students!) {
        if (lessonStudent.price != null) {
          total += lessonStudent.price!;
        }
      }
    }
    return total;
  }

  String get statusDisplay {
    switch (status) {
      case 'pending':
        return 'En attente';
      case 'confirmed':
        return 'Confirmé';
      case 'completed':
        return 'Terminé';
      case 'cancelled':
        return 'Annulé';
      case 'no_show':
        return 'Absent';
      case 'available':
        return 'Disponible';
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
    return 'Lesson(id: $id, title: $title, status: $status, students: ${studentCount})';
  }
}

