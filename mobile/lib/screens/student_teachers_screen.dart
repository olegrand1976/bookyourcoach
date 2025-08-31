import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/user.dart';
import '../providers/student_provider.dart';

class StudentTeachersScreen extends ConsumerStatefulWidget {
  const StudentTeachersScreen({super.key});

  @override
  ConsumerState<StudentTeachersScreen> createState() => _StudentTeachersScreenState();
}

class _StudentTeachersScreenState extends ConsumerState<StudentTeachersScreen> {
  @override
  void initState() {
    super.initState();
    // Charger les enseignants
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(studentProvider.notifier).loadTeachers();
    });
  }

  @override
  Widget build(BuildContext context) {
    final teachersState = ref.watch(studentProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Enseignants'),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1E3A8A),
        elevation: 0,
      ),
      body: _buildBody(ref),

          return RefreshIndicator(
            onRefresh: () async {
              ref.read(studentProvider.notifier).loadTeachers();
            },
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: teachers.length,
              itemBuilder: (context, index) {
                final teacher = teachers[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 16),
                  elevation: 2,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 30,
                          backgroundImage: NetworkImage(teacher.avatarUrl),
                          onBackgroundImageError: (exception, stackTrace) {
                            // Gérer l'erreur d'image
                          },
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                teacher.displayName,
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF1E3A8A),
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                teacher.email,
                                style: const TextStyle(
                                  fontSize: 14,
                                  color: Color(0xFF6B7280),
                                ),
                              ),
                              const SizedBox(height: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 4,
                                ),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF3B82F6),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Text(
                                  'Enseignant',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 12,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        IconButton(
                          onPressed: () {
                            _showTeacherDetails(teacher);
                          },
                          icon: const Icon(
                            Icons.info_outline,
                            color: Color(0xFF3B82F6),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          );
        },
        loading: () => const Center(
          child: CircularProgressIndicator(),
        ),
        error: (error, stack) => Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(
                Icons.error_outline,
                size: 64,
                color: Color(0xFFEF4444),
              ),
              const SizedBox(height: 16),
              Text(
                'Erreur lors du chargement',
                style: const TextStyle(
                  fontSize: 18,
                  color: Color(0xFFEF4444),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                error.toString(),
                style: const TextStyle(
                  fontSize: 14,
                  color: Color(0xFF6B7280),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () {
                  ref.read(studentProvider.notifier).loadTeachers();
                },
                child: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showTeacherDetails(User teacher) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(teacher.displayName),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: CircleAvatar(
                radius: 40,
                backgroundImage: NetworkImage(teacher.avatarUrl),
              ),
            ),
            const SizedBox(height: 16),
            Text('Email: ${teacher.email}'),
            if (teacher.profile != null) ...[
              if (teacher.profile!['bio'] != null) ...[
                const SizedBox(height: 8),
                Text('Bio: ${teacher.profile!['bio']}'),
              ],
              if (teacher.profile!['specialties'] != null) ...[
                const SizedBox(height: 8),
                Text('Spécialités: ${teacher.profile!['specialties']}'),
              ],
            ],
            const SizedBox(height: 8),
            Text('Membre depuis: ${teacher.createdAt.day}/${teacher.createdAt.month}/${teacher.createdAt.year}'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Fermer'),
          ),
        ],
      ),
    );
  }
}
