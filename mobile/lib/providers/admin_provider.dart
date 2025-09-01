import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../services/admin_service.dart';
import '../models/user.dart';
import '../models/club.dart';

// Provider pour le service admin
final adminServiceProvider = Provider<AdminService>((ref) {
  return AdminService();
});

// État pour les statistiques
class AdminStatsState {
  final bool isLoading;
  final Map<String, dynamic>? stats;
  final String? error;

  AdminStatsState({
    this.isLoading = false,
    this.stats,
    this.error,
  });

  AdminStatsState copyWith({
    bool? isLoading,
    Map<String, dynamic>? stats,
    String? error,
  }) {
    return AdminStatsState(
      isLoading: isLoading ?? this.isLoading,
      stats: stats ?? this.stats,
      error: error,
    );
  }
}

// Notifier pour les statistiques
class AdminStatsNotifier extends StateNotifier<AdminStatsState> {
  final AdminService _adminService;

  AdminStatsNotifier(this._adminService) : super(AdminStatsState());

  Future<void> loadStats() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final stats = await _adminService.getStats();
      state = state.copyWith(isLoading: false, stats: stats);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Provider pour les statistiques
final adminStatsProvider = StateNotifierProvider<AdminStatsNotifier, AdminStatsState>((ref) {
  final adminService = ref.watch(adminServiceProvider);
  return AdminStatsNotifier(adminService);
});

// État pour les activités
class AdminActivitiesState {
  final bool isLoading;
  final List<Map<String, dynamic>> activities;
  final String? error;

  AdminActivitiesState({
    this.isLoading = false,
    this.activities = const [],
    this.error,
  });

  AdminActivitiesState copyWith({
    bool? isLoading,
    List<Map<String, dynamic>>? activities,
    String? error,
  }) {
    return AdminActivitiesState(
      isLoading: isLoading ?? this.isLoading,
      activities: activities ?? this.activities,
      error: error,
    );
  }
}

// Notifier pour les activités
class AdminActivitiesNotifier extends StateNotifier<AdminActivitiesState> {
  final AdminService _adminService;

  AdminActivitiesNotifier(this._adminService) : super(AdminActivitiesState());

  Future<void> loadActivities({int? limit}) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final activities = await _adminService.getActivities(limit: limit);
      state = state.copyWith(isLoading: false, activities: activities);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Provider pour les activités
final adminActivitiesProvider = StateNotifierProvider<AdminActivitiesNotifier, AdminActivitiesState>((ref) {
  final adminService = ref.watch(adminServiceProvider);
  return AdminActivitiesNotifier(adminService);
});

// État pour les utilisateurs
class AdminUsersState {
  final bool isLoading;
  final List<User> users;
  final String? error;
  final int currentPage;
  final int totalPages;
  final int totalUsers;
  final String? searchQuery;
  final String? roleFilter;
  final String? statusFilter;

  AdminUsersState({
    this.isLoading = false,
    this.users = const [],
    this.error,
    this.currentPage = 1,
    this.totalPages = 1,
    this.totalUsers = 0,
    this.searchQuery,
    this.roleFilter,
    this.statusFilter,
  });

  AdminUsersState copyWith({
    bool? isLoading,
    List<User>? users,
    String? error,
    int? currentPage,
    int? totalPages,
    int? totalUsers,
    String? searchQuery,
    String? roleFilter,
    String? statusFilter,
  }) {
    return AdminUsersState(
      isLoading: isLoading ?? this.isLoading,
      users: users ?? this.users,
      error: error,
      currentPage: currentPage ?? this.currentPage,
      totalPages: totalPages ?? this.totalPages,
      totalUsers: totalUsers ?? this.totalUsers,
      searchQuery: searchQuery ?? this.searchQuery,
      roleFilter: roleFilter ?? this.roleFilter,
      statusFilter: statusFilter ?? this.statusFilter,
    );
  }
}

// Notifier pour les utilisateurs
class AdminUsersNotifier extends StateNotifier<AdminUsersState> {
  final AdminService _adminService;

  AdminUsersNotifier(this._adminService) : super(AdminUsersState());

