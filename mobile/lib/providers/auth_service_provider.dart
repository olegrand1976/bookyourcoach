import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../services/auth_service.dart';

// Provider pour le service d'authentification
final authServiceProvider = Provider<AuthService>((ref) {
  return AuthService();
});