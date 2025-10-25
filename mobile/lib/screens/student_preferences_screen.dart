import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/discipline.dart';
import '../providers/preferences_provider.dart';

class StudentPreferencesScreen extends ConsumerStatefulWidget {
  const StudentPreferencesScreen({super.key});

  @override
  ConsumerState<StudentPreferencesScreen> createState() => _StudentPreferencesScreenState();
}

class _StudentPreferencesScreenState extends ConsumerState<StudentPreferencesScreen> {
  @override
  void initState() {
    super.initState();
    // Charger les données au démarrage
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(disciplinesProvider.notifier).loadDisciplines();
      ref.read(studentPreferencesProvider.notifier).loadPreferences();
    });
  }

  @override
  Widget build(BuildContext context) {
    final disciplinesState = ref.watch(disciplinesProvider);
    final preferencesState = ref.watch(studentPreferencesProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Préférences'),
        backgroundColor: Colors.blue[600],
        foregroundColor: Colors.white,
        actions: [
          if (preferencesState.isSaving)
            const Padding(
              padding: EdgeInsets.all(16.0),
              child: SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                ),
              ),
            ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await ref.read(disciplinesProvider.notifier).loadDisciplines();
          await ref.read(studentPreferencesProvider.notifier).loadPreferences();
        },
        child: _buildBody(disciplinesState, preferencesState),
      ),
    );
  }

  Widget _buildBody(DisciplinesState disciplinesState, StudentPreferencesState preferencesState) {
    if (disciplinesState.isLoading || preferencesState.isLoading) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (disciplinesState.error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.red[300]),
            const SizedBox(height: 16),
            Text(
              'Erreur lors du chargement',
              style: TextStyle(fontSize: 18, color: Colors.red[700]),
            ),
            const SizedBox(height: 8),
            Text(
              disciplinesState.error!,
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.red[600]),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () {
                ref.read(disciplinesProvider.notifier).loadDisciplines();
                ref.read(studentPreferencesProvider.notifier).loadPreferences();
              },
              child: const Text('Réessayer'),
            ),
          ],
        ),
      );
    }

    if (disciplinesState.disciplines.isEmpty) {
      return const Center(
        child: Text('Aucune discipline disponible'),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: disciplinesState.disciplines.length,
      itemBuilder: (context, index) {
        final discipline = disciplinesState.disciplines[index];
        return _buildDisciplineCard(discipline, preferencesState);
      },
    );
  }

  Widget _buildDisciplineCard(Discipline discipline, StudentPreferencesState preferencesState) {
    final disciplinePreferences = preferencesState.getPreferencesForDiscipline(discipline.id);
    final hasDisciplinePreference = preferencesState.hasPreferenceForDiscipline(discipline.id);

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: ExpansionTile(
        title: Row(
          children: [
            Icon(
              _getDisciplineIcon(discipline.name),
              color: hasDisciplinePreference ? Colors.blue[600] : Colors.grey[600],
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                discipline.name,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w600,
                  color: hasDisciplinePreference ? Colors.blue[700] : Colors.grey[700],
                ),
              ),
            ),
            if (hasDisciplinePreference)
              Icon(
                Icons.check_circle,
                color: Colors.green[600],
                size: 20,
              ),
          ],
        ),
        subtitle: Text(
          discipline.description,
          style: TextStyle(color: Colors.grey[600]),
        ),
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Bouton pour sélectionner toute la discipline
                _buildDisciplineToggle(discipline, hasDisciplinePreference),
                
                if (hasDisciplinePreference && discipline.courseTypes.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  const Text(
                    'Types de cours préférés :',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  ...discipline.courseTypes.map((courseType) => 
                    _buildCourseTypeTile(discipline.id, courseType, preferencesState)
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDisciplineToggle(Discipline discipline, bool isSelected) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(
        isSelected ? Icons.check_box : Icons.check_box_outline_blank,
        color: isSelected ? Colors.blue[600] : Colors.grey[600],
      ),
      title: Text(
        isSelected ? 'Désélectionner ${discipline.name}' : 'Sélectionner ${discipline.name}',
        style: TextStyle(
          fontWeight: FontWeight.w500,
          color: isSelected ? Colors.blue[700] : Colors.grey[700],
        ),
      ),
      onTap: () {
        if (isSelected) {
          // Supprimer toutes les préférences pour cette discipline
          ref.read(studentPreferencesProvider.notifier).removePreference(
            disciplineId: discipline.id,
          );
        } else {
          // Ajouter une préférence pour la discipline (sans type de cours spécifique)
          ref.read(studentPreferencesProvider.notifier).addPreference(
            disciplineId: discipline.id,
          );
        }
      },
    );
  }

  Widget _buildCourseTypeTile(int disciplineId, CourseType courseType, StudentPreferencesState preferencesState) {
    final isSelected = preferencesState.hasPreferenceForCourseType(disciplineId, courseType.id);

    return Padding(
      padding: const EdgeInsets.only(left: 16, bottom: 8),
      child: ListTile(
        contentPadding: EdgeInsets.zero,
        leading: Icon(
          isSelected ? Icons.check_box : Icons.check_box_outline_blank,
          color: isSelected ? Colors.blue[600] : Colors.grey[600],
          size: 20,
        ),
        title: Text(
          courseType.name,
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: isSelected ? Colors.blue[700] : Colors.grey[700],
          ),
        ),
        subtitle: Text(
          '${courseType.typeDisplay} • ${courseType.durationDisplay}',
          style: TextStyle(
            fontSize: 12,
            color: Colors.grey[600],
          ),
        ),
        trailing: isSelected
            ? Icon(
                Icons.check_circle,
                color: Colors.green[600],
                size: 16,
              )
            : null,
        onTap: () {
          if (isSelected) {
            // Supprimer la préférence pour ce type de cours
            ref.read(studentPreferencesProvider.notifier).removePreference(
              disciplineId: disciplineId,
              courseTypeId: courseType.id,
            );
          } else {
            // Ajouter une préférence pour ce type de cours
            ref.read(studentPreferencesProvider.notifier).addPreference(
              disciplineId: disciplineId,
              courseTypeId: courseType.id,
            );
          }
        },
      ),
    );
  }

  IconData _getDisciplineIcon(String disciplineName) {
    switch (disciplineName.toLowerCase()) {
      case 'équitation':
        return Icons.pets;
      case 'natation':
        return Icons.pool;
      default:
        return Icons.sports;
    }
  }
}