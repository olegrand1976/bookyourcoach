import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../services/student_service.dart';
import '../models/lesson.dart';
import '../models/booking.dart';
import '../models/user.dart';
import '../models/student_preferences.dart';

// Provider pour le service élève
final studentServiceProvider = Provider<StudentService>((ref) {
  return StudentService();
});

// État pour les cours disponibles
class AvailableLessonsState {
  final bool isLoading;
  final List<Lesson> lessons;
  final String? error;
  final String? filterSubject;
  final DateTime? filterDate;

  AvailableLessonsState({
    this.isLoading = false,
    this.lessons = const [],
    this.error,
    this.filterSubject,
    this.filterDate,
  });

  AvailableLessonsState copyWith({
    bool? isLoading,
    List<Lesson>? lessons,
    String? error,
    String? filterSubject,
    DateTime? filterDate,
  }) {
    return AvailableLessonsState(
      isLoading: isLoading ?? this.isLoading,
      lessons: lessons ?? this.lessons,
      error: error,
      filterSubject: filterSubject ?? this.filterSubject,
      filterDate: filterDate ?? this.filterDate,
    );
  }
}

// Notifier pour les cours disponibles
class AvailableLessonsNotifier extends StateNotifier<AvailableLessonsState> {
  final StudentService _studentService;

  AvailableLessonsNotifier(this._studentService) : super(AvailableLessonsState());

