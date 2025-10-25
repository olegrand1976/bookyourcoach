import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/student_provider.dart';
import '../models/lesson.dart';

class StudentLessonsScreen extends ConsumerStatefulWidget {
  const StudentLessonsScreen({super.key});

  @override
  ConsumerState<StudentLessonsScreen> createState() => _StudentLessonsScreenState();
}

class _StudentLessonsScreenState extends ConsumerState<StudentLessonsScreen> {
  String _selectedFilter = 'all';
  String? _selectedDateFilter;
  String? _selectedPriceFilter;
  String? _selectedLocationFilter;
  @override
  void initState() {
    super.initState();
    // Charger les leçons disponibles au démarrage
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(studentProvider.notifier).loadAvailableLessons();
    });
  }

  @override
  Widget build(BuildContext context) {
    final studentState = ref.watch(studentProvider);
    final lessons = studentState.availableLessons.lessons;
    final isLoading = studentState.availableLessons.isLoading;
    final error = studentState.availableLessons.error;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Cours Disponibles'),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1E3A8A),
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: () => _showFilterDialog(),
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () {
              ref.read(studentProvider.notifier).loadAvailableLessons();
            },
          ),
        ],
      ),
      drawer: _buildDrawer(),
      body: _buildBody(lessons, isLoading, error),
    );
  }

  Widget _buildDrawer() {
    return Drawer(
      child: Column(
        children: [
          // En-tête du drawer
          DrawerHeader(
            decoration: const BoxDecoration(
              color: Color(0xFF1E3A8A),
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
                  'Filtrer les cours disponibles',
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.8),
                    fontSize: 14,
                  ),
                ),
              ],
            ),
          ),
          // Filtres
          Expanded(
            child: ListView(
              padding: EdgeInsets.zero,
              children: [
                // Filtres par date
                _buildFilterSection(
                  'Date',
                  [
                    {'label': 'Toutes les dates', 'value': 'all'},
                    {'label': 'Aujourd\'hui', 'value': 'today'},
                    {'label': 'Cette semaine', 'value': 'week'},
                    {'label': 'Ce mois', 'value': 'month'},
                  ],
                  _selectedDateFilter ?? 'all',
                  (value) {
                    setState(() {
                      _selectedDateFilter = value == 'all' ? null : value;
                    });
                    _applyFilters();
                  },
                ),
                // Filtres par prix
                _buildFilterSection(
                  'Prix',
                  [
                    {'label': 'Tous les prix', 'value': 'all'},
                    {'label': 'Moins de 30€', 'value': 'low'},
                    {'label': '30€ - 50€', 'value': 'medium'},
                    {'label': 'Plus de 50€', 'value': 'high'},
                  ],
                  _selectedPriceFilter ?? 'all',
                  (value) {
                    setState(() {
                      _selectedPriceFilter = value == 'all' ? null : value;
                    });
                    _applyFilters();
                  },
                ),
                // Filtres par localisation
                _buildFilterSection(
                  'Localisation',
                  [
                    {'label': 'Toutes les localisations', 'value': 'all'},
                    {'label': 'Proche de moi', 'value': 'near'},
                    {'label': 'Centre-ville', 'value': 'center'},
                    {'label': 'Périphérie', 'value': 'suburb'},
                  ],
                  _selectedLocationFilter ?? 'all',
                  (value) {
                    setState(() {
                      _selectedLocationFilter = value == 'all' ? null : value;
                    });
                    _applyFilters();
                  },
                ),
              ],
            ),
          ),
          // Bouton de réinitialisation
          Container(
            padding: const EdgeInsets.all(16),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _resetFilters,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF1E3A8A),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
                child: const Text('Réinitialiser les filtres'),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterSection(String title, List<Map<String, String>> options, String selectedValue, Function(String) onChanged) {
    return ExpansionTile(
      title: Text(
        title,
        style: const TextStyle(
          fontWeight: FontWeight.w600,
          color: Color(0xFF1E3A8A),
        ),
      ),
      children: options.map((option) {
        return RadioListTile<String>(
          title: Text(option['label']!),
          value: option['value']!,
          groupValue: selectedValue,
          onChanged: (value) => onChanged(value!),
          activeColor: const Color(0xFF1E3A8A),
        );
      }).toList(),
    );
  }

  void _applyFilters() {
    // Ici vous pouvez implémenter la logique de filtrage
    // Pour l'instant, on recharge simplement les données
    ref.read(studentProvider.notifier).loadAvailableLessons();
  }

  void _resetFilters() {
    setState(() {
      _selectedDateFilter = null;
      _selectedPriceFilter = null;
      _selectedLocationFilter = null;
    });
    _applyFilters();
  }

  void _showFilterDialog() {
    Scaffold.of(context).openDrawer();
  }

  Widget _buildBody(List<Lesson> lessons, bool isLoading, String? error) {
    if (isLoading) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircularProgressIndicator(),
            SizedBox(height: 16),
            Text(
              'Chargement des cours...',
              style: TextStyle(
                fontSize: 16,
                color: Color(0xFF6B7280),
              ),
            ),
          ],
        ),
      );
    }

    if (error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(
              Icons.error_outline,
              size: 64,
              color: Color(0xFFEF4444),
            ),
            const SizedBox(height: 16),
            Text(
              'Erreur de chargement',
              style: const TextStyle(
                fontSize: 18,
                color: Color(0xFF1E3A8A),
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              error,
              style: const TextStyle(
                fontSize: 14,
                color: Color(0xFF6B7280),
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () {
                ref.read(studentProvider.notifier).loadAvailableLessons();
              },
              child: const Text('Réessayer'),
            ),
          ],
        ),
      );
    }

    if (lessons.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.school_outlined,
              size: 64,
              color: Color(0xFF6B7280),
            ),
            SizedBox(height: 16),
            Text(
              'Aucune leçon disponible',
              style: TextStyle(
                fontSize: 18,
                color: Color(0xFF6B7280),
              ),
            ),
            SizedBox(height: 8),
            Text(
              'Les enseignants ajouteront bientôt de nouvelles leçons',
              style: TextStyle(
                fontSize: 14,
                color: Color(0xFF9CA3AF),
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () async {
        ref.read(studentProvider.notifier).loadAvailableLessons();
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
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    lesson.title,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1E3A8A),
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFF10B981),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Text(
                    'Disponible',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              lesson.description,
              style: const TextStyle(
                fontSize: 14,
                color: Color(0xFF6B7280),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(
                  Icons.person,
                  size: 16,
                  color: Colors.grey[600],
                ),
                const SizedBox(width: 4),
                Text(
                  lesson.teacher?.displayName ?? 'Enseignant',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[600],
                  ),
                ),
                const Spacer(),
                Icon(
                  Icons.euro,
                  size: 16,
                  color: Colors.grey[600],
                ),
                const SizedBox(width: 4),
                Text(
                  '${lesson.price}€',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF10B981),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(
                  Icons.access_time,
                  size: 16,
                  color: Colors.grey[600],
                ),
                const SizedBox(width: 4),
                Text(
                  '${lesson.duration} minutes',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[600],
                  ),
                ),
                const Spacer(),
                Icon(
                  Icons.school,
                  size: 16,
                  color: Colors.grey[600],
                ),
                const SizedBox(width: 4),
                                 Text(
                   lesson.title.split(' ').first, // Utiliser le premier mot du titre comme sujet
                   style: TextStyle(
                     fontSize: 14,
                     color: Colors.grey[600],
                   ),
                 ),
              ],
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => _bookLesson(lesson),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF3B82F6),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
                child: const Text(
                  'Réserver ce cours',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _bookLesson(Lesson lesson) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Réserver ce cours'),
        content: Text(
          'Êtes-vous sûr de vouloir réserver le cours "${lesson.title}" ?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.of(context).pop();
              try {
                await ref.read(studentProvider.notifier).bookLesson(lesson.id);
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Cours réservé avec succès !'),
                      backgroundColor: Color(0xFF10B981),
                    ),
                  );
                }
              } catch (e) {
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Erreur lors de la réservation: ${e.toString()}'),
                      backgroundColor: const Color(0xFFEF4444),
                    ),
                  );
                }
              }
            },
            child: const Text('Confirmer'),
          ),
        ],
      ),
    );
  }
}
