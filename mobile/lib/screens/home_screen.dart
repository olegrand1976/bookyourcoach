import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../state/app_state.dart';
import 'student_profile_screen.dart';
import 'teacher_profile_screen.dart';
import 'student_bookings_screen.dart';
import 'teacher_lessons_screen.dart';
import 'language_settings_screen.dart';
import '../l10n/app_localizations.dart' as app_l10n;
import 'admin_home_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final app = context.watch<AppState>();
    final isTeacher = app.isTeacher;
    final isStudent = app.isStudent;
    final t = app_l10n.AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Text(t?.appTitle ?? 'Accueil'),
        actions: [
          IconButton(
            icon: const Icon(Icons.language),
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const LanguageSettingsScreen()),
              );
            },
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(t?.homeWelcome ?? 'Bienvenue sur BookYourCoach Mobile'),
            const SizedBox(height: 16),
            if (isStudent)
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const StudentProfileScreen()),
                    );
                  },
                  child: Text(t?.homeStudentProfile ?? 'Profil Élève'),
                ),
              ),
            if (isStudent)
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const StudentBookingsScreen()),
                    );
                  },
                  child: Text(t?.homeStudentBookings ?? 'Mes Réservations'),
                ),
              ),
            if (isTeacher)
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const TeacherProfileScreen()),
                    );
                  },
                  child: Text(t?.homeTeacherProfile ?? 'Profil Enseignant'),
                ),
              ),
            if (isTeacher)
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const TeacherLessonsScreen()),
                    );
                  },
                  child: Text(t?.homeTeacherLessons ?? 'Mes Leçons'),
                ),
              ),
            if (app.isAdmin)
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const AdminHomeScreen()),
                    );
                  },
                  child: const Text('Administration'),
                ),
              ),
            if (!isStudent && !isTeacher)
              Text(t?.homeNoRole ?? 'Aucun rôle détecté, connectez-vous ou complétez votre profil.'),
          ],
        ),
      ),
    );
  }
}