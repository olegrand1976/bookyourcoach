import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/student_provider.dart';

class StudentPreferencesScreen extends ConsumerStatefulWidget {
  const StudentPreferencesScreen({super.key});

  @override
  ConsumerState<StudentPreferencesScreen> createState() => _StudentPreferencesScreenState();
}

class _StudentPreferencesScreenState extends ConsumerState<StudentPreferencesScreen> {
  final Set<String> _selectedDisciplines = {};
  final Set<String> _selectedLevels = {};
  final Set<String> _selectedFormats = {};
  
  // Disciplines disponibles
  final List<String> _availableDisciplines = [
    'Mathématiques',
    'Physique',
    'Chimie',
    'Biologie',
    'Histoire',
    'Géographie',
    'Français',
    'Anglais',
    'Espagnol',
    'Allemand',
    'Philosophie',
    'Économie',
    'Informatique',
    'Musique',
    'Arts plastiques',
    'Sport',
    'Sciences politiques',
    'Psychologie',
    'Sociologie',
    'Droit',
  ];

  // Niveaux disponibles
  final List<String> _availableLevels = [
    'Primaire',
    'Collège',
    'Lycée',
    'Supérieur',
    'Adulte',
  ];

  // Formats disponibles
  final List<String> _availableFormats = [
    'Cours particulier',
    'Cours en groupe',
    'Cours en ligne',
    'Cours en présentiel',
    'Stage intensif',
  ];

  @override
  void initState() {
    super.initState();
    _loadCurrentPreferences();
  }

