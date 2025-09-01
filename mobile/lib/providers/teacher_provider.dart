import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../services/teacher_service.dart';
import '../models/lesson.dart';
import '../models/availability.dart';
import '../models/user.dart';

// Provider pour le service enseignant
final teacherServiceProvider = Provider<TeacherService>((ref) {
  return TeacherService();
});

// État pour les cours de l'enseignant
class TeacherLessonsState {
  final bool isLoading;
  final List<Lesson> lessons;
  final String? error;
  final String? filterStatus;
  final DateTime? filterDate;

  TeacherLessonsState({
    this.isLoading = false,
    this.lessons = const [],
    this.error,
    this.filterStatus,
    this.filterDate,
  });

  TeacherLessonsState copyWith({
    bool? isLoading,
    List<Lesson>? lessons,
    String? error,
    String? filterStatus,
    DateTime? filterDate,
  }) {
    return TeacherLessonsState(
      isLoading: isLoading ?? this.isLoading,
      lessons: lessons ?? this.lessons,
      error: error,
      filterStatus: filterStatus ?? this.filterStatus,
      filterDate: filterDate ?? this.filterDate,
    );
  }
}

// Notifier pour les cours de l'enseignant
class TeacherLessonsNotifier extends StateNotifier<TeacherLessonsState> {
  final TeacherService _teacherService;

  TeacherLessonsNotifier(this._teacherService) : super(TeacherLessonsState());

