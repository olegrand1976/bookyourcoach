import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/equestrian_models.dart';
import '../providers/equestrian_provider.dart';
import '../providers/auth_provider.dart';

class DisciplinesManagementScreen extends ConsumerStatefulWidget {
  const DisciplinesManagementScreen({super.key});

  @override
  ConsumerState<DisciplinesManagementScreen> createState() => _DisciplinesManagementScreenState();
}

class _DisciplinesManagementScreenState extends ConsumerState<DisciplinesManagementScreen> {
  @override
  void initState() {
    super.initState();
    _loadData();
  }

  void _loadData() {
    ref.read(disciplinesProvider.notifier).loadDisciplines();
    
    final user = ref.read(authProvider).user;
    if (user != null) {
      ref.read(studentDisciplinesProvider.notifier).loadStudentDisciplines(user.id);
    }
  }

  @override
  Widget build(BuildContext context) {
    // Vérifier si nous sommes sur le web
    if (!kIsWeb) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Gestion des disciplines'),
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
                'Gestion des disciplines',
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
                'Veuillez ouvrir l\'application dans votre navigateur\npour gérer vos disciplines',
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
    final disciplinesState = ref.watch(disciplinesProvider);
    final studentDisciplinesState = ref.watch(studentDisciplinesProvider);

    if (user == null) {
      return const Scaffold(
        body: Center(child: Text('Utilisateur non connecté')),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Gestion des Disciplines'),
        backgroundColor: const Color(0xFF1E3A8A),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _showAddDisciplineDialog(context, user.id),
          ),
        ],
      ),
      body: kIsWeb ? _buildWebLayout(disciplinesState, studentDisciplinesState) : _buildMobileLayout(disciplinesState, studentDisciplinesState),
    );
  }

  Widget _buildWebLayout(DisciplinesState disciplinesState, StudentDisciplinesState studentDisciplinesState) {
    return Row(
      children: [
        // Sidebar avec disciplines disponibles
        Container(
          width: 350,
          decoration: BoxDecoration(
            color: Colors.grey.shade50,
            border: Border(right: BorderSide(color: Colors.grey.shade300)),
          ),
          child: _buildAvailableDisciplines(disciplinesState),
        ),
        
        // Contenu principal avec disciplines de l'étudiant
        Expanded(
          child: _buildStudentDisciplines(studentDisciplinesState),
        ),
      ],
    );
  }

  Widget _buildMobileLayout(DisciplinesState disciplinesState, StudentDisciplinesState studentDisciplinesState) {
    return DefaultTabController(
      length: 2,
      child: Column(
        children: [
          const TabBar(
            tabs: [
              Tab(text: 'Mes Disciplines'),
              Tab(text: 'Disponibles'),
            ],
            labelColor: Color(0xFF1E3A8A),
            unselectedLabelColor: Colors.grey,
          ),
          Expanded(
            child: TabBarView(
              children: [
                _buildStudentDisciplines(studentDisciplinesState),
                _buildAvailableDisciplines(disciplinesState),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAvailableDisciplines(DisciplinesState disciplinesState) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Disciplines Disponibles',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 16),
          if (disciplinesState.isLoading)
            const Center(child: CircularProgressIndicator())
          else if (disciplinesState.error != null)
            Center(
              child: Column(
                children: [
                  const Icon(Icons.error, color: Colors.red, size: 64),
                  const SizedBox(height: 16),
                  Text('Erreur: ${disciplinesState.error}'),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _loadData,
                    child: const Text('Réessayer'),
                  ),
                ],
              ),
            )
          else
            Expanded(
              child: GridView.builder(
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  childAspectRatio: 1.2,
                  crossAxisSpacing: 16,
                  mainAxisSpacing: 16,
                ),
                itemCount: disciplinesState.disciplines.length,
                itemBuilder: (context, index) {
                  final discipline = disciplinesState.disciplines[index];
                  return _buildDisciplineCard(discipline, false);
                },
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildStudentDisciplines(StudentDisciplinesState studentDisciplinesState) {
    final user = ref.read(authProvider).user;
    final studentDisciplines = user != null 
        ? studentDisciplinesState.getDisciplinesForStudent(user.id)
        : <StudentDiscipline>[];

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Mes Disciplines',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 16),
          if (studentDisciplinesState.isLoading)
            const Center(child: CircularProgressIndicator())
          else if (studentDisciplinesState.error != null)
            Center(
              child: Column(
                children: [
                  const Icon(Icons.error, color: Colors.red, size: 64),
                  const SizedBox(height: 16),
                  Text('Erreur: ${studentDisciplinesState.error}'),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _loadData,
                    child: const Text('Réessayer'),
                  ),
                ],
              ),
            )
          else if (studentDisciplines.isEmpty)
            Center(
              child: Column(
                children: [
                  const Icon(Icons.sports, size: 64, color: Colors.grey),
                  const SizedBox(height: 16),
                  const Text('Aucune discipline sélectionnée'),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () {
                      final user = ref.read(authProvider).user;
                      if (user != null) {
                        _showAddDisciplineDialog(context, user.id);
                      }
                    },
                    child: const Text('Ajouter une discipline'),
                  ),
                ],
              ),
            )
          else
            Expanded(
              child: ListView.builder(
                itemCount: studentDisciplines.length,
                itemBuilder: (context, index) {
                  final studentDiscipline = studentDisciplines[index];
                  return _buildStudentDisciplineCard(studentDiscipline);
                },
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildDisciplineCard(EquestrianDiscipline discipline, bool isStudentDiscipline) {
    return Card(
      child: InkWell(
        onTap: isStudentDiscipline ? null : () {
          final user = ref.read(authProvider).user;
          if (user != null) {
            _showAddDisciplineDialog(context, user.id, discipline: discipline);
          }
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(
                    _getDisciplineIcon(discipline.code),
                    size: 32,
                    color: const Color(0xFF1E3A8A),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      discipline.name,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                discipline.description,
                style: TextStyle(
                  color: Colors.grey.shade600,
                  fontSize: 14,
                ),
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 4,
                children: discipline.levels.map((level) {
                  return Chip(
                    label: Text(
                      level,
                      style: const TextStyle(fontSize: 10),
                    ),
                    backgroundColor: Colors.grey.shade200,
                  );
                }).toList(),
              ),
              if (!isStudentDiscipline) ...[
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () {
                      final user = ref.read(authProvider).user;
                      if (user != null) {
                        _showAddDisciplineDialog(context, user.id, discipline: discipline);
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1E3A8A),
                      foregroundColor: Colors.white,
                    ),
                    child: const Text('Ajouter'),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStudentDisciplineCard(StudentDiscipline studentDiscipline) {
    final discipline = studentDiscipline.discipline;
    if (discipline == null) return const SizedBox.shrink();

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  _getDisciplineIcon(discipline.code),
                  size: 24,
                  color: const Color(0xFF1E3A8A),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    discipline.name,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                PopupMenuButton<String>(
                  onSelected: (value) {
                    switch (value) {
                      case 'edit':
                        _showEditDisciplineDialog(context, studentDiscipline);
                        break;
                      case 'remove':
                        _showRemoveDisciplineDialog(context, studentDiscipline);
                        break;
                    }
                  },
                  itemBuilder: (context) => [
                    const PopupMenuItem(
                      value: 'edit',
                      child: Row(
                        children: [
                          Icon(Icons.edit),
                          SizedBox(width: 8),
                          Text('Modifier'),
                        ],
                      ),
                    ),
                    const PopupMenuItem(
                      value: 'remove',
                      child: Row(
                        children: [
                          Icon(Icons.delete, color: Colors.red),
                          SizedBox(width: 8),
                          Text('Supprimer', style: TextStyle(color: Colors.red)),
                        ],
                      ),
                    ),
                  ],
                  child: const Icon(Icons.more_vert),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _buildInfoItem('Niveau actuel', studentDiscipline.currentLevel),
                ),
                Expanded(
                  child: _buildInfoItem('Niveau cible', studentDiscipline.targetLevel ?? 'Non défini'),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: _buildInfoItem('Score actuel', 
                    studentDiscipline.currentScore?.toStringAsFixed(1) ?? 'Non évalué'),
                ),
                Expanded(
                  child: _buildInfoItem('Score cible', 
                    studentDiscipline.targetScore?.toStringAsFixed(1) ?? 'Non défini'),
                ),
              ],
            ),
            if (studentDiscipline.notes?.isNotEmpty == true) ...[
              const SizedBox(height: 8),
              Text(
                'Notes: ${studentDiscipline.notes}',
                style: TextStyle(
                  color: Colors.grey.shade600,
                  fontSize: 12,
                ),
              ),
            ],
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: LinearProgressIndicator(
                    value: _calculateProgress(studentDiscipline),
                    backgroundColor: Colors.grey.shade200,
                    valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF1E3A8A)),
                  ),
                ),
                const SizedBox(width: 12),
                Text(
                  '${(_calculateProgress(studentDiscipline) * 100).toStringAsFixed(0)}%',
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoItem(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: Colors.grey.shade600,
          ),
        ),
        Text(
          value,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
          ),
        ),
      ],
    );
  }

  double _calculateProgress(StudentDiscipline studentDiscipline) {
    // Logique simplifiée pour calculer le progrès
    if (studentDiscipline.currentScore == null || studentDiscipline.targetScore == null) {
      return 0.0;
    }
    
    final progress = studentDiscipline.currentScore! / studentDiscipline.targetScore!;
    return progress.clamp(0.0, 1.0);
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

  void _showAddDisciplineDialog(BuildContext context, int studentId, {EquestrianDiscipline? discipline}) {
    final disciplinesState = ref.read(disciplinesProvider);
    final availableDisciplines = disciplinesState.disciplines;
    
    if (availableDisciplines.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Aucune discipline disponible')),
      );
      return;
    }

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(discipline != null ? 'Ajouter ${discipline.name}' : 'Ajouter une discipline'),
        content: discipline != null 
            ? _buildDisciplineForm(context, studentId, discipline)
            : _buildDisciplineSelector(context, studentId, availableDisciplines),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
        ],
      ),
    );
  }

  Widget _buildDisciplineSelector(BuildContext context, int studentId, List<EquestrianDiscipline> disciplines) {
    return SizedBox(
      width: 400,
      height: 300,
      child: ListView.builder(
        itemCount: disciplines.length,
        itemBuilder: (context, index) {
          final discipline = disciplines[index];
          return ListTile(
            leading: Icon(_getDisciplineIcon(discipline.code)),
            title: Text(discipline.name),
            subtitle: Text(discipline.description),
            onTap: () {
              Navigator.of(context).pop();
              _showAddDisciplineDialog(context, studentId, discipline: discipline);
            },
          );
        },
      ),
    );
  }

  Widget _buildDisciplineForm(BuildContext context, int studentId, EquestrianDiscipline discipline) {
    final formKey = GlobalKey<FormState>();
    final currentLevelController = TextEditingController(text: discipline.levels.first);
    final targetLevelController = TextEditingController();
    final notesController = TextEditingController();

    return Form(
      key: formKey,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text('Discipline: ${discipline.name}'),
          const SizedBox(height: 16),
          DropdownButtonFormField<String>(
            value: discipline.levels.first,
            decoration: const InputDecoration(
              labelText: 'Niveau actuel',
              border: OutlineInputBorder(),
            ),
            items: discipline.levels.map((level) {
              return DropdownMenuItem(value: level, child: Text(level));
            }).toList(),
            onChanged: (value) => currentLevelController.text = value ?? '',
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<String>(
            decoration: const InputDecoration(
              labelText: 'Niveau cible (optionnel)',
              border: OutlineInputBorder(),
            ),
            items: discipline.levels.map((level) {
              return DropdownMenuItem(value: level, child: Text(level));
            }).toList(),
            onChanged: (value) => targetLevelController.text = value ?? '',
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: notesController,
            decoration: const InputDecoration(
              labelText: 'Notes (optionnel)',
              border: OutlineInputBorder(),
            ),
            maxLines: 3,
          ),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              TextButton(
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('Annuler'),
              ),
              const SizedBox(width: 8),
              ElevatedButton(
                onPressed: () async {
                  if (formKey.currentState!.validate()) {
                    try {
                      await ref.read(studentDisciplinesProvider.notifier).addStudentDiscipline(
                        studentId,
                        {
                          'discipline_id': discipline.id,
                          'current_level': currentLevelController.text,
                          'target_level': targetLevelController.text.isNotEmpty ? targetLevelController.text : null,
                          'notes': notesController.text.isNotEmpty ? notesController.text : null,
                        },
                      );
                      Navigator.of(context).pop();
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text('${discipline.name} ajoutée avec succès')),
                      );
                    } catch (e) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(content: Text('Erreur: $e')),
                      );
                    }
                  }
                },
                child: const Text('Ajouter'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  void _showEditDisciplineDialog(BuildContext context, StudentDiscipline studentDiscipline) {
    // TODO: Implémenter l'édition de discipline
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Fonctionnalité d\'édition à implémenter')),
    );
  }

  void _showRemoveDisciplineDialog(BuildContext context, StudentDiscipline studentDiscipline) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Supprimer la discipline'),
        content: Text('Êtes-vous sûr de vouloir supprimer ${studentDiscipline.discipline?.name ?? 'cette discipline'} ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () async {
              try {
                final user = ref.read(authProvider).user;
                if (user != null) {
                  await ref.read(studentDisciplinesProvider.notifier).removeStudentDiscipline(
                    user.id,
                    studentDiscipline.disciplineId,
                  );
                  Navigator.of(context).pop();
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Discipline supprimée avec succès')),
                  );
                }
              } catch (e) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text('Erreur: $e')),
                );
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );
  }
}