  Future<void> loadUsers({
    int page = 1,
    int perPage = 10,
    String? search,
    String? role,
    String? status,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final result = await _adminService.getUsers(
        page: page,
        perPage: perPage,
        search: search,
        role: role,
        status: status,
      );

      final users = (result['data'] as List).map((json) => User.fromJson(json)).toList();
      final currentPage = result['current_page'] ?? 1;
      final totalPages = result['last_page'] ?? 1;
      final totalUsers = result['total'] ?? 0;

      state = state.copyWith(
        isLoading: false,
        users: users,
        currentPage: currentPage,
        totalPages: totalPages,
        totalUsers: totalUsers,
        searchQuery: search,
        roleFilter: role,
        statusFilter: status,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  Future<void> createUser({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String role,
  }) async {
    try {
      await _adminService.createUser(
        name: name,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
        role: role,
      );
      
      // Recharger la liste des utilisateurs
      await loadUsers(
        page: state.currentPage,
        search: state.searchQuery,
        role: state.roleFilter,
        status: state.statusFilter,
      );
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> updateUser({
    required int userId,
    String? name,
    String? email,
    String? role,
  }) async {
    try {
      await _adminService.updateUser(
        userId: userId,
        name: name,
        email: email,
        role: role,
      );
      
      // Recharger la liste des utilisateurs
      await loadUsers(
        page: state.currentPage,
        search: state.searchQuery,
        role: state.roleFilter,
        status: state.statusFilter,
      );
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> toggleUserStatus(int userId) async {
    try {
      await _adminService.toggleUserStatus(userId);
      
      // Recharger la liste des utilisateurs
      await loadUsers(
        page: state.currentPage,
        search: state.searchQuery,
        role: state.roleFilter,
        status: state.statusFilter,
      );
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Provider pour les utilisateurs
final adminUsersProvider = StateNotifierProvider<AdminUsersNotifier, AdminUsersState>((ref) {
  final adminService = ref.watch(adminServiceProvider);
  return AdminUsersNotifier(adminService);
});

// État pour les clubs
class AdminClubsState {
  final bool isLoading;
  final List<Club> clubs;
  final String? error;

  AdminClubsState({
    this.isLoading = false,
    this.clubs = const [],
    this.error,
  });

  AdminClubsState copyWith({
    bool? isLoading,
    List<Club>? clubs,
    String? error,
  }) {
    return AdminClubsState(
      isLoading: isLoading ?? this.isLoading,
      clubs: clubs ?? this.clubs,
      error: error,
    );
  }
}

// Notifier pour les clubs
class AdminClubsNotifier extends StateNotifier<AdminClubsState> {
  final AdminService _adminService;

  AdminClubsNotifier(this._adminService) : super(AdminClubsState());

  Future<void> loadClubs() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final clubs = await _adminService.getClubs();
      state = state.copyWith(isLoading: false, clubs: clubs);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  Future<void> createClub({
    required String name,
    required String address,
    required String city,
    required String postalCode,
    required String country,
    String? description,
    List<String>? facilities,
  }) async {
    try {
      await _adminService.createClub(
        name: name,
        address: address,
        city: city,
        postalCode: postalCode,
        country: country,
        description: description,
        facilities: facilities,
      );
      
      // Recharger la liste des clubs
      await loadClubs();
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> updateClub({
    required int clubId,
    String? name,
    String? address,
    String? city,
    String? postalCode,
    String? country,
    String? description,
    List<String>? facilities,
  }) async {
    try {
      await _adminService.updateClub(
        clubId: clubId,
        name: name,
        address: address,
        city: city,
        postalCode: postalCode,
        country: country,
        description: description,
        facilities: facilities,
      );
      
      // Recharger la liste des clubs
      await loadClubs();
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> deleteClub(int clubId) async {
    try {
      await _adminService.deleteClub(clubId);
      
      // Recharger la liste des clubs
      await loadClubs();
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  Future<void> toggleClubStatus(int clubId) async {
    try {
      await _adminService.toggleClubStatus(clubId);
      
      // Recharger la liste des clubs
      await loadClubs();
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Provider pour les clubs
final adminClubsProvider = StateNotifierProvider<AdminClubsNotifier, AdminClubsState>((ref) {
  final adminService = ref.watch(adminServiceProvider);
  return AdminClubsNotifier(adminService);
});

// État pour les paramètres
class AdminSettingsState {
  final bool isLoading;
  final Map<String, dynamic>? settings;
  final String? error;

  AdminSettingsState({
    this.isLoading = false,
    this.settings,
    this.error,
  });

  AdminSettingsState copyWith({
    bool? isLoading,
    Map<String, dynamic>? settings,
    String? error,
  }) {
    return AdminSettingsState(
      isLoading: isLoading ?? this.isLoading,
      settings: settings ?? this.settings,
      error: error,
    );
  }
}

// Notifier pour les paramètres
class AdminSettingsNotifier extends StateNotifier<AdminSettingsState> {
  final AdminService _adminService;

  AdminSettingsNotifier(this._adminService) : super(AdminSettingsState());

  Future<void> loadSettings() async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final settings = await _adminService.getAllSettings();
      state = state.copyWith(isLoading: false, settings: settings);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  Future<void> updateSettings({
    required String type,
    required Map<String, dynamic> settings,
  }) async {
    try {
      await _adminService.updateSettings(type: type, settings: settings);
      
      // Recharger les paramètres
      await loadSettings();
    } catch (e) {
      state = state.copyWith(error: e.toString());
    }
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

// Provider pour les paramètres
final adminSettingsProvider = StateNotifierProvider<AdminSettingsNotifier, AdminSettingsState>((ref) {
  final adminService = ref.watch(adminServiceProvider);
  return AdminSettingsNotifier(adminService);
});