  Future<void> loadLessons({String? status, DateTime? date}) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final lessons = await _teacherService.getTeacherLessons(
        status: status,
        date: date,
      );
      state = state.copyWith(
        isLoading: false,
        lessons: lessons,
        filterStatus: status,
        filterDate: date,
      );
    } catch (e) {
      String errorMessage = 'Erreur lors du chargement des cours';
      
      if (e.toString().contains('type \'Null\' is not a subtype of type \'String\'')) {
        errorMessage = 'Erreur de format des données reçues. Veuillez réessayer.';
      } else if (e.toString().contains('Token non trouvé')) {
        errorMessage = 'Session expirée. Veuillez vous reconnecter.';
      } else if (e.toString().contains('Erreur de connexion')) {
        errorMessage = 'Problème de connexion au serveur. Vérifiez votre connexion internet.';
      } else if (e.toString().contains('timeout')) {
        errorMessage = 'Délai de réponse dépassé. Veuillez réessayer.';
      }
      
      state = state.copyWith(
        isLoading: false,
        error: errorMessage,
      );
    }
  }

  Future<void> createLesson({
    required String title,
    required String description,
    required DateTime startTime,
    required DateTime endTime,
    String? location,
    double? price,
    String? notes,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final newLesson = await _teacherService.createLesson(
        title: title,
        description: description,
        startTime: startTime,
        endTime: endTime,
        location: location,
        price: price,
        notes: notes,
      );
      
      final updatedLessons = [newLesson, ...state.lessons];
      state = state.copyWith(
        isLoading: false,
        lessons: updatedLessons,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<void> updateLesson({
    required int lessonId,
    String? title,
    String? description,
    DateTime? startTime,
    DateTime? endTime,
    String? location,
    double? price,
    String? notes,
    String? status,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final updatedLesson = await _teacherService.updateLesson(
        lessonId: lessonId,
        title: title,
        description: description,
        startTime: startTime,
        endTime: endTime,
        location: location,
        price: price,
        notes: notes,
        status: status,
      );
      
      final updatedLessons = state.lessons.map((lesson) {
        return lesson.id == lessonId ? updatedLesson : lesson;
      }).toList();
      
      state = state.copyWith(
        isLoading: false,
        lessons: updatedLessons,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<void> deleteLesson(int lessonId) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final success = await _teacherService.deleteLesson(lessonId);
      if (success) {
        final updatedLessons = state.lessons.where((lesson) => lesson.id != lessonId).toList();
        state = state.copyWith(
          isLoading: false,
          lessons: updatedLessons,
        );
      } else {
        throw Exception('Erreur lors de la suppression du cours');
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Provider pour les cours de l'enseignant
final teacherLessonsProvider = StateNotifierProvider<TeacherLessonsNotifier, TeacherLessonsState>((ref) {
  final teacherService = ref.watch(teacherServiceProvider);
  return TeacherLessonsNotifier(teacherService);
});

// État pour les disponibilités de l'enseignant
class TeacherAvailabilitiesState {
  final bool isLoading;
  final List<Availability> availabilities;
  final String? error;

  TeacherAvailabilitiesState({
    this.isLoading = false,
    this.availabilities = const [],
    this.error,
  });

  TeacherAvailabilitiesState copyWith({
    bool? isLoading,
    List<Availability>? availabilities,
    String? error,
  }) {
    return TeacherAvailabilitiesState(
      isLoading: isLoading ?? this.isLoading,
      availabilities: availabilities ?? this.availabilities,
      error: error,
    );
  }
}

// Notifier pour les disponibilités de l'enseignant
class TeacherAvailabilitiesNotifier extends StateNotifier<TeacherAvailabilitiesState> {
  final TeacherService _teacherService;

  TeacherAvailabilitiesNotifier(this._teacherService) : super(TeacherAvailabilitiesState());

  Future<void> loadAvailabilities() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final availabilities = await _teacherService.getTeacherAvailabilities();
      state = state.copyWith(
        isLoading: false,
        availabilities: availabilities,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<void> createAvailability({
    required DateTime startTime,
    required DateTime endTime,
    required String dayOfWeek,
    String? notes,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final newAvailability = await _teacherService.createAvailability(
        startTime: startTime,
        endTime: endTime,
        dayOfWeek: dayOfWeek,
        notes: notes,
      );
      
      final updatedAvailabilities = [newAvailability, ...state.availabilities];
      state = state.copyWith(
        isLoading: false,
        availabilities: updatedAvailabilities,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<void> updateAvailability({
    required int availabilityId,
    DateTime? startTime,
    DateTime? endTime,
    String? dayOfWeek,
    bool? isAvailable,
    String? notes,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final updatedAvailability = await _teacherService.updateAvailability(
        availabilityId: availabilityId,
        startTime: startTime,
        endTime: endTime,
        dayOfWeek: dayOfWeek,
        isAvailable: isAvailable,
        notes: notes,
      );
      
      final updatedAvailabilities = state.availabilities.map((availability) {
        return availability.id == availabilityId ? updatedAvailability : availability;
      }).toList();
      
      state = state.copyWith(
        isLoading: false,
        availabilities: updatedAvailabilities,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<void> deleteAvailability(int availabilityId) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final success = await _teacherService.deleteAvailability(availabilityId);
      if (success) {
        final updatedAvailabilities = state.availabilities.where((availability) => availability.id != availabilityId).toList();
        state = state.copyWith(
          isLoading: false,
          availabilities: updatedAvailabilities,
        );
      } else {
        throw Exception('Erreur lors de la suppression de la disponibilité');
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Provider pour les disponibilités de l'enseignant
final teacherAvailabilitiesProvider = StateNotifierProvider<TeacherAvailabilitiesNotifier, TeacherAvailabilitiesState>((ref) {
  final teacherService = ref.watch(teacherServiceProvider);
  return TeacherAvailabilitiesNotifier(teacherService);
});

// État pour les statistiques de l'enseignant
class TeacherStatsState {
  final bool isLoading;
  final Map<String, dynamic>? stats;
  final String? error;

  TeacherStatsState({
    this.isLoading = false,
    this.stats,
    this.error,
  });

  TeacherStatsState copyWith({
    bool? isLoading,
    Map<String, dynamic>? stats,
    String? error,
  }) {
    return TeacherStatsState(
      isLoading: isLoading ?? this.isLoading,
      stats: stats ?? this.stats,
      error: error,
    );
  }
}

// Notifier pour les statistiques de l'enseignant
class TeacherStatsNotifier extends StateNotifier<TeacherStatsState> {
  final TeacherService _teacherService;

  TeacherStatsNotifier(this._teacherService) : super(TeacherStatsState());

  Future<void> loadStats() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final stats = await _teacherService.getTeacherStats();
      state = state.copyWith(
        isLoading: false,
        stats: stats,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Provider pour les statistiques de l'enseignant
final teacherStatsProvider = StateNotifierProvider<TeacherStatsNotifier, TeacherStatsState>((ref) {
  final teacherService = ref.watch(teacherServiceProvider);
  return TeacherStatsNotifier(teacherService);
});

// État pour les étudiants de l'enseignant
class TeacherStudentsState {
  final bool isLoading;
  final List<User> students;
  final String? error;

  TeacherStudentsState({
    this.isLoading = false,
    this.students = const [],
    this.error,
  });

  TeacherStudentsState copyWith({
    bool? isLoading,
    List<User>? students,
    String? error,
  }) {
    return TeacherStudentsState(
      isLoading: isLoading ?? this.isLoading,
      students: students ?? this.students,
      error: error,
    );
  }
}

// Notifier pour les étudiants de l'enseignant
class TeacherStudentsNotifier extends StateNotifier<TeacherStudentsState> {
  final TeacherService _teacherService;

  TeacherStudentsNotifier(this._teacherService) : super(TeacherStudentsState());

  Future<void> loadStudents() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final students = await _teacherService.getTeacherStudents();
      state = state.copyWith(
        isLoading: false,
        students: students,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Provider pour les étudiants de l'enseignant
final teacherStudentsProvider = StateNotifierProvider<TeacherStudentsNotifier, TeacherStudentsState>((ref) {
  final teacherService = ref.watch(teacherServiceProvider);
  return TeacherStudentsNotifier(teacherService);
});

// État combiné pour l'enseignant
class TeacherState {
  final TeacherLessonsState lessons;
  final TeacherAvailabilitiesState availabilities;
  final TeacherStudentsState students;
  final TeacherStatsState stats;

  TeacherState({
    required this.lessons,
    required this.availabilities,
    required this.students,
    required this.stats,
  });

  TeacherState copyWith({
    TeacherLessonsState? lessons,
    TeacherAvailabilitiesState? availabilities,
    TeacherStudentsState? students,
    TeacherStatsState? stats,
  }) {
    return TeacherState(
      lessons: lessons ?? this.lessons,
      availabilities: availabilities ?? this.availabilities,
      students: students ?? this.students,
      stats: stats ?? this.stats,
    );
  }
}

// Notifier combiné pour l'enseignant
class TeacherNotifier extends StateNotifier<TeacherState> {
  final TeacherService _teacherService;

  TeacherNotifier(this._teacherService) : super(TeacherState(
    lessons: TeacherLessonsState(),
    availabilities: TeacherAvailabilitiesState(),
    students: TeacherStudentsState(),
    stats: TeacherStatsState(),
  ));

  // Méthodes pour les leçons
  Future<void> loadLessons() async {
    final notifier = TeacherLessonsNotifier(_teacherService);
    await notifier.loadLessons();
    state = state.copyWith(lessons: notifier.state);
  }

  Future<void> createLesson({
    required String title,
    required String description,
    required DateTime startTime,
    required DateTime endTime,
    String? location,
    double? price,
    String? notes,
  }) async {
    final notifier = TeacherLessonsNotifier(_teacherService);
    await notifier.createLesson(
      title: title,
      description: description,
      startTime: startTime,
      endTime: endTime,
      location: location,
      price: price,
      notes: notes,
    );
    state = state.copyWith(lessons: notifier.state);
  }

  Future<void> updateLesson({
    required int lessonId,
    required String title,
    required String description,
    required DateTime startTime,
    required DateTime endTime,
    String? location,
    double? price,
    String? notes,
  }) async {
    final notifier = TeacherLessonsNotifier(_teacherService);
    await notifier.updateLesson(
      lessonId: lessonId,
      title: title,
      description: description,
      startTime: startTime,
      endTime: endTime,
      location: location,
      price: price,
      notes: notes,
    );
    state = state.copyWith(lessons: notifier.state);
  }

  Future<void> deleteLesson(int lessonId) async {
    final notifier = TeacherLessonsNotifier(_teacherService);
    await notifier.deleteLesson(lessonId);
    state = state.copyWith(lessons: notifier.state);
  }

  // Méthodes pour les disponibilités
  Future<void> loadAvailabilities() async {
    final notifier = TeacherAvailabilitiesNotifier(_teacherService);
    await notifier.loadAvailabilities();
    state = state.copyWith(availabilities: notifier.state);
  }

  Future<void> addAvailability({
    required DateTime startTime,
    required DateTime endTime,
    String? notes,
  }) async {
    final notifier = TeacherAvailabilitiesNotifier(_teacherService);
    await notifier.createAvailability(
      startTime: startTime,
      endTime: endTime,
      dayOfWeek: 'Monday', // Valeur par défaut
      notes: notes,
    );
    state = state.copyWith(availabilities: notifier.state);
  }

  Future<void> updateAvailability({
    required int availabilityId,
    required DateTime startTime,
    required DateTime endTime,
    String? notes,
  }) async {
    final notifier = TeacherAvailabilitiesNotifier(_teacherService);
    await notifier.updateAvailability(
      availabilityId: availabilityId,
      startTime: startTime,
      endTime: endTime,
      notes: notes,
    );
    state = state.copyWith(availabilities: notifier.state);
  }

  Future<void> deleteAvailability(int availabilityId) async {
    final notifier = TeacherAvailabilitiesNotifier(_teacherService);
    await notifier.deleteAvailability(availabilityId);
    state = state.copyWith(availabilities: notifier.state);
  }

  // Méthodes pour les étudiants
  Future<void> loadStudents() async {
    final notifier = TeacherStudentsNotifier(_teacherService);
    await notifier.loadStudents();
    state = state.copyWith(students: notifier.state);
  }

  // Méthodes pour les statistiques
  Future<void> loadStats() async {
    final notifier = TeacherStatsNotifier(_teacherService);
    await notifier.loadStats();
    state = state.copyWith(stats: notifier.state);
  }
}

// Provider principal pour l'enseignant
final teacherProvider = StateNotifierProvider<TeacherNotifier, TeacherState>((ref) {
  final teacherService = ref.watch(teacherServiceProvider);
  return TeacherNotifier(teacherService);
});

