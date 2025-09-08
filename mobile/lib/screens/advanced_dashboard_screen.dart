import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/equestrian_models.dart';
import '../providers/equestrian_provider.dart';
import '../providers/auth_provider.dart';
import '../widgets/performance_chart.dart';
import '../widgets/discipline_selector.dart';
import '../widgets/metrics_card.dart';
import '../widgets/goals_widget.dart';
import '../widgets/alerts_widget.dart';

class AdvancedDashboardScreen extends ConsumerStatefulWidget {
  const AdvancedDashboardScreen({super.key});

  @override
  ConsumerState<AdvancedDashboardScreen> createState() => _AdvancedDashboardScreenState();
}

class _AdvancedDashboardScreenState extends ConsumerState<AdvancedDashboardScreen> {
  String _selectedDiscipline = 'all';
  String _selectedPeriod = 'month';
  bool _isExpanded = true;

  @override
  void initState() {
    super.initState();
    _loadDashboardData();
  }

  void _loadDashboardData() {
    final user = ref.read(authProvider).user;
    if (user != null) {
      ref.read(dashboardProvider.notifier).loadDashboardStats(
        userId: user.id,
        userType: user.role,
        startDate: _getStartDate(),
        endDate: DateTime.now(),
      );
      
      ref.read(performanceMetricsProvider.notifier).loadMetrics(
        studentId: user.role == 'student' ? user.id : null,
        disciplineId: _selectedDiscipline != 'all' ? int.tryParse(_selectedDiscipline) : null,
        startDate: _getStartDate(),
        endDate: DateTime.now(),
      );
      
      ref.read(trainingGoalsProvider.notifier).loadGoals(
        studentId: user.role == 'student' ? user.id : null,
        disciplineId: _selectedDiscipline != 'all' ? int.tryParse(_selectedDiscipline) : null,
      );
    }
  }

  DateTime _getStartDate() {
    final now = DateTime.now();
    switch (_selectedPeriod) {
      case 'week':
        return now.subtract(const Duration(days: 7));
      case 'month':
        return DateTime(now.year, now.month - 1, now.day);
      case 'quarter':
        return DateTime(now.year, now.month - 3, now.day);
      case 'year':
        return DateTime(now.year - 1, now.month, now.day);
      default:
        return DateTime(now.year, now.month - 1, now.day);
    }
  }

