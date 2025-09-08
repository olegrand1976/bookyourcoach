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
    Future.microtask(() => _loadLessons());
  }

  void _loadLessons() {
    String? statusFilter;
    DateTime? dateFilter;
    
    // Gestion des filtres de statut
    if (_selectedFilter != 'all' && 
        !['today', 'week', 'month'].contains(_selectedFilter)) {
      statusFilter = _selectedFilter;
    }
    
    // Gestion des filtres de date
    if (_selectedFilter == 'today') {
      final now = DateTime.now();
      dateFilter = DateTime(now.year, now.month, now.day);
    } else if (_selectedFilter == 'week') {
      final now = DateTime.now();
      final startOfWeek = now.subtract(Duration(days: now.weekday - 1));
      dateFilter = DateTime(startOfWeek.year, startOfWeek.month, startOfWeek.day);
    } else if (_selectedFilter == 'month') {
      final now = DateTime.now();
      dateFilter = DateTime(now.year, now.month, 1);
    }
    
    ref.read(teacherLessonsProvider.notifier).loadLessons(
      status: statusFilter,
      date: dateFilter,
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
      drawer: _buildDrawer(),
      body: lessonsState.isLoading
          ? const Center(child: CircularProgressIndicator())
          : lessonsState.error != null
              ? _buildErrorState(lessonsState.error!)
              : lessonsState.lessons.isEmpty
                  ? _buildEmptyState()
                  : _buildLessonsList(lessonsState.lessons),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _navigateToLessonForm(),
        backgroundColor: const Color(0xFF2563EB),
        foregroundColor: Colors.white,
        child: const Icon(Icons.add),
      ),
    );
  }

  Widget _buildDrawer() {
    return Drawer(
      child: Column(
        children: [
          // En-tête du drawer
          DrawerHeader(
            decoration: const BoxDecoration(
              color: Color(0xFF2563EB),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 40),
                const Text(
                  'Filtres des Cours',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Filtrer vos cours par statut',
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.8),
                    fontSize: 14,
                  ),
                ),
              ],
            ),
          ),
          
          // Section des filtres
          Expanded(
            child: ListView(
              padding: EdgeInsets.zero,
              children: [
                _buildFilterTile('Tous les cours', 'all', Icons.list),
                _buildFilterTile('En attente', 'pending', Icons.schedule),
                _buildFilterTile('Confirmés', 'confirmed', Icons.check_circle),
                _buildFilterTile('Terminés', 'completed', Icons.done_all),
                _buildFilterTile('Disponibles', 'available', Icons.event_available),
                _buildFilterTile('Annulés', 'cancelled', Icons.cancel),
                _buildFilterTile('Absents', 'no_show', Icons.person_off),
                const Divider(),
                _buildFilterTile('Aujourd\'hui', 'today', Icons.today),
                _buildFilterTile('Cette semaine', 'week', Icons.view_week),
                _buildFilterTile('Ce mois', 'month', Icons.calendar_month),
              ],
            ),
          ),
          
          // Bouton pour fermer le drawer
          Padding(
            padding: const EdgeInsets.all(16),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(context),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF2563EB),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
                child: const Text('Fermer'),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterTile(String title, String value, IconData icon) {
    final isSelected = _selectedFilter == value;
    
    return ListTile(
      leading: Icon(
        icon,
        color: isSelected ? const Color(0xFF2563EB) : Colors.grey[600],
      ),
      title: Text(
        title,
        style: TextStyle(
          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
          color: isSelected ? const Color(0xFF2563EB) : Colors.grey[800],
        ),
      ),
      trailing: isSelected 
          ? const Icon(Icons.check, color: Color(0xFF2563EB))
          : null,
      selected: isSelected,
      selectedTileColor: const Color(0xFF2563EB).withOpacity(0.1),
      onTap: () {
        setState(() {
          _selectedFilter = value;
        });
        _loadLessons();
        Navigator.pop(context); // Ferme le drawer
      },
    );
  }

  Widget _buildLessonsList(List<Lesson> lessons) {
    return RefreshIndicator(
      onRefresh: () async {
        _loadLessons();
      },
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: lessons.length,
        itemBuilder: (context, index) {
          final lesson = lessons[index];
          return _buildLessonCard(lesson);
        },
      ),
    );
  }

  Widget _buildLessonCard(Lesson lesson) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 3,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: Column(
        children: [
          // En-tête du cours
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: _getStatusColor(lesson.status).withOpacity(0.1),
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(8),
                topRight: Radius.circular(8),
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
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF1E293B),
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        lesson.description,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey[600],
                        ),
                        maxLines: 1,
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
            padding: const EdgeInsets.all(12),
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(
                      child: _buildDetailRow(Icons.access_time, 'Horaires', lesson.formattedTime),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: _buildDetailRow(Icons.calendar_today, 'Date', lesson.formattedDate),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                if (lesson.locationData != null || lesson.location != null) ...[
                  _buildDetailRow(Icons.location_on, 'Lieu', lesson.locationData?['name'] ?? lesson.location ?? 'Lieu non spécifié'),
                  const SizedBox(height: 8),
                ],
                Row(
                  children: [
                    if (lesson.price != null) ...[
                      Expanded(
                        child: _buildDetailRow(Icons.euro, 'Prix', '${lesson.price}€'),
                      ),
                      const SizedBox(width: 16),
                    ],
                    if (lesson.student != null) ...[
                      Expanded(
                        child: _buildDetailRow(Icons.person, 'Étudiant', lesson.student!.displayName),
                      ),
                    ],
                  ],
                ),
                if (lesson.notes != null && lesson.notes!.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  _buildDetailRow(Icons.note, 'Notes', lesson.notes!),
                ],
              ],
            ),
          ),
          
          // Actions
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.grey[50],
              borderRadius: const BorderRadius.only(
                bottomLeft: Radius.circular(8),
                bottomRight: Radius.circular(8),
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
        Icon(icon, size: 14, color: Colors.grey[600]),
        const SizedBox(width: 6),
        Text(
          '$label: ',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w500,
            color: Colors.grey[700],
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[600],
            ),
            overflow: TextOverflow.ellipsis,
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
              Future.microtask(() => _loadLessons());
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
                  Future.microtask(() => _loadLessons());
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
                  Future.microtask(() => _loadLessons());
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
                  Future.microtask(() => _loadLessons());
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
                  Future.microtask(() => _loadLessons());
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
      case 'pending':
        return const Color(0xFFF59E0B); // Orange pour en attente
      case 'confirmed':
        return const Color(0xFF2563EB); // Bleu pour confirmé
      case 'completed':
        return const Color(0xFF059669); // Vert pour terminé
      case 'cancelled':
        return const Color(0xFFDC2626); // Rouge pour annulé
      case 'no_show':
        return const Color(0xFF6B7280); // Gris pour absent
      case 'available':
        return const Color(0xFF10B981); // Vert pour disponible
      default:
        return Colors.grey;
    }
  }
}