  void _loadCurrentPreferences() {
    // TODO: Charger les préférences depuis le backend
    // Pour l'instant, on utilise des valeurs par défaut
    setState(() {
      _selectedDisciplines.addAll(['Mathématiques', 'Physique']);
      _selectedLevels.addAll(['Lycée', 'Supérieur']);
      _selectedFormats.addAll(['Cours particulier', 'Cours en ligne']);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Préférences'),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1E3A8A),
        elevation: 0,
        actions: [
          TextButton(
            onPressed: _savePreferences,
            child: const Text(
              'Sauvegarder',
              style: TextStyle(
                color: Color(0xFF3B82F6),
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // En-tête
            const Card(
              child: Padding(
                padding: EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(
                      Icons.settings,
                      size: 32,
                      color: Color(0xFF3B82F6),
                    ),
                    SizedBox(height: 12),
                    Text(
                      'Personnalisez votre expérience',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1E3A8A),
                      ),
                    ),
                    SizedBox(height: 8),
                    Text(
                      'Sélectionnez vos préférences pour recevoir des recommandations personnalisées et filtrer automatiquement les enseignants et leçons.',
                      style: TextStyle(
                        fontSize: 14,
                        color: Color(0xFF6B7280),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Disciplines préférées
            _buildSection(
              title: 'Disciplines préférées',
              subtitle: 'Sélectionnez les matières qui vous intéressent',
              icon: Icons.school,
              items: _availableDisciplines,
              selectedItems: _selectedDisciplines,
              onItemToggle: (item) {
                setState(() {
                  if (_selectedDisciplines.contains(item)) {
                    _selectedDisciplines.remove(item);
                  } else {
                    _selectedDisciplines.add(item);
                  }
                });
              },
            ),
            const SizedBox(height: 24),

            // Niveaux préférés
            _buildSection(
              title: 'Niveaux préférés',
              subtitle: 'Choisissez vos niveaux d\'étude',
              icon: Icons.grade,
              items: _availableLevels,
              selectedItems: _selectedLevels,
              onItemToggle: (item) {
                setState(() {
                  if (_selectedLevels.contains(item)) {
                    _selectedLevels.remove(item);
                  } else {
                    _selectedLevels.add(item);
                  }
                });
              },
            ),
            const SizedBox(height: 24),

            // Formats préférés
            _buildSection(
              title: 'Formats préférés',
              subtitle: 'Sélectionnez vos formats de cours préférés',
              icon: Icons.format_list_bulleted,
              items: _availableFormats,
              selectedItems: _selectedFormats,
              onItemToggle: (item) {
                setState(() {
                  if (_selectedFormats.contains(item)) {
                    _selectedFormats.remove(item);
                  } else {
                    _selectedFormats.add(item);
                  }
                });
              },
            ),
            const SizedBox(height: 32),

            // Boutons d'action
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _resetPreferences,
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: Color(0xFF6B7280)),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 16),
                    ),
                    child: const Text(
                      'Réinitialiser',
                      style: TextStyle(color: Color(0xFF6B7280)),
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: ElevatedButton(
                    onPressed: _savePreferences,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF3B82F6),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 16),
                    ),
                    child: const Text(
                      'Sauvegarder',
                      style: TextStyle(color: Colors.white),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Résumé des préférences
            if (_selectedDisciplines.isNotEmpty || _selectedLevels.isNotEmpty || _selectedFormats.isNotEmpty)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Résumé de vos préférences',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF1E3A8A),
                        ),
                      ),
                      const SizedBox(height: 12),
                      if (_selectedDisciplines.isNotEmpty) ...[
                        _buildPreferenceSummary('Disciplines', _selectedDisciplines),
                        const SizedBox(height: 8),
                      ],
                      if (_selectedLevels.isNotEmpty) ...[
                        _buildPreferenceSummary('Niveaux', _selectedLevels),
                        const SizedBox(height: 8),
                      ],
                      if (_selectedFormats.isNotEmpty) ...[
                        _buildPreferenceSummary('Formats', _selectedFormats),
                      ],
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildSection({
    required String title,
    required String subtitle,
    required IconData icon,
    required List<String> items,
    required Set<String> selectedItems,
    required Function(String) onItemToggle,
  }) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: const Color(0xFF3B82F6)),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF1E3A8A),
                        ),
                      ),
                      Text(
                        subtitle,
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
            const SizedBox(height: 16),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: items.map((item) {
                final isSelected = selectedItems.contains(item);
                return FilterChip(
                  label: Text(item),
                  selected: isSelected,
                  onSelected: (selected) => onItemToggle(item),
                  selectedColor: const Color(0xFF3B82F6),
                  checkmarkColor: Colors.white,
                  labelStyle: TextStyle(
                    color: isSelected ? Colors.white : const Color(0xFF6B7280),
                    fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                  ),
                  backgroundColor: const Color(0xFFF3F4F6),
                  side: BorderSide(
                    color: isSelected ? const Color(0xFF3B82F6) : const Color(0xFFE5E7EB),
                  ),
                );
              }).toList(),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPreferenceSummary(String title, Set<String> items) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          '$title : ',
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: Color(0xFF6B7280),
          ),
        ),
        Expanded(
          child: Text(
            items.join(', '),
            style: const TextStyle(
              fontSize: 14,
              color: Color(0xFF1E3A8A),
            ),
          ),
        ),
      ],
    );
  }

  void _savePreferences() async {
    try {
      // TODO: Sauvegarder les préférences via le provider
      await ref.read(studentProvider.notifier).savePreferences(
        disciplines: _selectedDisciplines.toList(),
        levels: _selectedLevels.toList(),
        formats: _selectedFormats.toList(),
      );

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Préférences sauvegardées avec succès !'),
            backgroundColor: Color(0xFF10B981),
          ),
        );
        Navigator.of(context).pop();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erreur lors de la sauvegarde: ${e.toString()}'),
            backgroundColor: const Color(0xFFEF4444),
          ),
        );
      }
    }
  }

  void _resetPreferences() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Réinitialiser les préférences'),
        content: const Text(
          'Êtes-vous sûr de vouloir réinitialiser toutes vos préférences ? Cette action ne peut pas être annulée.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.of(context).pop();
              setState(() {
                _selectedDisciplines.clear();
                _selectedLevels.clear();
                _selectedFormats.clear();
              });
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Préférences réinitialisées'),
                  backgroundColor: Color(0xFF10B981),
                ),
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFEF4444),
            ),
            child: const Text(
              'Réinitialiser',
              style: TextStyle(color: Colors.white),
            ),
          ),
        ],
      ),
    );
  }
}
