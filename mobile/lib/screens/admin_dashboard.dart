import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/auth_provider.dart';
import '../providers/admin_provider.dart';
import 'admin_users_screen.dart';
import 'admin_clubs_screen.dart';
import 'admin_settings_screen.dart';
import 'admin_activities_screen.dart';

class AdminDashboard extends ConsumerStatefulWidget {
  const AdminDashboard({super.key});

  @override
  ConsumerState<AdminDashboard> createState() => _AdminDashboardState();
}

class _AdminDashboardState extends ConsumerState<AdminDashboard> {
  int _currentIndex = 0;
  bool _isStatsExpanded = true; // Nouvelle variable pour contrôler l'expansion des stats

  @override
  void initState() {
    super.initState();
    // Charger les données initiales
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(adminStatsProvider.notifier).loadStats();
      ref.read(adminActivitiesProvider.notifier).loadActivities(limit: 5);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Administration'),
        backgroundColor: const Color(0xFF1E3A8A),
        foregroundColor: Colors.white,
        elevation: 0,
        actions: [
          IconButton(
            onPressed: () {
              ref.read(authProvider.notifier).logout();
            },
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: [
          _buildOverviewTab(),
          const AdminUsersScreen(),
          const AdminClubsScreen(),
          const AdminSettingsScreen(),
          const AdminActivitiesScreen(),
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        type: BottomNavigationBarType.fixed,
        backgroundColor: Colors.white,
        selectedItemColor: const Color(0xFF1E3A8A),
        unselectedItemColor: const Color(0xFF6B7280),
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.dashboard),
            label: 'Vue d\'ensemble',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.people),
            label: 'Utilisateurs',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.location_on),
            label: 'Clubs',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.settings),
            label: 'Paramètres',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.history),
            label: 'Activités',
          ),
        ],
      ),
    );
  }

  Widget _buildOverviewTab() {
    final statsState = ref.watch(adminStatsProvider);
    final activitiesState = ref.watch(adminActivitiesProvider);

    return RefreshIndicator(
      onRefresh: () async {
        ref.read(adminStatsProvider.notifier).loadStats();
        ref.read(adminActivitiesProvider.notifier).loadActivities(limit: 5);
      },
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // En-tête
            const Text(
              'Tableau de Bord Administrateur',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1E3A8A),
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Gérez votre plateforme et surveillez les activités',
              style: TextStyle(
                fontSize: 16,
                color: Color(0xFF6B7280),
              ),
            ),
            const SizedBox(height: 24),

            // Actions rapides
            const Text(
              'Actions Rapides',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1E3A8A),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () {
                      setState(() {
                        _currentIndex = 1; // Onglet Utilisateurs
                      });
                    },
                    icon: const Icon(Icons.person_add),
                    label: const Text('Ajouter un utilisateur'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF3B82F6),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () {
                      setState(() {
                        _currentIndex = 2; // Onglet Clubs
                      });
                    },
                    icon: const Icon(Icons.add_location),
                    label: const Text('Ajouter un club'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFF3B82F6),
                      side: const BorderSide(color: Color(0xFF3B82F6)),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 24),

            // Statistiques repliables
            if (statsState.stats != null) _buildCollapsibleStats(statsState.stats!),

            const SizedBox(height: 24),

            // Activités récentes
            const Text(
              'Activités Récentes',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1E3A8A),
              ),
            ),
            const SizedBox(height: 12),
            _buildRecentActivities(activitiesState),
          ],
        ),
      ),
    );
  }

  Widget _buildCollapsibleStats(Map<String, dynamic> stats) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Column(
        children: [
          // Barre de titre avec bouton de repli
          GestureDetector(
            onTap: () {
              setState(() {
                _isStatsExpanded = !_isStatsExpanded;
              });
            },
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Row(
                children: [
                  Icon(
                    _isStatsExpanded ? Icons.keyboard_arrow_up : Icons.keyboard_arrow_down,
                    color: Colors.grey[600],
                    size: 20,
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'Statistiques de la plateforme',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      color: Colors.grey[700],
                    ),
                  ),
                ],
              ),
            ),
          ),
          // Contenu des statistiques avec animation
          AnimatedContainer(
            duration: const Duration(milliseconds: 300),
            height: _isStatsExpanded ? null : 0,
            child: _isStatsExpanded ? _buildStatsCards(stats) : null,
          ),
        ],
      ),
    );
  }

  Widget _buildStatsCards(Map<String, dynamic> stats) {
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      childAspectRatio: 1.5,
      children: [
        _buildStatCard(
          'Utilisateurs',
          stats['users']?.toString() ?? '0',
          Icons.people,
          const Color(0xFF3B82F6),
        ),
        _buildStatCard(
          'Enseignants',
          stats['teachers']?.toString() ?? '0',
          Icons.school,
          const Color(0xFF10B981),
        ),
        _buildStatCard(
          'Élèves',
          stats['students']?.toString() ?? '0',
          Icons.person,
          const Color(0xFFF59E0B),
        ),
        _buildStatCard(
          'Clubs',
          stats['clubs']?.toString() ?? '0',
          Icons.location_on,
          const Color(0xFFEF4444),
        ),
      ],
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              size: 24,
              color: color,
            ),
            const SizedBox(height: 6),
            Text(
              value,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              title,
              style: const TextStyle(
                fontSize: 12,
                color: Color(0xFF6B7280),
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRecentActivities(AdminActivitiesState activitiesState) {
    if (activitiesState.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (activitiesState.activities.isEmpty) {
      return const Card(
        child: Padding(
          padding: EdgeInsets.all(16),
          child: Text(
            'Aucune activité récente',
            style: TextStyle(
              color: Color(0xFF6B7280),
            ),
          ),
        ),
      );
    }

    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: ListView.builder(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        itemCount: activitiesState.activities.length,
        itemBuilder: (context, index) {
          final activity = activitiesState.activities[index];
          return ListTile(
            leading: CircleAvatar(
              backgroundColor: const Color(0xFF3B82F6),
              child: Icon(
                _getActivityIcon(activity['icon'] ?? ''),
                color: Colors.white,
                size: 20,
              ),
            ),
            title: Text(
              activity['message'] ?? 'Activité inconnue',
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w500,
              ),
            ),
            subtitle: Text(
              '${activity['user'] ?? 'Système'} • ${activity['time'] ?? ''}',
              style: const TextStyle(
                fontSize: 12,
                color: Color(0xFF6B7280),
              ),
            ),
          );
        },
      ),
    );
  }

  IconData _getActivityIcon(String iconName) {
    switch (iconName) {
      case 'user_add':
        return Icons.person_add;
      case 'user_edit':
        return Icons.edit;
      case 'user_delete':
        return Icons.delete;
      case 'settings':
        return Icons.settings;
      case 'club_add':
        return Icons.add_location;
      case 'club_edit':
        return Icons.edit_location;
      case 'club_delete':
        return Icons.delete_location;
      default:
        return Icons.info;
    }
  }
}
