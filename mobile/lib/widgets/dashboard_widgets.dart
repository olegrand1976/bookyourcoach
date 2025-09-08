import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/equestrian_models.dart';
import '../providers/equestrian_provider.dart';

// Widget pour sélectionner les disciplines
class DisciplineSelector extends ConsumerWidget {
  final String selectedDiscipline;
  final Function(String) onDisciplineChanged;

  const DisciplineSelector({
    super.key,
    required this.selectedDiscipline,
    required this.onDisciplineChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final disciplinesState = ref.watch(disciplinesProvider);

    return Card(
      margin: const EdgeInsets.all(8),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Disciplines',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            if (disciplinesState.isLoading)
              const Center(child: CircularProgressIndicator())
            else if (disciplinesState.error != null)
              Text(
                'Erreur: ${disciplinesState.error}',
                style: const TextStyle(color: Colors.red),
              )
            else
              Column(
                children: [
                  // Option "Toutes les disciplines"
                  _buildDisciplineOption('all', 'Toutes les disciplines', Icons.all_inclusive),
                  const SizedBox(height: 8),
                  // Disciplines disponibles
                  ...disciplinesState.disciplines.map((discipline) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: _buildDisciplineOption(
                        discipline.id.toString(),
                        discipline.name,
                        _getDisciplineIcon(discipline.code),
                      ),
                    );
                  }).toList(),
                ],
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildDisciplineOption(String value, String label, IconData icon) {
    final isSelected = selectedDiscipline == value;
    
    return InkWell(
      onTap: () => onDisciplineChanged(value),
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF1E3A8A).withOpacity(0.1) : null,
          borderRadius: BorderRadius.circular(8),
          border: isSelected ? Border.all(color: const Color(0xFF1E3A8A)) : null,
        ),
        child: Row(
          children: [
            Icon(
              icon,
              size: 20,
              color: isSelected ? const Color(0xFF1E3A8A) : Colors.grey,
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                label,
                style: TextStyle(
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                  color: isSelected ? const Color(0xFF1E3A8A) : null,
                ),
              ),
            ),
            if (isSelected)
              const Icon(Icons.check, size: 16, color: Color(0xFF1E3A8A)),
          ],
        ),
      ),
    );
  }

  IconData _getDisciplineIcon(String code) {
    switch (code) {
      case 'dressage':
        return Icons.auto_awesome;
      case 'jumping':
        return Icons.trending_up;
      case 'eventing':
        return Icons.terrain;
      case 'western':
        return Icons.landscape;
      case 'endurance':
        return Icons.route;
      default:
        return Icons.sports;
    }
  }
}

// Widget pour afficher les métriques
class MetricsCard extends StatelessWidget {
  final String title;
  final String value;
  final String subtitle;
  final IconData icon;
  final Color color;

