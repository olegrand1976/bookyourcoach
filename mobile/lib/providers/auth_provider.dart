import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/user.dart';
import '../services/auth_service.dart';

// Provider pour le service d'authentification
final authServiceProvider = Provider<AuthService>((ref) {
  return AuthService();
});

// Provider pour l'état de l'authentification
final authStateProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  final authService = ref.watch(authServiceProvider);
  return AuthNotifier(authService);
});

// État de l'authentification
class AuthState {
  final bool isLoading;
  final User? user;
  final String? error;
  final bool isAuthenticated;

  const AuthState({
    this.isLoading = false,
    this.user,
    this.error,
    this.isAuthenticated = false,
  });

  AuthState copyWith({
    bool? isLoading,
    User? user,
    String? error,
    bool? isAuthenticated,
  }) {
    return AuthState(
      isLoading: isLoading ?? this.isLoading,
      user: user ?? this.user,
      error: error ?? this.error,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
    );
  }
}

// Notifier pour gérer l'état de l'authentification
class AuthNotifier extends StateNotifier<AuthState> {
  final AuthService _authService;

  AuthNotifier(this._authService) : super(const AuthState()) {
    _initializeAuth();
  }

  // Initialiser l'état d'authentification au démarrage
  Future<void> _initializeAuth() async {
    final isLoggedIn = await _authService.isLoggedIn();
    if (isLoggedIn) {
      final user = await _authService.getUser();
      if (user != null) {
        state = state.copyWith(
          user: user,
          isAuthenticated: true,
        );
      }
    }
  }

  // Connexion
  Future<bool> login(String email, String password) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final result = await _authService.login(email, password);
      
      if (result['success']) {
        final user = result['user'] as User;
        state = state.copyWith(
          user: user,
          isAuthenticated: true,
          isLoading: false,
        );
        return true;
      } else {
        state = state.copyWith(
          error: result['message'],
          isLoading: false,
        );
        return false;
      }
    } catch (e) {
      state = state.copyWith(
        error: 'Erreur inattendue: $e',
        isLoading: false,
      );
      return false;
    }
  }

  // Déconnexion
  Future<void> logout() async {
    state = state.copyWith(isLoading: true);

    try {
      await _authService.logout();
      state = const AuthState();
    } catch (e) {
      // Même en cas d'erreur, on déconnecte localement
      state = const AuthState();
    }
  }

  // Mettre à jour les données utilisateur
  Future<void> updateUser(User user) async {
    await _authService.updateUserData(user);
    state = state.copyWith(user: user);
  }

  // Effacer l'erreur
  void clearError() {
    state = state.copyWith(error: null);
  }

  // Vérifier les capacités utilisateur
  bool get canActAsTeacher => state.user?.canActAsTeacher() ?? false;
  bool get canActAsStudent => state.user?.canActAsStudent() ?? false;
  bool get isAdmin => state.user?.isAdmin ?? false;
  bool get isTeacher => state.user?.isTeacher ?? false;
  bool get isStudent => state.user?.isStudent ?? false;
}

// Providers utilitaires
final userProvider = Provider<User?>((ref) {
  return ref.watch(authStateProvider).user;
});

final isAuthenticatedProvider = Provider<bool>((ref) {
  return ref.watch(authStateProvider).isAuthenticated;
});

final isLoadingProvider = Provider<bool>((ref) {
  return ref.watch(authStateProvider).isLoading;
});

final errorProvider = Provider<String?>((ref) {
  return ref.watch(authStateProvider).error;
});
