import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/teacher_provider.dart';

class TeacherAvailabilitiesScreen extends ConsumerWidget {
  const TeacherAvailabilitiesScreen({super.key});

  Widget _buildBody(WidgetRef ref) {
    final availabilitiesState = ref.watch(teacherProvider).availabilities;
    final availabilities = availabilitiesState.availabilities;
    
    if (availabilitiesState.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }
    
    if (availabilities.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.schedule_outlined,
              size: 64,
              color: Color(0xFF6B7280),
            ),
            SizedBox(height: 16),
            Text(
              'Aucune disponibilité',
              style: TextStyle(
                fontSize: 18,
                color: Color(0xFF6B7280),
              ),
            ),
            SizedBox(height: 8),
            Text(
              'Ajoutez vos disponibilités pour commencer',
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
        ref.read(teacherProvider.notifier).loadAvailabilities();
      },
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: availabilities.length,
        itemBuilder: (context, index) {
          final availability = availabilities[index];
          return _buildAvailabilityCard(availability);
        },
      ),
    );
  }

  Widget _buildAvailabilityCard(dynamic availability) {
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
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    'Disponibilité',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1E3A8A),
                    ),
                  ),
                ),
                PopupMenuButton<String>(
                  onSelected: (value) {
                    if (value == 'edit') {
                      _editAvailability(availability);
                    } else if (value == 'delete') {
                      _deleteAvailability(availability);
                    }
                  },
                  itemBuilder: (context) => [
                    const PopupMenuItem(
                      value: 'edit',
                      child: Row(
                        children: [
                          Icon(Icons.edit, size: 20),
                          SizedBox(width: 8),
                          Text('Modifier'),
                        ],
                      ),
                    ),
                    const PopupMenuItem(
                      value: 'delete',
                      child: Row(
                        children: [
                          Icon(Icons.delete, size: 20, color: Colors.red),
                          SizedBox(width: 8),
                          Text('Supprimer', style: TextStyle(color: Colors.red)),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(Icons.access_time, size: 16, color: Colors.grey[600]),
                const SizedBox(width: 4),
                Text(
                  '${availability.startTime} - ${availability.endTime}',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
            if (availability.notes != null && availability.notes!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                availability.notes!,
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey[600],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _editAvailability(dynamic availability) {
    // TODO: Implémenter la modification
  }

  void _deleteAvailability(dynamic availability) {
    // TODO: Implémenter la suppression
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Disponibilités'),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1E3A8A),
        elevation: 0,
        actions: [
          IconButton(
            onPressed: () {
              _addAvailability();
            },
            icon: const Icon(Icons.add),
          ),
        ],
      ),
      body: _buildBody(ref),
    );
  }

  void _addAvailability() {
    // TODO: Implémenter l'ajout
  }
}
