import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/teacher_provider.dart';


class TeacherStudentsScreen extends ConsumerStatefulWidget {
  const TeacherStudentsScreen({super.key});

  @override
  ConsumerState<TeacherStudentsScreen> createState() => _TeacherStudentsScreenState();
}

class _TeacherStudentsScreenState extends ConsumerState<TeacherStudentsScreen> {
  @override
  void initState() {
    super.initState();
    // Charger les étudiants au démarrage
    Future.microtask(() {
      ref.read(teacherProvider.notifier).loadStudents();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Étudiants'),
        backgroundColor: const Color(0xFF2563EB),
        foregroundColor: Colors.white,
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    final studentsState = ref.watch(teacherProvider).students;
    final students = studentsState.students;
    
    if (studentsState.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    
    if (students.isEmpty) {
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
              'Aucun étudiant',
              style: TextStyle(
                fontSize: 18,
                color: Color(0xFF6B7280),
              ),
            ),
            SizedBox(height: 8),
            Text(
              'Les étudiants s\'inscriront bientôt',
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
        ref.read(teacherProvider.notifier).loadStudents();
      },
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: students.length,
        itemBuilder: (context, index) {
          final student = students[index];
          return _buildStudentCard(student);
        },
      ),
    );
  }

  Widget _buildStudentCard(dynamic student) {
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
                backgroundImage: student.avatarUrl != null && student.avatarUrl!.isNotEmpty 
                    ? NetworkImage(student.avatarUrl!) 
                    : null,
                onBackgroundImageError: (_, __) {},
                child: student.avatarUrl == null || student.avatarUrl!.isEmpty
                    ? Text(
                        student.displayName.isNotEmpty 
                            ? student.displayName.substring(0, 1).toUpperCase() 
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
                    student.displayName ?? 'Étudiant',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1E3A8A),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    student.email ?? '',
                    style: const TextStyle(
                      fontSize: 14,
                      color: Color(0xFF6B7280),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
