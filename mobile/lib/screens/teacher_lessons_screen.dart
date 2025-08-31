import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/teacher_provider.dart';
import '../models/lesson.dart';
import '../widgets/custom_button.dart';
import 'lesson_form_screen.dart';

class TeacherLessonsScreen extends ConsumerStatefulWidget {
  const TeacherLessonsScreen({super.key});

  @override
  ConsumerState<TeacherLessonsScreen> createState() => _TeacherLessonsScreenState();
}

class _TeacherLessonsScreenState extends ConsumerState<TeacherLessonsScreen> {
  String _selectedFilter = 'all';
  DateTime? _selectedDate;

  @override
  void initState() {
    super.initState();
    _loadLessons();
  }

  void _loadLessons() {
    ref.read(teacherLessonsProvider.notifier).loadLessons(
      status: _selectedFilter == 'all' ? null : _selectedFilter,
      date: _selectedDate,
    );
  }

  @override
  Widget build(BuildContext context) {
    final lessonsState = ref.watch(teacherLessonsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Cours'),
        backgroundColor: const Color(0xFF2563EB),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: () => _showFilterDialog(),
          ),
        ],
      ),
      body: Column(
        children: [
          // Filtres
          Container(
            padding: const EdgeInsets.all(16),
            color: const Color(0xFFF8FAFC),
            child: Row(
              children: [
                Expanded(
                  child: _buildFilterChip('Tous', 'all', _selectedFilter == 'all'),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildFilterChip('Planifiés', 'scheduled', _selectedFilter == 'scheduled'),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildFilterChip('En cours', 'in_progress', _selectedFilter == 'in_progress'),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildFilterChip('Terminés', 'completed', _selectedFilter == 'completed'),
                ),
              ],
            ),
          ),
          
          // Liste des cours
          Expanded(
            child: lessonsState.isLoading
                ? const Center(child: CircularProgressIndicator())
                : lessonsState.error != null
                    ? _buildErrorState(lessonsState.error!)
                    : lessonsState.lessons.isEmpty
                        ? _buildEmptyState()
                        : _buildLessonsList(lessonsState.lessons),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _navigateToLessonForm(),
        backgroundColor: const Color(0xFF2563EB),
        foregroundColor: Colors.white,
        child: const Icon(Icons.add),
      ),
    );
  }

  Widget _buildFilterChip(String label, String value, bool isSelected) {
    return GestureDetector(
      onTap: () {
        setState(() {
          _selectedFilter = value;
        });
        _loadLessons();
      },
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF2563EB) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? const Color(0xFF2563EB) : Colors.grey[300]!,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : Colors.grey[700],
            fontSize: 12,
            fontWeight: FontWeight.w500,
          ),
          textAlign: TextAlign.center,
        ),
      ),
    );
  }

  Widget _buildLessonsList(List<Lesson> lessons) {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: lessons.length,
      itemBuilder: (context, index) {
        final lesson = lessons[index];
        return _buildLessonCard(lesson);
      },
    );
  }

  Widget _buildLessonCard(Lesson lesson) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          // En-tête du cours
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: _getStatusColor(lesson.status).withOpacity(0.1),
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(12),
                topRight: Radius.circular(12),
              ),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        lesson.title,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF1E293B),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        lesson.description,
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey[600],
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: _getStatusColor(lesson.status),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    lesson.statusDisplay,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Colors.white,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          // Détails du cours
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                _buildDetailRow(Icons.access_time, 'Horaires', lesson.formattedTime),
                const SizedBox(height: 8),
                _buildDetailRow(Icons.calendar_today, 'Date', lesson.formattedDate),
                if (lesson.location != null) ...[
                  const SizedBox(height: 8),
                  _buildDetailRow(Icons.location_on, 'Lieu', lesson.location!),
                ],
                if (lesson.price != null) ...[
                  const SizedBox(height: 8),
                  _buildDetailRow(Icons.euro, 'Prix', '${lesson.price}€'),
                ],
                if (lesson.student != null) ...[
                  const SizedBox(height: 8),
                  _buildDetailRow(Icons.person, 'Étudiant', lesson.student!.displayName),
                ],
                if (lesson.notes != null && lesson.notes!.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  _buildDetailRow(Icons.note, 'Notes', lesson.notes!),
                ],
              ],
            ),
          ),
          
          // Actions
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.grey[50],
              borderRadius: const BorderRadius.only(
                bottomLeft: Radius.circular(12),
                bottomRight: Radius.circular(12),
              ),
            ),
            child: Row(
              children: [
                Expanded(
                  child: CustomOutlinedButton(
                    onPressed: () => _editLesson(lesson),
                    text: 'Modifier',
                    icon: Icons.edit,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: CustomOutlinedButton(
                    onPressed: () => _showLessonActions(lesson),
                    text: 'Actions',
                    icon: Icons.more_vert,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, size: 16, color: Colors.grey[600]),
        const SizedBox(width: 8),
        Text(
          '$label: ',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: Colors.grey[700],
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[600],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.school,
            size: 64,
            color: Colors.grey[400],
          ),
          const SizedBox(height: 16),
          Text(
            'Aucun cours trouvé',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.grey[600],
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Créez votre premier cours pour commencer',
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[500],
            ),
          ),
          const SizedBox(height: 24),
          CustomButton(
            onPressed: () => _navigateToLessonForm(),
            text: 'Créer un cours',
            icon: Icons.add,
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState(String error) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.error_outline,
            size: 64,
            color: Colors.red[400],
          ),
          const SizedBox(height: 16),
          Text(
            'Erreur',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.red[600],
            ),
          ),
          const SizedBox(height: 8),
          Text(
            error,
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[600],
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 24),
          CustomButton(
            onPressed: () {
              ref.read(teacherLessonsProvider.notifier).clearError();
              _loadLessons();
            },
            text: 'Réessayer',
            icon: Icons.refresh,
          ),
        ],
      ),
    );
  }

  void _showFilterDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Filtres'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              title: const Text('Tous les cours'),
              leading: Radio<String>(
                value: 'all',
                groupValue: _selectedFilter,
                onChanged: (value) {
                  setState(() {
                    _selectedFilter = value!;
                  });
                  Navigator.of(context).pop();
                  _loadLessons();
                },
              ),
            ),
            ListTile(
              title: const Text('Cours planifiés'),
              leading: Radio<String>(
                value: 'scheduled',
                groupValue: _selectedFilter,
                onChanged: (value) {
                  setState(() {
                    _selectedFilter = value!;
                  });
                  Navigator.of(context).pop();
                  _loadLessons();
                },
              ),
            ),
            ListTile(
              title: const Text('Cours en cours'),
              leading: Radio<String>(
                value: 'in_progress',
                groupValue: _selectedFilter,
                onChanged: (value) {
                  setState(() {
                    _selectedFilter = value!;
                  });
                  Navigator.of(context).pop();
                  _loadLessons();
                },
              ),
            ),
            ListTile(
              title: const Text('Cours terminés'),
              leading: Radio<String>(
                value: 'completed',
                groupValue: _selectedFilter,
                onChanged: (value) {
                  setState(() {
                    _selectedFilter = value!;
                  });
                  Navigator.of(context).pop();
                  _loadLessons();
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showLessonActions(Lesson lesson) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Container(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.edit),
              title: const Text('Modifier'),
              onTap: () {
                Navigator.of(context).pop();
                _editLesson(lesson);
              },
            ),
            if (lesson.isScheduled) ...[
              ListTile(
                leading: const Icon(Icons.play_arrow),
                title: const Text('Démarrer le cours'),
                onTap: () {
                  Navigator.of(context).pop();
                  _startLesson(lesson);
                },
              ),
              ListTile(
                leading: const Icon(Icons.cancel),
                title: const Text('Annuler le cours'),
                onTap: () {
                  Navigator.of(context).pop();
                  _cancelLesson(lesson);
                },
              ),
            ],
            if (lesson.isInProgress) ...[
              ListTile(
                leading: const Icon(Icons.check),
                title: const Text('Terminer le cours'),
                onTap: () {
                  Navigator.of(context).pop();
                  _completeLesson(lesson);
                },
              ),
            ],
            ListTile(
              leading: const Icon(Icons.delete, color: Colors.red),
              title: const Text('Supprimer', style: TextStyle(color: Colors.red)),
              onTap: () {
                Navigator.of(context).pop();
                _deleteLesson(lesson);
              },
            ),
          ],
        ),
      ),
    );
  }

  void _navigateToLessonForm() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (context) => const LessonFormScreen(),
      ),
    );
  }

  void _editLesson(Lesson lesson) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (context) => LessonFormScreen(lesson: lesson),
      ),
    );
  }

  void _startLesson(Lesson lesson) {
    ref.read(teacherLessonsProvider.notifier).updateLesson(
      lessonId: lesson.id,
      status: 'in_progress',
    );
  }

  void _completeLesson(Lesson lesson) {
    ref.read(teacherLessonsProvider.notifier).updateLesson(
      lessonId: lesson.id,
      status: 'completed',
    );
  }

  void _cancelLesson(Lesson lesson) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Annuler le cours'),
        content: const Text('Êtes-vous sûr de vouloir annuler ce cours ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Non'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              ref.read(teacherLessonsProvider.notifier).updateLesson(
                lessonId: lesson.id,
                status: 'cancelled',
              );
            },
            child: const Text('Oui'),
          ),
        ],
      ),
    );
  }

  void _deleteLesson(Lesson lesson) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Supprimer le cours'),
        content: const Text('Êtes-vous sûr de vouloir supprimer ce cours ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              ref.read(teacherLessonsProvider.notifier).deleteLesson(lesson.id);
            },
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'scheduled':
        return const Color(0xFF2563EB);
      case 'in_progress':
        return const Color(0xFF059669);
      case 'completed':
        return const Color(0xFF7C3AED);
      case 'cancelled':
        return const Color(0xFFDC2626);
      default:
        return Colors.grey;
    }
  }
}

