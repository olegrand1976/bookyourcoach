import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
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
    // Charger les enseignants au démarrage
    Future.microtask(() {
      ref.read(studentProvider.notifier).loadTeachers();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Enseignants'),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1E3A8A),
        elevation: 0,
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    final teachersState = ref.watch(studentProvider).teachers;
    final teachers = teachersState.teachers;
    
    if (teachersState.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    
    if (teachers.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.people_outline,
              size: 64,
              color: Color(0xFF6B7280),
            ),
            SizedBox(height: 16),
            Text(
              'Aucun enseignant',
              style: TextStyle(
                fontSize: 18,
                color: Color(0xFF6B7280),
              ),
            ),
            SizedBox(height: 8),
            Text(
              'Les enseignants s\'inscriront bientôt',
              style: TextStyle(
                fontSize: 14,
                color: Color(0xFF9CA3AF),
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () async {
        ref.read(studentProvider.notifier).loadTeachers();
      },
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: teachers.length,
        itemBuilder: (context, index) {
          final teacher = teachers[index];
          return _buildTeacherCard(teacher);
        },
      ),
    );
  }

  Widget _buildTeacherCard(dynamic teacher) {
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
                backgroundColor: const Color(0xFF2563EB),
                backgroundImage: teacher.avatarUrl != null && teacher.avatarUrl!.isNotEmpty 
                    ? NetworkImage(teacher.avatarUrl!) 
                    : null,
                onBackgroundImageError: (_, __) {},
                child: teacher.avatarUrl == null || teacher.avatarUrl!.isEmpty
                    ? Text(
                        teacher.displayName.isNotEmpty 
                            ? teacher.displayName.substring(0, 1).toUpperCase() 
                            : '?',
                        style: const TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      )
                    : null,
              ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    teacher.displayName ?? 'Enseignant',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1E3A8A),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    teacher.email ?? '',
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
                // TODO: Implémenter l'affichage des détails
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
  }

  void _showTeacherDetails(dynamic teacher, BuildContext context) {
    showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(teacher.displayName ?? 'Enseignant'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Email: ${teacher.email ?? 'Non disponible'}'),
            const SizedBox(height: 8),
            Text('Disciplines: ${teacher.disciplines?.join(', ') ?? 'Non spécifiées'}'),
            const SizedBox(height: 8),
            Text('Niveaux: ${teacher.levels?.join(', ') ?? 'Non spécifiés'}'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(),
            child: const Text('Fermer'),
          ),
        ],
      ),
    );
  }
}
