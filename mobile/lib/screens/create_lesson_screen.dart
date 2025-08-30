import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/api_client.dart';
import '../services/lesson_service.dart';
import '../state/app_state.dart';

class CreateLessonScreen extends StatefulWidget {
  const CreateLessonScreen({super.key});

  @override
  State<CreateLessonScreen> createState() => _CreateLessonScreenState();
}

class _CreateLessonScreenState extends State<CreateLessonScreen> {
  DateTime? _selectedDate;
  TimeOfDay? _selectedTime;
  final TextEditingController _locationController = TextEditingController();
  final List<String> _disciplines = const [
    'Saut d\'obstacles', 'Dressage', 'Cross', 'Poney', 'Voltige', 'Balade'
  ];
  String? _selectedDiscipline;
  int _capacity = 1;
  int _duration = 60;
  bool _submitting = false;

  @override
  void dispose() {
    _locationController.dispose();
    super.dispose();
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate ?? now,
      firstDate: now,
      lastDate: now.add(const Duration(days: 365)),
    );
    if (picked != null) {
      setState(() { _selectedDate = picked; });
    }
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _selectedTime ?? TimeOfDay.fromDateTime(DateTime.now().add(const Duration(hours: 1))),
    );
    if (picked != null) {
      setState(() { _selectedTime = picked; });
    }
  }

  DateTime? _combineDateTime() {
    if (_selectedDate == null || _selectedTime == null) return null;
    return DateTime(
      _selectedDate!.year,
      _selectedDate!.month,
      _selectedDate!.day,
      _selectedTime!.hour,
      _selectedTime!.minute,
    );
  }

  Future<void> _submit() async {
    final app = context.read<AppState>();
    final start = _combineDateTime();
    if (_selectedDiscipline == null || start == null || _locationController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez compléter tous les champs.')),
      );
      return;
    }
    setState(() { _submitting = true; });
    try {
      final client = await ApiFactory.authed();
      final service = LessonService(client);
      await service.createLesson(app.me!.id, {
        'start': start.toIso8601String(),
        'duration_minutes': _duration,
        'discipline': _selectedDiscipline,
        'location': _locationController.text.trim(),
        'capacity': _capacity,
      });
      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur lors de la création: $e')),
      );
    } finally {
      if (mounted) setState(() { _submitting = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Nouvelle Leçon')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Date & Heure', style: TextStyle(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: _pickDate,
                              icon: const Icon(Icons.event),
                              label: Text(
                                _selectedDate == null
                                  ? 'Choisir la date'
                                  : '${_selectedDate!.day}/${_selectedDate!.month}/${_selectedDate!.year}',
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: _pickTime,
                              icon: const Icon(Icons.schedule),
                              label: Text(
                                _selectedTime == null
                                  ? 'Choisir l\'heure'
                                  : _selectedTime!.format(context),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Discipline', style: TextStyle(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          for (final d in _disciplines)
                            ChoiceChip(
                              label: Text(d),
                              selected: _selectedDiscipline == d,
                              onSelected: (_) {
                                setState(() { _selectedDiscipline = d; });
                              },
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Lieu', style: TextStyle(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      TextField(
                        controller: _locationController,
                        decoration: const InputDecoration(
                          prefixIcon: Icon(Icons.place_outlined),
                          hintText: 'Centre équestre, adresse…',
                          border: OutlineInputBorder(),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Capacité', style: TextStyle(fontWeight: FontWeight.bold)),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                IconButton(
                                  onPressed: _capacity > 1 ? () => setState(() { _capacity--; }) : null,
                                  icon: const Icon(Icons.remove_circle_outline),
                                ),
                                Text('$_capacity'),
                                IconButton(
                                  onPressed: () => setState(() { _capacity++; }),
                                  icon: const Icon(Icons.add_circle_outline),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  Expanded(
                    child: Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Durée (min)', style: TextStyle(fontWeight: FontWeight.bold)),
                            const SizedBox(height: 8),
                            DropdownButton<int>(
                              value: _duration,
                              isExpanded: true,
                              items: const [30, 45, 60, 90, 120]
                                  .map((v) => DropdownMenuItem(value: v, child: Text('$v')))
                                  .toList(),
                              onChanged: (v) => setState(() { if (v != null) _duration = v; }),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 80),
            ],
          ),
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
          child: SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: _submitting ? null : _submit,
              icon: _submitting
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.check_circle_outline),
              label: const Text('Publier la leçon'),
            ),
          ),
        ),
      ),
    );
  }
}

