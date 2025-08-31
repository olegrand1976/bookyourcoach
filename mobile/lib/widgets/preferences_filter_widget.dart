import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/student_provider.dart';

class PreferencesFilterWidget extends ConsumerStatefulWidget {
  final Function(Map<String, dynamic>) onFilterChanged;
  final Map<String, dynamic> currentFilters;

  const PreferencesFilterWidget({
    super.key,
    required this.onFilterChanged,
    required this.currentFilters,
  });

  @override
  ConsumerState<PreferencesFilterWidget> createState() => _PreferencesFilterWidgetState();
}

class _PreferencesFilterWidgetState extends ConsumerState<PreferencesFilterWidget> {
  final Set<String> _selectedDisciplines = {};
  final Set<String> _selectedLevels = {};
  final Set<String> _selectedFormats = {};
  String? _selectedLocation;
  double? _maxPrice;

  @override
  void initState() {
    super.initState();
    _loadCurrentFilters();
    _loadPreferences();
  }

  void _loadCurrentFilters() {
    setState(() {
      _selectedDisciplines.addAll(
        List<String>.from(widget.currentFilters['disciplines'] ?? []),
      );
      _selectedLevels.addAll(
        List<String>.from(widget.currentFilters['levels'] ?? []),
      );
      _selectedFormats.addAll(
        List<String>.from(widget.currentFilters['formats'] ?? []),
      );
      _selectedLocation = widget.currentFilters['location'];
      _maxPrice = widget.currentFilters['maxPrice'];
    });
  }

  void _loadPreferences() {
    final preferences = ref.read(studentProvider).preferences.preferences;
    if (preferences != null) {
      setState(() {
        // Appliquer les préférences par défaut si aucun filtre n'est sélectionné
        if (_selectedDisciplines.isEmpty) {
          _selectedDisciplines.addAll(preferences.preferredDisciplines);
        }
        if (_selectedLevels.isEmpty) {
          _selectedLevels.addAll(preferences.preferredLevels);
        }
        if (_selectedFormats.isEmpty) {
          _selectedFormats.addAll(preferences.preferredFormats);
        }
        if (_selectedLocation == null) {
          _selectedLocation = preferences.location;
        }
        if (_maxPrice == null) {
          _maxPrice = preferences.maxPrice;
        }
      });
      _applyFilters();
    }
  }

  void _applyFilters() {
    final filters = {
      'disciplines': _selectedDisciplines.toList(),
      'levels': _selectedLevels.toList(),
      'formats': _selectedFormats.toList(),
      'location': _selectedLocation,
      'maxPrice': _maxPrice,
    };
    widget.onFilterChanged(filters);
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.all(16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(
                  Icons.filter_list,
                  color: Color(0xFF3B82F6),
                ),
                const SizedBox(width: 8),
                const Text(
                  'Filtres',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E3A8A),
                  ),
                ),
                const Spacer(),
                TextButton(
                  onPressed: _resetFilters,
                  child: const Text(
                    'Réinitialiser',
                    style: TextStyle(color: Color(0xFF6B7280)),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Disciplines
            _buildFilterSection(
              title: 'Disciplines',
              items: [
                'Mathématiques', 'Physique', 'Chimie', 'Biologie',
                'Histoire', 'Géographie', 'Français', 'Anglais',
                'Espagnol', 'Allemand', 'Philosophie', 'Économie',
                'Informatique', 'Musique', 'Arts plastiques', 'Sport',
              ],
              selectedItems: _selectedDisciplines,
              onItemToggle: (item) {
                setState(() {
                  if (_selectedDisciplines.contains(item)) {
                    _selectedDisciplines.remove(item);
                  } else {
                    _selectedDisciplines.add(item);
                  }
                });
                _applyFilters();
              },
            ),
            const SizedBox(height: 16),

            // Niveaux
            _buildFilterSection(
              title: 'Niveaux',
              items: ['Primaire', 'Collège', 'Lycée', 'Supérieur', 'Adulte'],
              selectedItems: _selectedLevels,
              onItemToggle: (item) {
                setState(() {
                  if (_selectedLevels.contains(item)) {
                    _selectedLevels.remove(item);
                  } else {
                    _selectedLevels.add(item);
                  }
                });
                _applyFilters();
              },
            ),
            const SizedBox(height: 16),

            // Formats
            _buildFilterSection(
              title: 'Formats',
              items: [
                'Cours particulier', 'Cours en groupe', 'Cours en ligne',
                'Cours en présentiel', 'Stage intensif',
              ],
              selectedItems: _selectedFormats,
              onItemToggle: (item) {
                setState(() {
                  if (_selectedFormats.contains(item)) {
                    _selectedFormats.remove(item);
                  } else {
                    _selectedFormats.add(item);
                  }
                });
                _applyFilters();
              },
            ),
            const SizedBox(height: 16),

            // Prix maximum
            Row(
              children: [
                const Icon(
                  Icons.euro,
                  color: Color(0xFF6B7280),
                  size: 20,
                ),
                const SizedBox(width: 8),
                const Text(
                  'Prix maximum:',
                  style: TextStyle(
                    fontSize: 14,
                    color: Color(0xFF6B7280),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Slider(
                    value: _maxPrice?.toDouble() ?? 0,
                    min: 0,
                    max: 200,
                    divisions: 20,
                    label: '${_maxPrice?.toInt() ?? 0}€',
                    onChanged: (value) {
                      setState(() {
                        _maxPrice = value;
                      });
                      _applyFilters();
                    },
                  ),
                ),
                Text(
                  '${_maxPrice?.toInt() ?? 0}€',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E3A8A),
                  ),
                ),
              ],
            ),

            // Bouton pour appliquer les préférences
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _applyPreferences,
                icon: const Icon(Icons.auto_awesome),
                label: const Text('Appliquer mes préférences'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF10B981),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterSection({
    required String title,
    required List<String> items,
    required Set<String> selectedItems,
    required Function(String) onItemToggle,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: Color(0xFF1E3A8A),
          ),
        ),
        const SizedBox(height: 8),
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
    );
  }

  void _resetFilters() {
    setState(() {
      _selectedDisciplines.clear();
      _selectedLevels.clear();
      _selectedFormats.clear();
      _selectedLocation = null;
      _maxPrice = null;
    });
    _applyFilters();
  }

  void _applyPreferences() {
    final preferences = ref.read(studentProvider).preferences.preferences;
    if (preferences != null) {
      setState(() {
        _selectedDisciplines.clear();
        _selectedDisciplines.addAll(preferences.preferredDisciplines);
        _selectedLevels.clear();
        _selectedLevels.addAll(preferences.preferredLevels);
        _selectedFormats.clear();
        _selectedFormats.addAll(preferences.preferredFormats);
        _selectedLocation = preferences.location;
        _maxPrice = preferences.maxPrice;
      });
      _applyFilters();
      
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Préférences appliquées !'),
          backgroundColor: Color(0xFF10B981),
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Aucune préférence configurée'),
          backgroundColor: Color(0xFFF59E0B),
        ),
      );
    }
  }
}
