import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'providers/auth_provider.dart';
import 'providers/preferences_provider.dart';
import 'constants/app_colors.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';
import 'screens/teacher_dashboard.dart';
import 'screens/student_dashboard.dart';
import 'screens/test_connection_screen.dart';

void main() {
  runApp(
    const ProviderScope(
      child: activibeApp(),
    ),
  );
}

class activibeApp extends ConsumerWidget {
  const activibeApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return MaterialApp(
      title: 'activibe',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      home: const AuthWrapper(),
    );
  }
}

class AuthWrapper extends ConsumerWidget {
  const AuthWrapper({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isAuthenticated = ref.watch(isAuthenticatedProvider);
    final isLoading = ref.watch(isLoadingProvider);
    final user = ref.watch(userProvider);

    if (isLoading) {
      return const Scaffold(
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(),
              SizedBox(height: 16),
              Text(
                'Chargement...',
                style: TextStyle(
                  fontSize: 16,
                  color: Color(0xFF6B7280),
                ),
              ),
            ],
          ),
        ),
      );
    }

    if (isAuthenticated) {
      // Rediriger vers le tableau de bord approprié selon le rôle
      if (user?.isTeacher == true) {
        return const TeacherDashboard();
      } else if (user?.isStudent == true) {
        return const StudentDashboard();
      } else {
        return const HomeScreen();
      }
    } else {
      return const LoginScreen();
    }
  }
}