  const MetricsCard({
    super.key,
    required this.title,
    required this.value,
    required this.subtitle,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: color, size: 24),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    title,
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      color: Colors.grey.shade600,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              value,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            Text(
              subtitle,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Colors.grey.shade500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// Widget pour les graphiques de performance
class PerformanceChart extends StatelessWidget {
  final List<PerformanceMetrics> metrics;
  final bool isLoading;

  const PerformanceChart({
    super.key,
    required this.metrics,
    required this.isLoading,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Évolution des Performances',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            if (isLoading)
              const Center(child: CircularProgressIndicator())
            else if (metrics.isEmpty)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(32),
                  child: Text('Aucune donnée de performance disponible'),
                ),
              )
            else
              SizedBox(
                height: 200,
                child: _buildChart(context),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildChart(BuildContext context) {
    // Tri des métriques par date
    final sortedMetrics = List<PerformanceMetrics>.from(metrics)
      ..sort((a, b) => a.date.compareTo(b.date));

    return CustomPaint(
      size: const Size(double.infinity, 200),
      painter: PerformanceChartPainter(sortedMetrics),
    );
  }
}

// Peintre personnalisé pour le graphique
class PerformanceChartPainter extends CustomPainter {
  final List<PerformanceMetrics> metrics;

  PerformanceChartPainter(this.metrics);

  @override
  void paint(Canvas canvas, Size size) {
    if (metrics.isEmpty) return;

    final paint = Paint()
      ..color = const Color(0xFF1E3A8A)
      ..strokeWidth = 2
      ..style = PaintingStyle.stroke;

    final fillPaint = Paint()
      ..color = const Color(0xFF1E3A8A).withOpacity(0.1)
      ..style = PaintingStyle.fill;

    final path = Path();
    final fillPath = Path();

    final width = size.width;
    final height = size.height;
    final padding = 20.0;
    final chartWidth = width - 2 * padding;
    final chartHeight = height - 2 * padding;

    // Trouver les valeurs min/max pour l'échelle
    double minScore = double.infinity;
    double maxScore = -double.infinity;
    
    for (final metric in metrics) {
      if (metric.overallScore != null) {
        minScore = minScore > metric.overallScore! ? metric.overallScore! : minScore;
        maxScore = maxScore < metric.overallScore! ? metric.overallScore! : maxScore;
      }
    }

    if (minScore == double.infinity) return;

    // Ajuster l'échelle
    final scoreRange = maxScore - minScore;
    final adjustedMin = minScore - scoreRange * 0.1;
    final adjustedMax = maxScore + scoreRange * 0.1;
    final adjustedRange = adjustedMax - adjustedMin;

    // Dessiner les points et lignes
    for (int i = 0; i < metrics.length; i++) {
      final metric = metrics[i];
      if (metric.overallScore == null) continue;

      final x = padding + (i / (metrics.length - 1)) * chartWidth;
      final y = height - padding - ((metric.overallScore! - adjustedMin) / adjustedRange) * chartHeight;

      if (i == 0) {
        path.moveTo(x, y);
        fillPath.moveTo(x, height - padding);
        fillPath.lineTo(x, y);
      } else {
        path.lineTo(x, y);
        fillPath.lineTo(x, y);
      }

      // Dessiner les points
      final pointPaint = Paint()
        ..color = const Color(0xFF1E3A8A)
        ..style = PaintingStyle.fill;
      
      canvas.drawCircle(Offset(x, y), 4, pointPaint);
    }

    // Fermer le chemin de remplissage
    fillPath.lineTo(width - padding, height - padding);
    fillPath.close();

    // Dessiner le remplissage et la ligne
    canvas.drawPath(fillPath, fillPaint);
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => true;
}

// Widget pour les objectifs d'entraînement
class GoalsWidget extends StatelessWidget {
  final List<TrainingGoal> goals;
  final bool isLoading;

  const GoalsWidget({
    super.key,
    required this.goals,
    required this.isLoading,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Objectifs d\'Entraînement',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.add),
                  onPressed: () {
                    // TODO: Ouvrir le dialogue d'ajout d'objectif
                  },
                ),
              ],
            ),
            const SizedBox(height: 16),
            if (isLoading)
              const Center(child: CircularProgressIndicator())
            else if (goals.isEmpty)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(32),
                  child: Text('Aucun objectif défini'),
                ),
              )
            else
              ...goals.map((goal) => _buildGoalCard(context, goal)).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildGoalCard(BuildContext context, TrainingGoal goal) {
    final isOverdue = goal.isOverdue;
    final isCompleted = goal.isCompleted;
    
    Color statusColor;
    IconData statusIcon;
    
    if (isCompleted) {
      statusColor = Colors.green;
      statusIcon = Icons.check_circle;
    } else if (isOverdue) {
      statusColor = Colors.red;
      statusIcon = Icons.warning;
    } else {
      statusColor = Colors.orange;
      statusIcon = Icons.schedule;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(statusIcon, color: statusColor, size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    goal.title,
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
                Text(
                  '${goal.progressPercentage.toStringAsFixed(0)}%',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: statusColor,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              goal.description,
              style: TextStyle(color: Colors.grey.shade600),
            ),
            const SizedBox(height: 8),
            LinearProgressIndicator(
              value: goal.progressPercentage / 100,
              backgroundColor: Colors.grey.shade200,
              valueColor: AlwaysStoppedAnimation<Color>(statusColor),
            ),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Niveau cible: ${goal.targetLevel}',
                  style: const TextStyle(fontSize: 12),
                ),
                Text(
                  'Échéance: ${goal.targetDate.day}/${goal.targetDate.month}/${goal.targetDate.year}',
                  style: const TextStyle(fontSize: 12),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// Widget pour les alertes
class AlertsWidget extends StatelessWidget {
  final Map<String, dynamic> alerts;

  const AlertsWidget({
    super.key,
    required this.alerts,
  });

  @override
  Widget build(BuildContext context) {
    final alertList = alerts.entries.toList();

    return Card(
      margin: const EdgeInsets.all(8),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Alertes',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            if (alertList.isEmpty)
              const Text(
                'Aucune alerte',
                style: TextStyle(color: Colors.grey),
              )
            else
              ...alertList.map((alert) => _buildAlertItem(context, alert)).toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildAlertItem(BuildContext context, MapEntry<String, dynamic> alert) {
    final type = alert.key;
    final data = alert.value;
    
    IconData icon;
    Color color;
    String message;

    switch (type) {
      case 'overload':
        icon = Icons.warning;
        color = Colors.orange;
        message = 'Surcharge détectée pour ${data['horse_name']}';
        break;
      case 'goal_deadline':
        icon = Icons.schedule;
        color = Colors.red;
        message = 'Objectif à échéance: ${data['goal_title']}';
        break;
      case 'performance_drop':
        icon = Icons.trending_down;
        color = Colors.red;
        message = 'Baisse de performance détectée';
        break;
      case 'achievement':
        icon = Icons.emoji_events;
        color = Colors.green;
        message = 'Nouveau badge obtenu: ${data['badge_name']}';
        break;
      default:
        icon = Icons.info;
        color = Colors.blue;
        message = data.toString();
    }

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, color: color, size: 16),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(fontSize: 12),
            ),
          ),
        ],
      ),
    );
  }
}
