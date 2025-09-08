import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/discipline.dart';
import '../services/preferences_service.dart';
import 'dio_provider.dart';
import 'auth_service_provider.dart';

// Provider pour le service des préférences
final preferencesServiceProvider = Provider<PreferencesService>((ref) {
  final dio = ref.watch(dioProvider);
  final authService = ref.watch(authServiceProvider);
  return PreferencesService(dio, authService);
});

// État des disciplines
class DisciplinesState {
  final List<Discipline> disciplines;
  final bool isLoading;
  final String? error;

  DisciplinesState({
    this.disciplines = const [],
    this.isLoading = false,
    this.error,
  });

  DisciplinesState copyWith({
    List<Discipline>? disciplines,
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

// Notifier pour les disciplines
class DisciplinesNotifier extends StateNotifier<DisciplinesState> {
  final PreferencesService _preferencesService;

  DisciplinesNotifier(this._preferencesService) : super(DisciplinesState());

  Future<void> loadDisciplines() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final disciplines = await _preferencesService.getDisciplines();
      state = state.copyWith(
        disciplines: disciplines,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }
}

// Provider pour les disciplines
final disciplinesProvider = StateNotifierProvider<DisciplinesNotifier, DisciplinesState>((ref) {
  final preferencesService = ref.watch(preferencesServiceProvider);
  return DisciplinesNotifier(preferencesService);
});

// État des préférences étudiant
class StudentPreferencesState {
  final List<StudentPreference> preferences;
  final bool isLoading;
  final String? error;
  final bool isSaving;

  StudentPreferencesState({
    this.preferences = const [],
    this.isLoading = false,
    this.error,
    this.isSaving = false,
  });

  StudentPreferencesState copyWith({
    List<StudentPreference>? preferences,
    bool? isLoading,
    String? error,
    bool? isSaving,
  }) {
    return StudentPreferencesState(
      preferences: preferences ?? this.preferences,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      isSaving: isSaving ?? this.isSaving,
    );
  }

  // Méthodes utilitaires
  List<StudentPreference> getPreferencesForDiscipline(int disciplineId) {
    return preferences.where((p) => p.disciplineId == disciplineId).toList();
  }

  bool hasPreferenceForDiscipline(int disciplineId) {
    return preferences.any((p) => p.disciplineId == disciplineId);
  }

  bool hasPreferenceForCourseType(int disciplineId, int courseTypeId) {
    return preferences.any((p) => 
        p.disciplineId == disciplineId && p.courseTypeId == courseTypeId);
  }
}

// Notifier pour les préférences étudiant
class StudentPreferencesNotifier extends StateNotifier<StudentPreferencesState> {
  final PreferencesService _preferencesService;

  StudentPreferencesNotifier(this._preferencesService) : super(StudentPreferencesState());

  Future<void> loadPreferences() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final preferences = await _preferencesService.getPreferences();
      state = state.copyWith(
        preferences: preferences,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  Future<void> addPreference({
    required int disciplineId,
    int? courseTypeId,
    bool isPreferred = true,
    int priorityLevel = 1,
  }) async {
    try {
      final newPreference = await _preferencesService.addPreference(
        disciplineId: disciplineId,
        courseTypeId: courseTypeId,
        isPreferred: isPreferred,
        priorityLevel: priorityLevel,
      );
      
      state = state.copyWith(
        preferences: [...state.preferences, newPreference],
      );
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> removePreference({
    required int disciplineId,
    int? courseTypeId,
  }) async {
    try {
      await _preferencesService.removePreference(
        disciplineId: disciplineId,
        courseTypeId: courseTypeId,
      );
      
      state = state.copyWith(
        preferences: state.preferences.where((p) => 
          !(p.disciplineId == disciplineId && p.courseTypeId == courseTypeId)
        ).toList(),
      );
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> updatePreferences(List<StudentPreference> preferences) async {
    state = state.copyWith(isSaving: true, error: null);
    try {
      await _preferencesService.updatePreferences(preferences);
      state = state.copyWith(
        preferences: preferences,
        isSaving: false,
      );
    } catch (e) {
      state = state.copyWith(
        isSaving: false,
        error: e.toString(),
      );
    }
  }
}

// Provider pour les préférences étudiant
final studentPreferencesProvider = StateNotifierProvider<StudentPreferencesNotifier, StudentPreferencesState>((ref) {
  final preferencesService = ref.watch(preferencesServiceProvider);
  return StudentPreferencesNotifier(preferencesService);
});