  @override
  Widget build(BuildContext context) {
    // Vérifier si nous sommes sur le web
    if (!kIsWeb) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Tableau de bord avancé'),
          backgroundColor: const Color(0xFF1E3A8A),
          foregroundColor: Colors.white,
        ),
        body: const Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.computer,
                size: 64,
                color: Color(0xFF6B7280),
              ),
              SizedBox(height: 16),
              Text(
                'Tableau de bord avancé',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1E3A8A),
                ),
              ),
              SizedBox(height: 8),
              Text(
                'Cette fonctionnalité est uniquement disponible\nsur la version web',
                style: TextStyle(
                  fontSize: 16,
                  color: Color(0xFF6B7280),
                ),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: 16),
              Text(
                'Veuillez ouvrir l\'application dans votre navigateur\npour accéder aux tableaux de bord avancés',
                style: TextStyle(
                  fontSize: 14,
                  color: Color(0xFF9CA3AF),
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      );
    }

    final user = ref.watch(authProvider).user;
    final dashboardState = ref.watch(dashboardProvider);
    final metricsState = ref.watch(performanceMetricsProvider);
    final goalsState = ref.watch(trainingGoalsProvider);

    if (user == null) {
      return const Scaffold(
        body: Center(child: Text('Utilisateur non connecté')),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text('Tableau de Bord ${user.role == 'student' ? 'Élève' : 'Professeur'}'),
        backgroundColor: const Color(0xFF1E3A8A),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: Icon(_isExpanded ? Icons.expand_less : Icons.expand_more),
            onPressed: () => setState(() => _isExpanded = !_isExpanded),
          ),
          PopupMenuButton<String>(
            onSelected: (period) {
              setState(() {
                _selectedPeriod = period;
                _loadDashboardData();
              });
            },
            itemBuilder: (context) => [
              const PopupMenuItem(value: 'week', child: Text('Semaine')),
              const PopupMenuItem(value: 'month', child: Text('Mois')),
              const PopupMenuItem(value: 'quarter', child: Text('Trimestre')),
              const PopupMenuItem(value: 'year', child: Text('Année')),
            ],
            child: const Padding(
              padding: EdgeInsets.all(8.0),
              child: Icon(Icons.calendar_today),
            ),
          ),
        ],
      ),
      body: kIsWeb ? _buildWebLayout(dashboardState, metricsState, goalsState) : _buildMobileLayout(dashboardState, metricsState, goalsState),
    );
  }

  Widget _buildWebLayout(DashboardState dashboardState, PerformanceMetricsState metricsState, TrainingGoalsState goalsState) {
    return Row(
      children: [
        // Sidebar avec filtres et navigation
        Container(
          width: 280,
          decoration: BoxDecoration(
            color: Colors.grey.shade50,
            border: Border(right: BorderSide(color: Colors.grey.shade300)),
          ),
          child: Column(
            children: [
              // Sélecteur de discipline
              DisciplineSelector(
                selectedDiscipline: _selectedDiscipline,
                onDisciplineChanged: (discipline) {
                  setState(() {
                    _selectedDiscipline = discipline;
                    _loadDashboardData();
                  });
                },
              ),
              
              const Divider(),
              
              // Navigation rapide
              _buildQuickNavigation(),
              
              const Divider(),
              
              // Alertes
              Expanded(
                child: AlertsWidget(
                  alerts: dashboardState.stats?.alerts ?? {},
                ),
              ),
            ],
          ),
        ),
        
        // Contenu principal
        Expanded(
          child: _buildMainContent(dashboardState, metricsState, goalsState),
        ),
      ],
    );
  }

  Widget _buildMobileLayout(DashboardState dashboardState, PerformanceMetricsState metricsState, TrainingGoalsState goalsState) {
    return Column(
      children: [
        // Filtres en haut
        Container(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Expanded(
                child: DisciplineSelector(
                  selectedDiscipline: _selectedDiscipline,
                  onDisciplineChanged: (discipline) {
                    setState(() {
                      _selectedDiscipline = discipline;
                      _loadDashboardData();
                    });
                  },
                ),
              ),
              const SizedBox(width: 16),
              IconButton(
                icon: Icon(_isExpanded ? Icons.expand_less : Icons.expand_more),
                onPressed: () => setState(() => _isExpanded = !_isExpanded),
              ),
            ],
          ),
        ),
        
        // Contenu principal
        Expanded(
          child: _buildMainContent(dashboardState, metricsState, goalsState),
        ),
      ],
    );
  }

  Widget _buildMainContent(DashboardState dashboardState, PerformanceMetricsState metricsState, TrainingGoalsState goalsState) {
    if (dashboardState.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (dashboardState.error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error, size: 64, color: Colors.red),
            const SizedBox(height: 16),
            Text('Erreur: ${dashboardState.error}'),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _loadDashboardData,
              child: const Text('Réessayer'),
            ),
          ],
        ),
      );
    }

    final stats = dashboardState.stats;
    if (stats == null) {
      return const Center(child: Text('Aucune donnée disponible'));
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // En-tête avec statistiques principales
          if (_isExpanded) _buildMainStats(stats),
          
          const SizedBox(height: 24),
          
          // Grille de widgets
          kIsWeb ? _buildWebGrid(stats, metricsState, goalsState) : _buildMobileGrid(stats, metricsState, goalsState),
        ],
      ),
    );
  }

  Widget _buildMainStats(DashboardStats stats) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Vue d\'ensemble',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: MetricsCard(
                    title: 'Cours Total',
                    value: '${stats.totalLessons}',
                    subtitle: '${stats.completedLessons} terminés',
                    icon: Icons.school,
                    color: Colors.blue,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: MetricsCard(
                    title: 'Score Moyen',
                    value: '${stats.averageScore.toStringAsFixed(1)}',
                    subtitle: 'sur 10',
                    icon: Icons.star,
                    color: Colors.amber,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: MetricsCard(
                    title: 'Heures Total',
                    value: '${stats.totalHours.toStringAsFixed(1)}h',
                    subtitle: 'd\'entraînement',
                    icon: Icons.timer,
                    color: Colors.green,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: MetricsCard(
                    title: 'Distance',
                    value: '${stats.totalDistance.toStringAsFixed(1)}km',
                    subtitle: 'parcourue',
                    icon: Icons.route,
                    color: Colors.purple,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildWebGrid(DashboardStats stats, PerformanceMetricsState metricsState, TrainingGoalsState goalsState) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Colonne gauche
        Expanded(
          flex: 2,
          child: Column(
            children: [
              // Graphique de performance
              PerformanceChart(
                metrics: metricsState.metrics,
                isLoading: metricsState.isLoading,
              ),
              const SizedBox(height: 16),
              
              // Objectifs d'entraînement
              GoalsWidget(
                goals: goalsState.goals,
                isLoading: goalsState.isLoading,
              ),
            ],
          ),
        ),
        
        const SizedBox(width: 16),
        
        // Colonne droite
        Expanded(
          flex: 1,
          child: Column(
            children: [
              // Progression par discipline
              _buildDisciplineProgress(stats),
              const SizedBox(height: 16),
              
              // Métriques récentes
              _buildRecentMetrics(metricsState),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildMobileGrid(DashboardStats stats, PerformanceMetricsState metricsState, TrainingGoalsState goalsState) {
    return Column(
      children: [
        // Graphique de performance
        PerformanceChart(
          metrics: metricsState.metrics,
          isLoading: metricsState.isLoading,
        ),
        const SizedBox(height: 16),
        
        // Objectifs d'entraînement
        GoalsWidget(
          goals: goalsState.goals,
          isLoading: goalsState.isLoading,
        ),
        const SizedBox(height: 16),
        
        // Progression par discipline
        _buildDisciplineProgress(stats),
        const SizedBox(height: 16),
        
        // Métriques récentes
        _buildRecentMetrics(metricsState),
      ],
    );
  }

  Widget _buildDisciplineProgress(DashboardStats stats) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Progression par Discipline',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            ...stats.progressByDiscipline.entries.map((entry) {
              final progress = entry.value;
              final color = progress >= 80 ? Colors.green : 
                          progress >= 60 ? Colors.orange : Colors.red;
              
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(entry.key),
                        Text('${progress.toStringAsFixed(1)}%'),
                      ],
                    ),
                    const SizedBox(height: 4),
                    LinearProgressIndicator(
                      value: progress / 100,
                      backgroundColor: Colors.grey.shade200,
                      valueColor: AlwaysStoppedAnimation<Color>(color),
                    ),
                  ],
                ),
              );
            }).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildRecentMetrics(PerformanceMetricsState metricsState) {
    final recentMetrics = metricsState.metrics.take(5).toList();
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Métriques Récentes',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            if (recentMetrics.isEmpty)
              const Text('Aucune métrique récente')
            else
              ...recentMetrics.map((metric) {
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Row(
                    children: [
                      Icon(Icons.trending_up, color: Colors.green),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              '${metric.date.day}/${metric.date.month}/${metric.date.year}',
                              style: const TextStyle(fontWeight: FontWeight.bold),
                            ),
                            if (metric.overallScore != null)
                              Text('Score: ${metric.overallScore!.toStringAsFixed(1)}'),
                          ],
                        ),
                      ),
                      if (metric.averageSpeed != null)
                        Text('${metric.averageSpeed!.toStringAsFixed(1)} km/h'),
                    ],
                  ),
                );
              }).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickNavigation() {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Navigation Rapide',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 12),
          _buildNavItem(Icons.analytics, 'Analyses', () {}),
          _buildNavItem(Icons.goal, 'Objectifs', () {}),
          _buildNavItem(Icons.video_library, 'Vidéos', () {}),
          _buildNavItem(Icons.gps_fixed, 'GPS', () {}),
          _buildNavItem(Icons.assessment, 'Évaluations', () {}),
        ],
      ),
    );
  }

  Widget _buildNavItem(IconData icon, String title, VoidCallback onTap) {
    return ListTile(
      leading: Icon(icon, size: 20),
      title: Text(title, style: const TextStyle(fontSize: 14)),
      onTap: onTap,
      dense: true,
    );
  }
}
