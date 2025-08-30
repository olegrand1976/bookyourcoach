import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../state/app_state.dart';
import 'student_profile_screen.dart';
import 'teacher_profile_screen.dart';
import 'student_bookings_screen.dart';
import 'teacher_lessons_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final app = context.watch<AppState>();
    final isTeacher = app.isTeacher;
    final isStudent = app.isStudent;
    return Scaffold(
      appBar: AppBar(title: const Text('Accueil')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Bienvenue sur BookYourCoach Mobile'),
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
                  child: const Text('Profil Élève'),
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
                  child: const Text('Mes Réservations'),
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
                  child: const Text('Profil Enseignant'),
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
                  child: const Text('Mes Leçons'),
                ),
              ),
            if (!isStudent && !isTeacher)
              const Text('Aucun rôle détecté, connectez-vous ou complétez votre profil.'),
          ],
        ),
      ),
    );
  }
}