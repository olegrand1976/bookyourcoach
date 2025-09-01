import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/auth_provider.dart';
import '../providers/teacher_provider.dart';
import '../widgets/custom_button.dart';

import 'teacher_lessons_screen.dart';
import 'teacher_availabilities_screen.dart';
import 'teacher_students_screen.dart';
import 'teacher_stats_screen.dart';

class TeacherDashboard extends ConsumerStatefulWidget {
  const TeacherDashboard({super.key});

  @override
  ConsumerState<TeacherDashboard> createState() => _TeacherDashboardState();
}

class _TeacherDashboardState extends ConsumerState<TeacherDashboard> {
  int _selectedIndex = 0;
  bool _isStatsExpanded = true; // Nouvelle variable pour contrôler l'expansion des stats

  @override
  void initState() {
    super.initState();
    // Charger les données initiales
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(teacherLessonsProvider.notifier).loadLessons();
      ref.read(teacherStatsProvider.notifier).loadStats();
    });
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(userProvider);
    final lessonsState = ref.watch(teacherLessonsProvider);
    final statsState = ref.watch(teacherStatsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Tableau de Bord Enseignant'),
        backgroundColor: const Color(0xFF2563EB),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => _showLogoutDialog(),
          ),
        ],
      ),
      body: Column(
        children: [
          // En-tête avec informations de l'utilisateur
          Container(
            padding: const EdgeInsets.all(16),
            color: const Color(0xFFF8FAFC),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 30,
                  backgroundImage: user?.avatarUrl != null && user!.avatarUrl!.isNotEmpty 
                      ? NetworkImage(user!.avatarUrl!) 
                      : null,
                  onBackgroundImageError: (_, __) {},
                  child: user?.avatarUrl == null || user!.avatarUrl!.isEmpty
                      ? Text(
                          user?.displayName.isNotEmpty == true 
                              ? user!.displayName.substring(0, 1).toUpperCase() 
                              : 'E',
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
                        'Bonjour, ${user?.displayName ?? 'Enseignant'}',
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF1E293B),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Enseignant',
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          
          // Statistiques rapides repliables
          if (statsState.stats != null) _buildCollapsibleStats(statsState.stats!),
          
          // Navigation par onglets
          Expanded(
            child: IndexedStack(
              index: _selectedIndex,
              children:               const [
                TeacherLessonsScreen(),
                TeacherAvailabilitiesScreen(),
                TeacherStudentsScreen(),
                TeacherStatsScreen(),
              ],
            ),
          ),
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        type: BottomNavigationBarType.fixed,
        currentIndex: _selectedIndex,
        onTap: (index) => setState(() => _selectedIndex = index),
        selectedItemColor: const Color(0xFF2563EB),
        unselectedItemColor: Colors.grey[600],
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.school),
            label: 'Cours',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.schedule),
            label: 'Disponibilités',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.people),
            label: 'Étudiants',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.analytics),
            label: 'Statistiques',
          ),
        ],
      ),
    );
  }

  Widget _buildQuickStats(Map<String, dynamic> stats) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Statistiques Rapides',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1E293B),
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildStatCard(
                  'Cours ce mois',
                  stats['total_lessons']?.toString() ?? '0',
                  Icons.school,
                  const Color(0xFF2563EB),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildStatCard(
                  'Cours terminés',
                  stats['completed_lessons']?.toString() ?? '0',
                  Icons.people,
                  const Color(0xFF059669),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildStatCard(
                  'Heures enseignées',
                  '${stats['total_hours']?.toString() ?? '0'}h',
                  Icons.access_time,
                  const Color(0xFFDC2626),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildStatCard(
                  'Revenus',
                  '${stats['monthly_earnings']?.toString() ?? '0'}€',
                  Icons.euro,
                  const Color(0xFF7C3AED),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
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
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            title,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey[600],
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  void _showLogoutDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Déconnexion'),
        content: const Text('Êtes-vous sûr de vouloir vous déconnecter ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              ref.read(authProvider.notifier).logout();
            },
            child: const Text('Déconnexion'),
          ),
        ],
      ),
    );
  }
}

class _OverviewTab extends ConsumerWidget {
  const _OverviewTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final lessonsState = ref.watch(teacherLessonsProvider);
    final upcomingLessons = lessonsState.lessons
        .where((lesson) => lesson.isUpcoming)
        .take(3)
        .toList();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Actions rapides
          const Text(
            'Actions Rapides',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1E293B),
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: CustomButton(
                  onPressed: () {
                    // Navigation vers la création de cours
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (context) => const TeacherLessonsScreen(),
                      ),
                    );
                  },
                  text: 'Nouveau Cours',
                  icon: Icons.add,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: CustomOutlinedButton(
                  onPressed: () {
                    // Navigation vers les disponibilités
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (context) => const TeacherAvailabilitiesScreen(),
                      ),
                    );
                  },
                  text: 'Gérer Disponibilités',
                  icon: Icons.schedule,
                ),
              ),
            ],
          ),
          
          const SizedBox(height: 24),
          
          // Prochains cours
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Prochains Cours',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1E293B),
                ),
              ),
              TextButton(
                onPressed: () {
                  // Navigation vers tous les cours
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (context) => const TeacherLessonsScreen(),
                    ),
                  );
                },
                child: const Text('Voir tout'),
              ),
            ],
          ),
          const SizedBox(height: 12),
          
          if (lessonsState.isLoading)
            const Center(child: CircularProgressIndicator())
          else if (upcomingLessons.isEmpty)
            _buildEmptyState(
              'Aucun cours à venir',
              'Créez votre premier cours pour commencer',
              Icons.school,
            )
          else
            ...upcomingLessons.map((lesson) => _buildLessonCard(lesson)),
        ],
      ),
    );
  }

  Widget _buildLessonCard(dynamic lesson) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  lesson.title,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E293B),
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: _getStatusColor(lesson.status).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  lesson.statusDisplay,
                  style: TextStyle(
                    fontSize: 12,
                    color: _getStatusColor(lesson.status),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            lesson.description,
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[600],
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Icon(Icons.access_time, size: 16, color: Colors.grey[600]),
              const SizedBox(width: 4),
              Text(
                lesson.formattedTime,
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey[600],
                ),
              ),
              const SizedBox(width: 16),
              Icon(Icons.calendar_today, size: 16, color: Colors.grey[600]),
              const SizedBox(width: 4),
              Text(
                lesson.formattedDate,
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey[600],
                ),
              ),
            ],
          ),
          if (lesson.location != null) ...[
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.location_on, size: 16, color: Colors.grey[600]),
                const SizedBox(width: 4),
                Text(
                  lesson.location!,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildEmptyState(String title, String message, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(32),
      child: Column(
        children: [
          Icon(
            icon,
            size: 64,
            color: Colors.grey[400],
          ),
          const SizedBox(height: 16),
          Text(
            title,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.grey[600],
            ),
          ),
          const SizedBox(height: 8),
          Text(
            message,
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[500],
            ),
            textAlign: TextAlign.center,
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

