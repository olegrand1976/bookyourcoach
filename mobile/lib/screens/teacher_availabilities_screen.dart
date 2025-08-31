import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/availability.dart';
import '../providers/teacher_provider.dart';

class TeacherAvailabilitiesScreen extends ConsumerStatefulWidget {
  const TeacherAvailabilitiesScreen({super.key});

  @override
  ConsumerState<TeacherAvailabilitiesScreen> createState() => _TeacherAvailabilitiesScreenState();
}

class _TeacherAvailabilitiesScreenState extends ConsumerState<TeacherAvailabilitiesScreen> {
  @override
  void initState() {
    super.initState();
    // Charger les disponibilités
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(teacherProvider.notifier).loadAvailabilities();
    });
  }

  @override
  Widget build(BuildContext context) {
    final availabilitiesState = ref.watch(teacherProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Disponibilités'),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1E3A8A),
        elevation: 0,
        actions: [
          IconButton(
            onPressed: () {
              _showAddAvailabilityDialog();
            },
            icon: const Icon(Icons.add),
          ),
        ],
      ),
      body: availabilitiesState.when(
        data: (data) {
          final availabilities = data.availabilities;
          if (availabilities.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(
                    Icons.schedule,
                    size: 64,
                    color: Color(0xFF6B7280),
                  ),
                  const SizedBox(height: 16),
                  const Text(
                    'Aucune disponibilité',
                    style: TextStyle(
                      fontSize: 18,
                      color: Color(0xFF6B7280),
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Ajoutez vos créneaux disponibles pour recevoir des réservations',
                    style: TextStyle(
                      fontSize: 14,
                      color: Color(0xFF9CA3AF),
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton(
                    onPressed: () {
                      _showAddAvailabilityDialog();
                    },
                    child: const Text('Ajouter une disponibilité'),
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
                                'Disponibilité #${availability.id}',
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
                                color: availability.isAvailable 
                                    ? const Color(0xFF10B981)
                                    : const Color(0xFF6B7280),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                availability.isAvailable ? 'Disponible' : 'Indisponible',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            const Icon(
                              Icons.calendar_today,
                              size: 16,
                              color: Color(0xFF6B7280),
                            ),
                            const SizedBox(width: 4),
                            Text(
                              '${availability.startTime.day.toString().padLeft(2, '0')}/${availability.startTime.month.toString().padLeft(2, '0')}/${availability.startTime.year}',
                              style: const TextStyle(
                                fontSize: 14,
                                color: Color(0xFF6B7280),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            const Icon(
                              Icons.access_time,
                              size: 16,
                              color: Color(0xFF6B7280),
                            ),
                            const SizedBox(width: 4),
                            Text(
                              '${availability.startTime.hour.toString().padLeft(2, '0')}:${availability.startTime.minute.toString().padLeft(2, '0')} - ${availability.endTime.hour.toString().padLeft(2, '0')}:${availability.endTime.minute.toString().padLeft(2, '0')}',
                              style: const TextStyle(
                                fontSize: 14,
                                color: Color(0xFF6B7280),
                              ),
                            ),
                          ],
                        ),
                        if (availability.notes != null && availability.notes!.isNotEmpty) ...[
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              const Icon(
                                Icons.note,
                                size: 16,
                                color: Color(0xFF6B7280),
                              ),
                              const SizedBox(width: 4),
                              Expanded(
                                child: Text(
                                  availability.notes!,
                                  style: const TextStyle(
                                    fontSize: 14,
                                    color: Color(0xFF6B7280),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
                              child: OutlinedButton(
                                onPressed: () {
                                  _showEditAvailabilityDialog(availability);
                                },
                                style: OutlinedButton.styleFrom(
                                  side: const BorderSide(color: Color(0xFF3B82F6)),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                ),
                                child: const Text(
                                  'Modifier',
                                  style: TextStyle(color: Color(0xFF3B82F6)),
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: ElevatedButton(
                                onPressed: () {
                                  _deleteAvailability(availability);
                                },
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFFEF4444),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                ),
                                child: const Text(
                                  'Supprimer',
                                  style: TextStyle(color: Colors.white),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          );
        },
        loading: () => const Center(
          child: CircularProgressIndicator(),
        ),
        error: (error, stack) => Center(
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
                'Erreur lors du chargement',
                style: const TextStyle(
                  fontSize: 18,
                  color: Color(0xFFEF4444),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                error.toString(),
                style: const TextStyle(
                  fontSize: 14,
                  color: Color(0xFF6B7280),
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () {
                  ref.read(teacherProvider.notifier).loadAvailabilities();
                },
                child: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          _showAddAvailabilityDialog();
        },
        backgroundColor: const Color(0xFF3B82F6),
        child: const Icon(Icons.add, color: Colors.white),
      ),
    );
  }

  void _showAddAvailabilityDialog() {
    DateTime selectedDate = DateTime.now();
    TimeOfDay startTime = const TimeOfDay(hour: 9, minute: 0);
    TimeOfDay endTime = const TimeOfDay(hour: 10, minute: 0);
    final notesController = TextEditingController();

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Ajouter une disponibilité'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              title: const Text('Date'),
              subtitle: Text(
                '${selectedDate.day.toString().padLeft(2, '0')}/${selectedDate.month.toString().padLeft(2, '0')}/${selectedDate.year}',
              ),
              trailing: const Icon(Icons.calendar_today),
              onTap: () async {
                final date = await showDatePicker(
                  context: context,
                  initialDate: selectedDate,
                  firstDate: DateTime.now(),
                  lastDate: DateTime.now().add(const Duration(days: 365)),
                );
                if (date != null) {
                  selectedDate = date;
                }
              },
            ),
            ListTile(
              title: const Text('Heure de début'),
              subtitle: Text(startTime.format(context)),
              trailing: const Icon(Icons.access_time),
              onTap: () async {
                final time = await showTimePicker(
                  context: context,
                  initialTime: startTime,
                );
                if (time != null) {
                  startTime = time;
                }
              },
            ),
            ListTile(
              title: const Text('Heure de fin'),
              subtitle: Text(endTime.format(context)),
              trailing: const Icon(Icons.access_time),
              onTap: () async {
                final time = await showTimePicker(
                  context: context,
                  initialTime: endTime,
                );
                if (time != null) {
                  endTime = time;
                }
              },
            ),
            TextField(
              controller: notesController,
              decoration: const InputDecoration(
                labelText: 'Notes (optionnel)',
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () async {
              final startDateTime = DateTime(
                selectedDate.year,
                selectedDate.month,
                selectedDate.day,
                startTime.hour,
                startTime.minute,
              );
              final endDateTime = DateTime(
                selectedDate.year,
                selectedDate.month,
                selectedDate.day,
                endTime.hour,
                endTime.minute,
              );

              try {
                await ref.read(teacherProvider.notifier).addAvailability(
                  startDateTime,
                  endDateTime,
                  notesController.text.isEmpty ? null : notesController.text,
                );
                if (mounted) {
                  Navigator.of(context).pop();
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Disponibilité ajoutée avec succès !'),
                      backgroundColor: Color(0xFF10B981),
                    ),
                  );
                }
              } catch (e) {
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Erreur: ${e.toString()}'),
                      backgroundColor: const Color(0xFFEF4444),
                    ),
                  );
                }
              }
            },
            child: const Text('Ajouter'),
          ),
        ],
      ),
    );
  }

  void _showEditAvailabilityDialog(Availability availability) {
    DateTime selectedDate = availability.startTime;
    TimeOfDay startTime = TimeOfDay.fromDateTime(availability.startTime);
    TimeOfDay endTime = TimeOfDay.fromDateTime(availability.endTime);
    final notesController = TextEditingController(text: availability.notes);

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Modifier la disponibilité'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              title: const Text('Date'),
              subtitle: Text(
                '${selectedDate.day.toString().padLeft(2, '0')}/${selectedDate.month.toString().padLeft(2, '0')}/${selectedDate.year}',
              ),
              trailing: const Icon(Icons.calendar_today),
              onTap: () async {
                final date = await showDatePicker(
                  context: context,
                  initialDate: selectedDate,
                  firstDate: DateTime.now(),
                  lastDate: DateTime.now().add(const Duration(days: 365)),
                );
                if (date != null) {
                  selectedDate = date;
                }
              },
            ),
            ListTile(
              title: const Text('Heure de début'),
              subtitle: Text(startTime.format(context)),
              trailing: const Icon(Icons.access_time),
              onTap: () async {
                final time = await showTimePicker(
                  context: context,
                  initialTime: startTime,
                );
                if (time != null) {
                  startTime = time;
                }
              },
            ),
            ListTile(
              title: const Text('Heure de fin'),
              subtitle: Text(endTime.format(context)),
              trailing: const Icon(Icons.access_time),
              onTap: () async {
                final time = await showTimePicker(
                  context: context,
                  initialTime: endTime,
                );
                if (time != null) {
                  endTime = time;
                }
              },
            ),
            TextField(
              controller: notesController,
              decoration: const InputDecoration(
                labelText: 'Notes (optionnel)',
                border: OutlineInputBorder(),
              ),
              maxLines: 3,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () async {
              final startDateTime = DateTime(
                selectedDate.year,
                selectedDate.month,
                selectedDate.day,
                startTime.hour,
                startTime.minute,
              );
              final endDateTime = DateTime(
                selectedDate.year,
                selectedDate.month,
                selectedDate.day,
                endTime.hour,
                endTime.minute,
              );

              try {
                await ref.read(teacherProvider.notifier).updateAvailability(
                  availability.id,
                  startDateTime,
                  endDateTime,
                  notesController.text.isEmpty ? null : notesController.text,
                );
                if (mounted) {
                  Navigator.of(context).pop();
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Disponibilité modifiée avec succès !'),
                      backgroundColor: Color(0xFF10B981),
                    ),
                  );
                }
              } catch (e) {
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Erreur: ${e.toString()}'),
                      backgroundColor: const Color(0xFFEF4444),
                    ),
                  );
                }
              }
            },
            child: const Text('Modifier'),
          ),
        ],
      ),
    );
  }

  void _deleteAvailability(Availability availability) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Supprimer la disponibilité'),
        content: Text(
          'Êtes-vous sûr de vouloir supprimer cette disponibilité ?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () async {
              try {
                await ref.read(teacherProvider.notifier).deleteAvailability(availability.id);
                if (mounted) {
                  Navigator.of(context).pop();
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Disponibilité supprimée avec succès !'),
                      backgroundColor: Color(0xFF10B981),
                    ),
                  );
                }
              } catch (e) {
                if (mounted) {
                  Navigator.of(context).pop();
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('Erreur: ${e.toString()}'),
                      backgroundColor: const Color(0xFFEF4444),
                    ),
                  );
                }
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFEF4444),
            ),
            child: const Text(
              'Supprimer',
              style: TextStyle(color: Colors.white),
            ),
          ),
        ],
      ),
    );
  }
}
