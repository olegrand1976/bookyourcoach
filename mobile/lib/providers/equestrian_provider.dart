import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/equestrian_models.dart';
import '../services/equestrian_service.dart';

// Provider du service
final equestrianServiceProvider = Provider<EquestrianService>((ref) {
  return EquestrianService();
});

// État des disciplines
class DisciplinesState {
  final List<EquestrianDiscipline> disciplines;
  final bool isLoading;
  final String? error;

  DisciplinesState({
    this.disciplines = const [],
    this.isLoading = false,
    this.error,
  });

  DisciplinesState copyWith({
    List<EquestrianDiscipline>? disciplines,
    bool? isLoading,
    String? error,
  }) {
    return DisciplinesState(
      disciplines: disciplines ?? this.disciplines,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class DisciplinesNotifier extends StateNotifier<DisciplinesState> {
  final EquestrianService _service;

  DisciplinesNotifier(this._service) : super(DisciplinesState());

  Future<void> loadDisciplines() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final disciplines = await _service.getDisciplines();
      state = state.copyWith(disciplines: disciplines, isLoading: false);
    } catch (e) {
      state = state.copyWith(error: e.toString(), isLoading: false);
    }
  }
}

final disciplinesProvider = StateNotifierProvider<DisciplinesNotifier, DisciplinesState>((ref) {
  return DisciplinesNotifier(ref.read(equestrianServiceProvider));
});

// État des disciplines des étudiants
class StudentDisciplinesState {
  final Map<int, List<StudentDiscipline>> studentDisciplines;
  final bool isLoading;
  final String? error;

  StudentDisciplinesState({
    this.studentDisciplines = const {},
    this.isLoading = false,
    this.error,
  });

  StudentDisciplinesState copyWith({
    Map<int, List<StudentDiscipline>>? studentDisciplines,
    bool? isLoading,
    String? error,
  }) {
    return StudentDisciplinesState(
      studentDisciplines: studentDisciplines ?? this.studentDisciplines,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }

  List<StudentDiscipline> getDisciplinesForStudent(int studentId) {
    return studentDisciplines[studentId] ?? [];
  }
}

class StudentDisciplinesNotifier extends StateNotifier<StudentDisciplinesState> {
  final EquestrianService _service;

  StudentDisciplinesNotifier(this._service) : super(StudentDisciplinesState());

  Future<void> loadStudentDisciplines(int studentId) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final disciplines = await _service.getStudentDisciplines(studentId);
      final updatedMap = Map<int, List<StudentDiscipline>>.from(state.studentDisciplines);
      updatedMap[studentId] = disciplines;
      state = state.copyWith(studentDisciplines: updatedMap, isLoading: false);
    } catch (e) {
      state = state.copyWith(error: e.toString(), isLoading: false);
    }
  }