  Future<void> loadAvailableLessons({String? subject, DateTime? date}) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final lessons = await _studentService.getAvailableLessons(
        subject: subject,
        date: date,
      );
      state = state.copyWith(
        isLoading: false,
        lessons: lessons,
        filterSubject: subject,
        filterDate: date,
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

// Provider pour les cours disponibles
final availableLessonsProvider = StateNotifierProvider<AvailableLessonsNotifier, AvailableLessonsState>((ref) {
  final studentService = ref.watch(studentServiceProvider);
  return AvailableLessonsNotifier(studentService);
});

// État pour les réservations de l'élève
class StudentBookingsState {
  final bool isLoading;
  final List<Booking> bookings;
  final String? error;
  final String? filterStatus;

  StudentBookingsState({
    this.isLoading = false,
    this.bookings = const [],
    this.error,
    this.filterStatus,
  });

  StudentBookingsState copyWith({
    bool? isLoading,
    List<Booking>? bookings,
    String? error,
    String? filterStatus,
  }) {
    return StudentBookingsState(
      isLoading: isLoading ?? this.isLoading,
      bookings: bookings ?? this.bookings,
      error: error,
      filterStatus: filterStatus ?? this.filterStatus,
    );
  }
}

// Notifier pour les réservations de l'élève
class StudentBookingsNotifier extends StateNotifier<StudentBookingsState> {
  final StudentService _studentService;

  StudentBookingsNotifier(this._studentService) : super(StudentBookingsState());

  Future<void> loadBookings({String? status}) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final bookings = await _studentService.getStudentBookings(status: status);
      state = state.copyWith(
        isLoading: false,
        bookings: bookings,
        filterStatus: status,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<void> bookLesson({
    required int lessonId,
    String? notes,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final newBooking = await _studentService.bookLesson(
        lessonId: lessonId,
        notes: notes,
      );
      
      final updatedBookings = [newBooking, ...state.bookings];
      state = state.copyWith(
        isLoading: false,
        bookings: updatedBookings,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<void> cancelBooking(int bookingId) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final success = await _studentService.cancelBooking(bookingId);
      if (success) {
        final updatedBookings = state.bookings.map((booking) {
          if (booking.id == bookingId) {
            return booking.copyWith(status: 'cancelled');
          }
          return booking;
        }).toList();
        
        state = state.copyWith(
          isLoading: false,
          bookings: updatedBookings,
        );
      } else {
        throw Exception('Erreur lors de l\'annulation de la réservation');
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

// Provider pour les réservations de l'élève
final studentBookingsProvider = StateNotifierProvider<StudentBookingsNotifier, StudentBookingsState>((ref) {
  final studentService = ref.watch(studentServiceProvider);
  return StudentBookingsNotifier(studentService);
});

// État pour les enseignants disponibles
class AvailableTeachersState {
  final bool isLoading;
  final List<User> teachers;
  final String? error;
  final String? filterSubject;

  AvailableTeachersState({
    this.isLoading = false,
    this.teachers = const [],
    this.error,
    this.filterSubject,
  });

  AvailableTeachersState copyWith({
    bool? isLoading,
    List<User>? teachers,
    String? error,
    String? filterSubject,
  }) {
    return AvailableTeachersState(
      isLoading: isLoading ?? this.isLoading,
      teachers: teachers ?? this.teachers,
      error: error,
      filterSubject: filterSubject ?? this.filterSubject,
    );
  }
}

// Notifier pour les enseignants disponibles
class AvailableTeachersNotifier extends StateNotifier<AvailableTeachersState> {
  final StudentService _studentService;

  AvailableTeachersNotifier(this._studentService) : super(AvailableTeachersState());

  Future<void> loadAvailableTeachers({String? subject}) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final teachers = await _studentService.getAvailableTeachers(subject: subject);
      state = state.copyWith(
        isLoading: false,
        teachers: teachers,
        filterSubject: subject,
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

// Provider pour les enseignants disponibles
final availableTeachersProvider = StateNotifierProvider<AvailableTeachersNotifier, AvailableTeachersState>((ref) {
  final studentService = ref.watch(studentServiceProvider);
  return AvailableTeachersNotifier(studentService);
});

// État pour les statistiques de l'élève
class StudentStatsState {
  final bool isLoading;
  final Map<String, dynamic>? stats;
  final String? error;

  StudentStatsState({
    this.isLoading = false,
    this.stats,
    this.error,
  });

  StudentStatsState copyWith({
    bool? isLoading,
    Map<String, dynamic>? stats,
    String? error,
  }) {
    return StudentStatsState(
      isLoading: isLoading ?? this.isLoading,
      stats: stats ?? this.stats,
      error: error,
    );
  }
}

// Notifier pour les statistiques de l'élève
class StudentStatsNotifier extends StateNotifier<StudentStatsState> {
  final StudentService _studentService;

  StudentStatsNotifier(this._studentService) : super(StudentStatsState());

  Future<void> loadStats() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final stats = await _studentService.getStudentStats();
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

// Provider pour les statistiques de l'élève
final studentStatsProvider = StateNotifierProvider<StudentStatsNotifier, StudentStatsState>((ref) {
  final studentService = ref.watch(studentServiceProvider);
  return StudentStatsNotifier(studentService);
});

// État pour les enseignants
class TeachersState {
  final bool isLoading;
  final List<User> teachers;
  final String? error;

  TeachersState({
    this.isLoading = false,
    this.teachers = const [],
    this.error,
  });

  TeachersState copyWith({
    bool? isLoading,
    List<User>? teachers,
    String? error,
  }) {
    return TeachersState(
      isLoading: isLoading ?? this.isLoading,
      teachers: teachers ?? this.teachers,
      error: error,
    );
  }
}

// Notifier pour les enseignants
class TeachersNotifier extends StateNotifier<TeachersState> {
  final StudentService _studentService;

  TeachersNotifier(this._studentService) : super(TeachersState());

  Future<void> loadTeachers() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final teachers = await _studentService.getTeachers();
      state = state.copyWith(
        isLoading: false,
        teachers: teachers,
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

// Provider pour les enseignants
final teachersProvider = StateNotifierProvider<TeachersNotifier, TeachersState>((ref) {
  final studentService = ref.watch(studentServiceProvider);
  return TeachersNotifier(studentService);
});

// État pour les enseignants favoris
class FavoriteTeachersState {
  final bool isLoading;
  final List<User> favoriteTeachers;
  final String? error;

  FavoriteTeachersState({
    this.isLoading = false,
    this.favoriteTeachers = const [],
    this.error,
  });

  FavoriteTeachersState copyWith({
    bool? isLoading,
    List<User>? favoriteTeachers,
    String? error,
  }) {
    return FavoriteTeachersState(
      isLoading: isLoading ?? this.isLoading,
      favoriteTeachers: favoriteTeachers ?? this.favoriteTeachers,
      error: error,
    );
  }
}

// Notifier pour les enseignants favoris
class FavoriteTeachersNotifier extends StateNotifier<FavoriteTeachersState> {
  final StudentService _studentService;

  FavoriteTeachersNotifier(this._studentService) : super(FavoriteTeachersState());

  Future<void> loadFavoriteTeachers() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final teachers = await _studentService.getFavoriteTeachers();
      state = state.copyWith(
        isLoading: false,
        favoriteTeachers: teachers,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<void> toggleFavoriteTeacher(int teacherId) async {
    try {
      final success = await _studentService.toggleFavoriteTeacher(teacherId);
      if (success) {
        // Recharger la liste des favoris
        await loadFavoriteTeachers();
      }
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Provider pour les enseignants favoris
final favoriteTeachersProvider = StateNotifierProvider<FavoriteTeachersNotifier, FavoriteTeachersState>((ref) {
  final studentService = ref.watch(studentServiceProvider);
  return FavoriteTeachersNotifier(studentService);
});

// État pour l'historique des cours
class LessonHistoryState {
  final bool isLoading;
  final List<Booking> history;
  final String? error;

  LessonHistoryState({
    this.isLoading = false,
    this.history = const [],
    this.error,
  });

  LessonHistoryState copyWith({
    bool? isLoading,
    List<Booking>? history,
    String? error,
  }) {
    return LessonHistoryState(
      isLoading: isLoading ?? this.isLoading,
      history: history ?? this.history,
      error: error,
    );
  }
}

// État pour les préférences de l'étudiant
class StudentPreferencesState {
  final bool isLoading;
  final StudentPreferences? preferences;
  final String? error;

  StudentPreferencesState({
    this.isLoading = false,
    this.preferences,
    this.error,
  });

  StudentPreferencesState copyWith({
    bool? isLoading,
    StudentPreferences? preferences,
    String? error,
  }) {
    return StudentPreferencesState(
      isLoading: isLoading ?? this.isLoading,
      preferences: preferences ?? this.preferences,
      error: error,
    );
  }
}

// Notifier pour l'historique des cours
class LessonHistoryNotifier extends StateNotifier<LessonHistoryState> {
  final StudentService _studentService;

  LessonHistoryNotifier(this._studentService) : super(LessonHistoryState());

  Future<void> loadHistory() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final history = await _studentService.getLessonHistory();
      state = state.copyWith(
        isLoading: false,
        history: history,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<void> rateLesson({
    required int bookingId,
    required int rating,
    String? review,
  }) async {
    try {
      final success = await _studentService.rateLesson(
        bookingId: bookingId,
        rating: rating,
        review: review,
      );
      if (success) {
        // Recharger l'historique
        await loadHistory();
      }
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Provider pour l'historique des cours
final lessonHistoryProvider = StateNotifierProvider<LessonHistoryNotifier, LessonHistoryState>((ref) {
  final studentService = ref.watch(studentServiceProvider);
  return LessonHistoryNotifier(studentService);
});

// Notifier pour les préférences de l'étudiant
class StudentPreferencesNotifier extends StateNotifier<StudentPreferencesState> {
  final StudentService _studentService;

  StudentPreferencesNotifier(this._studentService) : super(StudentPreferencesState());

  Future<void> loadPreferences() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final preferencesData = await _studentService.getStudentPreferences();
      // Créer un objet StudentPreferences à partir des données
      final preferences = StudentPreferences(
        id: preferencesData['id'] ?? 0,
        studentId: preferencesData['student_id'] ?? 0,
        preferredDisciplines: List<String>.from(preferencesData['preferred_disciplines'] ?? []),
        preferredLevels: List<String>.from(preferencesData['preferred_levels'] ?? []),
        preferredFormats: List<String>.from(preferencesData['preferred_formats'] ?? []),
        location: preferencesData['location'],
        maxPrice: preferencesData['max_price']?.toDouble(),
        maxDistance: preferencesData['max_distance'],
        notificationsEnabled: preferencesData['notifications_enabled'] ?? true,
        createdAt: DateTime.parse(preferencesData['created_at'] ?? DateTime.now().toIso8601String()),
        updatedAt: DateTime.parse(preferencesData['updated_at'] ?? DateTime.now().toIso8601String()),
      );
      state = state.copyWith(
        isLoading: false,
        preferences: preferences,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<void> savePreferences({
    required List<String> disciplines,
    required List<String> levels,
    required List<String> formats,
    String? location,
    double? maxPrice,
    int? maxDistance,
    bool? notificationsEnabled,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final preferencesData = await _studentService.saveStudentPreferences(
        disciplines: disciplines,
        levels: levels,
        formats: formats,
        location: location,
        maxPrice: maxPrice,
        maxDistance: maxDistance,
        notificationsEnabled: notificationsEnabled,
      );
      // Créer un objet StudentPreferences à partir des données
      final preferences = StudentPreferences(
        id: preferencesData['id'] ?? 0,
        studentId: preferencesData['student_id'] ?? 0,
        preferredDisciplines: List<String>.from(preferencesData['preferred_disciplines'] ?? []),
        preferredLevels: List<String>.from(preferencesData['preferred_levels'] ?? []),
        preferredFormats: List<String>.from(preferencesData['preferred_formats'] ?? []),
        location: preferencesData['location'],
        maxPrice: preferencesData['max_price']?.toDouble(),
        maxDistance: preferencesData['max_distance'],
        notificationsEnabled: preferencesData['notifications_enabled'] ?? true,
        createdAt: DateTime.parse(preferencesData['created_at'] ?? DateTime.now().toIso8601String()),
        updatedAt: DateTime.parse(preferencesData['updated_at'] ?? DateTime.now().toIso8601String()),
      );
      state = state.copyWith(
        isLoading: false,
        preferences: preferences,
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

// Provider pour les préférences de l'étudiant
final studentPreferencesProvider = StateNotifierProvider<StudentPreferencesNotifier, StudentPreferencesState>((ref) {
  final studentService = ref.watch(studentServiceProvider);
  return StudentPreferencesNotifier(studentService);
});

// État combiné pour l'étudiant
class StudentState {
  final AvailableLessonsState availableLessons;
  final StudentBookingsState bookings;
  final TeachersState teachers;
  final FavoriteTeachersState favoriteTeachers;
  final LessonHistoryState history;
  final StudentPreferencesState preferences;

  StudentState({
    required this.availableLessons,
    required this.bookings,
    required this.teachers,
    required this.favoriteTeachers,
    required this.history,
    required this.preferences,
  });

  StudentState copyWith({
    AvailableLessonsState? availableLessons,
    StudentBookingsState? bookings,
    TeachersState? teachers,
    FavoriteTeachersState? favoriteTeachers,
    LessonHistoryState? history,
    StudentPreferencesState? preferences,
  }) {
    return StudentState(
      availableLessons: availableLessons ?? this.availableLessons,
      bookings: bookings ?? this.bookings,
      teachers: teachers ?? this.teachers,
      favoriteTeachers: favoriteTeachers ?? this.favoriteTeachers,
      history: history ?? this.history,
      preferences: preferences ?? this.preferences,
    );
  }
}

// Notifier combiné pour l'étudiant
class StudentNotifier extends StateNotifier<StudentState> {
  final StudentService _studentService;

  StudentNotifier(this._studentService) : super(StudentState(
    availableLessons: AvailableLessonsState(),
    bookings: StudentBookingsState(),
    teachers: TeachersState(),
    favoriteTeachers: FavoriteTeachersState(),
    history: LessonHistoryState(),
    preferences: StudentPreferencesState(),
  ));

  // Méthodes pour les leçons disponibles
  Future<void> loadAvailableLessons({String? subject, DateTime? date}) async {
    final notifier = AvailableLessonsNotifier(_studentService);
    await notifier.loadAvailableLessons(subject: subject, date: date);
    state = state.copyWith(availableLessons: notifier.state);
  }

  Future<void> bookLesson(int lessonId) async {
    final notifier = StudentBookingsNotifier(_studentService);
    await notifier.bookLesson(lessonId: lessonId);
    state = state.copyWith(bookings: notifier.state);
  }

  // Méthodes pour les réservations
  Future<void> loadBookings() async {
    final notifier = StudentBookingsNotifier(_studentService);
    await notifier.loadBookings();
    state = state.copyWith(bookings: notifier.state);
  }

  Future<void> cancelBooking(int bookingId) async {
    final notifier = StudentBookingsNotifier(_studentService);
    await notifier.cancelBooking(bookingId);
    state = state.copyWith(bookings: notifier.state);
  }

  // Méthodes pour les enseignants
  Future<void> loadTeachers() async {
    final notifier = TeachersNotifier(_studentService);
    await notifier.loadTeachers();
    state = state.copyWith(teachers: notifier.state);
  }

  Future<void> loadFavoriteTeachers() async {
    final notifier = FavoriteTeachersNotifier(_studentService);
    await notifier.loadFavoriteTeachers();
    state = state.copyWith(favoriteTeachers: notifier.state);
  }

  Future<void> toggleFavoriteTeacher(int teacherId) async {
    final notifier = FavoriteTeachersNotifier(_studentService);
    await notifier.toggleFavoriteTeacher(teacherId);
    state = state.copyWith(favoriteTeachers: notifier.state);
  }

  // Méthodes pour l'historique
  Future<void> loadHistory() async {
    final notifier = LessonHistoryNotifier(_studentService);
    await notifier.loadHistory();
    state = state.copyWith(history: notifier.state);
  }

  Future<void> rateLesson({
    required int bookingId,
    required int rating,
    String? review,
  }) async {
    final notifier = LessonHistoryNotifier(_studentService);
    await notifier.rateLesson(bookingId: bookingId, rating: rating, review: review);
    state = state.copyWith(history: notifier.state);
  }

  // Méthodes pour les préférences
  Future<void> loadPreferences() async {
    final notifier = StudentPreferencesNotifier(_studentService);
    await notifier.loadPreferences();
    state = state.copyWith(preferences: notifier.state);
  }

  Future<void> savePreferences({
    required List<String> disciplines,
    required List<String> levels,
    required List<String> formats,
    String? location,
    double? maxPrice,
    int? maxDistance,
    bool? notificationsEnabled,
  }) async {
    final notifier = StudentPreferencesNotifier(_studentService);
    await notifier.savePreferences(
      disciplines: disciplines,
      levels: levels,
      formats: formats,
      location: location,
      maxPrice: maxPrice,
      maxDistance: maxDistance,
      notificationsEnabled: notificationsEnabled,
    );
    state = state.copyWith(preferences: notifier.state);
  }
}

// Provider principal pour l'étudiant
final studentProvider = StateNotifierProvider<StudentNotifier, StudentState>((ref) {
  final studentService = ref.watch(studentServiceProvider);
  return StudentNotifier(studentService);
});