  Future<void> addStudentDiscipline(int studentId, Map<String, dynamic> data) async {
    try {
      final discipline = await _service.addStudentDiscipline(studentId, data);
      final currentList = state.studentDisciplines[studentId] ?? [];
      final updatedList = [...currentList, discipline];
      final updatedMap = Map<int, List<StudentDiscipline>>.from(state.studentDisciplines);
      updatedMap[studentId] = updatedList;
      state = state.copyWith(studentDisciplines: updatedMap);
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> updateStudentDiscipline(int studentId, int disciplineId, Map<String, dynamic> data) async {
    try {
      final updatedDiscipline = await _service.updateStudentDiscipline(studentId, disciplineId, data);
      final currentList = state.studentDisciplines[studentId] ?? [];
      final updatedList = currentList.map((d) => d.id == disciplineId ? updatedDiscipline : d).toList();
      final updatedMap = Map<int, List<StudentDiscipline>>.from(state.studentDisciplines);
      updatedMap[studentId] = updatedList;
      state = state.copyWith(studentDisciplines: updatedMap);
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> removeStudentDiscipline(int studentId, int disciplineId) async {
    try {
      await _service.removeStudentDiscipline(studentId, disciplineId);
      final currentList = state.studentDisciplines[studentId] ?? [];
      final updatedList = currentList.where((d) => d.id != disciplineId).toList();
      final updatedMap = Map<int, List<StudentDiscipline>>.from(state.studentDisciplines);
      updatedMap[studentId] = updatedList;
      state = state.copyWith(studentDisciplines: updatedMap);
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }
}

final studentDisciplinesProvider = StateNotifierProvider<StudentDisciplinesNotifier, StudentDisciplinesState>((ref) {
  return StudentDisciplinesNotifier(ref.read(equestrianServiceProvider));
});

// Provider pour les disciplines d'un étudiant spécifique
final studentDisciplinesFamilyProvider = FutureProvider.family<List<StudentDiscipline>, int>((ref, studentId) async {
  final service = ref.read(equestrianServiceProvider);
  return await service.getStudentDisciplines(studentId);
});

// État des métriques de performance
class PerformanceMetricsState {
  final List<PerformanceMetrics> metrics;
  final bool isLoading;
  final String? error;

  PerformanceMetricsState({
    this.metrics = const [],
    this.isLoading = false,
    this.error,
  });

  PerformanceMetricsState copyWith({
    List<PerformanceMetrics>? metrics,
    bool? isLoading,
    String? error,
  }) {
    return PerformanceMetricsState(
      metrics: metrics ?? this.metrics,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class PerformanceMetricsNotifier extends StateNotifier<PerformanceMetricsState> {
  final EquestrianService _service;

  PerformanceMetricsNotifier(this._service) : super(PerformanceMetricsState());

  Future<void> loadMetrics({
    int? studentId,
    int? disciplineId,
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final metrics = await _service.getPerformanceMetrics(
        studentId: studentId,
        disciplineId: disciplineId,
        startDate: startDate,
        endDate: endDate,
      );
      state = state.copyWith(metrics: metrics, isLoading: false);
    } catch (e) {
      state = state.copyWith(error: e.toString(), isLoading: false);
    }
  }

  Future<void> addMetrics(Map<String, dynamic> data) async {
    try {
      final metric = await _service.addPerformanceMetrics(data);
      final updatedList = [...state.metrics, metric];
      state = state.copyWith(metrics: updatedList);
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> updateMetrics(int id, Map<String, dynamic> data) async {
    try {
      final updatedMetric = await _service.updatePerformanceMetrics(id, data);
      final updatedList = state.metrics.map((m) => m.id == id ? updatedMetric : m).toList();
      state = state.copyWith(metrics: updatedList);
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }
}

final performanceMetricsProvider = StateNotifierProvider<PerformanceMetricsNotifier, PerformanceMetricsState>((ref) {
  return PerformanceMetricsNotifier(ref.read(equestrianServiceProvider));
});

// État des tableaux de bord
class DashboardState {
  final DashboardStats? stats;
  final bool isLoading;
  final String? error;

  DashboardState({
    this.stats,
    this.isLoading = false,
    this.error,
  });

  DashboardState copyWith({
    DashboardStats? stats,
    bool? isLoading,
    String? error,
  }) {
    return DashboardState(
      stats: stats ?? this.stats,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class DashboardNotifier extends StateNotifier<DashboardState> {
  final EquestrianService _service;

  DashboardNotifier(this._service) : super(DashboardState());

  Future<void> loadDashboardStats({
    required int userId,
    required String userType,
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final stats = await _service.getDashboardStats(
        userId: userId,
        userType: userType,
        startDate: startDate,
        endDate: endDate,
      );
      state = state.copyWith(stats: stats, isLoading: false);
    } catch (e) {
      state = state.copyWith(error: e.toString(), isLoading: false);
    }
  }
}

final dashboardProvider = StateNotifierProvider<DashboardNotifier, DashboardState>((ref) {
  return DashboardNotifier(ref.read(equestrianServiceProvider));
});

// État des objectifs d'entraînement
class TrainingGoalsState {
  final List<TrainingGoal> goals;
  final bool isLoading;
  final String? error;

  TrainingGoalsState({
    this.goals = const [],
    this.isLoading = false,
    this.error,
  });

  TrainingGoalsState copyWith({
    List<TrainingGoal>? goals,
    bool? isLoading,
    String? error,
  }) {
    return TrainingGoalsState(
      goals: goals ?? this.goals,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class TrainingGoalsNotifier extends StateNotifier<TrainingGoalsState> {
  final EquestrianService _service;

  TrainingGoalsNotifier(this._service) : super(TrainingGoalsState());

  Future<void> loadGoals({
    int? studentId,
    int? disciplineId,
    String? status,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final goals = await _service.getTrainingGoals(
        studentId: studentId,
        disciplineId: disciplineId,
        status: status,
      );
      state = state.copyWith(goals: goals, isLoading: false);
    } catch (e) {
      state = state.copyWith(error: e.toString(), isLoading: false);
    }
  }

  Future<void> addGoal(Map<String, dynamic> data) async {
    try {
      final goal = await _service.addTrainingGoal(data);
      final updatedList = [...state.goals, goal];
      state = state.copyWith(goals: updatedList);
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> updateGoal(int id, Map<String, dynamic> data) async {
    try {
      final updatedGoal = await _service.updateTrainingGoal(id, data);
      final updatedList = state.goals.map((g) => g.id == id ? updatedGoal : g).toList();
      state = state.copyWith(goals: updatedList);
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> deleteGoal(int id) async {
    try {
      await _service.deleteTrainingGoal(id);
      final updatedList = state.goals.where((g) => g.id != id).toList();
      state = state.copyWith(goals: updatedList);
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }
}

final trainingGoalsProvider = StateNotifierProvider<TrainingGoalsNotifier, TrainingGoalsState>((ref) {
  return TrainingGoalsNotifier(ref.read(equestrianServiceProvider));
});
