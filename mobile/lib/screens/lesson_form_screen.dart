import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/lesson.dart';
import '../providers/teacher_provider.dart';

class LessonFormScreen extends ConsumerStatefulWidget {
  final Lesson? lesson; // null pour créer, non-null pour modifier

  const LessonFormScreen({super.key, this.lesson});

  @override
  ConsumerState<LessonFormScreen> createState() => _LessonFormScreenState();
}

class _LessonFormScreenState extends ConsumerState<LessonFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _locationController = TextEditingController();
  final _priceController = TextEditingController();
  final _notesController = TextEditingController();

  DateTime _selectedDate = DateTime.now();
  TimeOfDay _startTime = const TimeOfDay(hour: 9, minute: 0);
  TimeOfDay _endTime = const TimeOfDay(hour: 10, minute: 0);

  @override
  void initState() {
    super.initState();
    if (widget.lesson != null) {
      // Mode édition
      final lesson = widget.lesson!;
      _titleController.text = lesson.title;
      _descriptionController.text = lesson.description;
      _locationController.text = lesson.location ?? '';
      _priceController.text = lesson.price?.toString() ?? '';
      _notesController.text = lesson.notes ?? '';
      _selectedDate = lesson.startTime;
      _startTime = TimeOfDay.fromDateTime(lesson.startTime);
      _endTime = TimeOfDay.fromDateTime(lesson.endTime);
    }
  }

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    _locationController.dispose();
    _priceController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.lesson == null ? 'Nouvelle Leçon' : 'Modifier la Leçon'),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF1E3A8A),
        elevation: 0,
      ),
      body: Form(
        key: _formKey,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Titre
              TextFormField(
                controller: _titleController,
                decoration: const InputDecoration(
                  labelText: 'Titre de la leçon *',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.title),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Le titre est requis';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),

              // Description
              TextFormField(
                controller: _descriptionController,
                decoration: const InputDecoration(
                  labelText: 'Description *',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.description),
                ),
                maxLines: 3,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'La description est requise';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),

              // Date
              ListTile(
                title: const Text('Date *'),
                subtitle: Text(
                  '${_selectedDate.day.toString().padLeft(2, '0')}/${_selectedDate.month.toString().padLeft(2, '0')}/${_selectedDate.year}',
                ),
                leading: const Icon(Icons.calendar_today),
                trailing: const Icon(Icons.arrow_forward_ios),
                onTap: () async {
                  final date = await showDatePicker(
                    context: context,
                    initialDate: _selectedDate,
                    firstDate: DateTime.now(),
                    lastDate: DateTime.now().add(const Duration(days: 365)),
                  );
                  if (date != null) {
                    setState(() {
                      _selectedDate = date;
                    });
                  }
                },
              ),
              const SizedBox(height: 16),

              // Heures
              Row(
                children: [
                  Expanded(
                    child: ListTile(
                      title: const Text('Heure de début *'),
                      subtitle: Text(_startTime.format(context)),
                      leading: const Icon(Icons.access_time),
                      trailing: const Icon(Icons.arrow_forward_ios),
                      onTap: () async {
                        final time = await showTimePicker(
                          context: context,
                          initialTime: _startTime,
                        );
                        if (time != null) {
                          setState(() {
                            _startTime = time;
                          });
                        }
                      },
                    ),
                  ),
                  Expanded(
                    child: ListTile(
                      title: const Text('Heure de fin *'),
                      subtitle: Text(_endTime.format(context)),
                      leading: const Icon(Icons.access_time),
                      trailing: const Icon(Icons.arrow_forward_ios),
                      onTap: () async {
                        final time = await showTimePicker(
                          context: context,
                          initialTime: _endTime,
                        );
                        if (time != null) {
                          setState(() {
                            _endTime = time;
                          });
                        }
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // Lieu
              TextFormField(
                controller: _locationController,
                decoration: const InputDecoration(
                  labelText: 'Lieu (optionnel)',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.location_on),
                ),
              ),
              const SizedBox(height: 16),

              // Prix
              TextFormField(
                controller: _priceController,
                decoration: const InputDecoration(
                  labelText: 'Prix en euros (optionnel)',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.euro),
                ),
                keyboardType: TextInputType.number,
                validator: (value) {
                  if (value != null && value.isNotEmpty) {
                    final price = double.tryParse(value);
                    if (price == null || price < 0) {
                      return 'Prix invalide';
                    }
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),

              // Notes
              TextFormField(
                controller: _notesController,
                decoration: const InputDecoration(
                  labelText: 'Notes (optionnel)',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.note),
                ),
                maxLines: 3,
              ),
              const SizedBox(height: 32),

              // Boutons
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () {
                        Navigator.of(context).pop();
                      },
                      style: OutlinedButton.styleFrom(
                        side: const BorderSide(color: Color(0xFF3B82F6)),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                      child: const Text(
                        'Annuler',
                        style: TextStyle(color: Color(0xFF3B82F6)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: _submitForm,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF3B82F6),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                      child: Text(
                        widget.lesson == null ? 'Créer' : 'Modifier',
                        style: const TextStyle(color: Colors.white),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _submitForm() {
    if (_formKey.currentState!.validate()) {
      final startDateTime = DateTime(
        _selectedDate.year,
        _selectedDate.month,
        _selectedDate.day,
        _startTime.hour,
        _startTime.minute,
      );
      final endDateTime = DateTime(
        _selectedDate.year,
        _selectedDate.month,
        _selectedDate.day,
        _endTime.hour,
        _endTime.minute,
      );

      // Validation des heures
      if (endDateTime.isBefore(startDateTime)) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('L\'heure de fin doit être après l\'heure de début'),
            backgroundColor: Color(0xFFEF4444),
          ),
        );
        return;
      }

      final price = _priceController.text.isNotEmpty
          ? double.tryParse(_priceController.text)
          : null;

      try {
        if (widget.lesson == null) {
          // Créer une nouvelle leçon
          ref.read(teacherProvider.notifier).createLesson(
            title: _titleController.text,
            description: _descriptionController.text,
            startTime: startDateTime,
            endTime: endDateTime,
            location: _locationController.text.isEmpty ? null : _locationController.text,
            price: price,
            notes: _notesController.text.isEmpty ? null : _notesController.text,
          );
        } else {
          // Modifier une leçon existante
          ref.read(teacherProvider.notifier).updateLesson(
            widget.lesson!.id,
            title: _titleController.text,
            description: _descriptionController.text,
            startTime: startDateTime,
            endTime: endDateTime,
            location: _locationController.text.isEmpty ? null : _locationController.text,
            price: price,
            notes: _notesController.text.isEmpty ? null : _notesController.text,
          );
        }

        Navigator.of(context).pop();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              widget.lesson == null
                  ? 'Leçon créée avec succès !'
                  : 'Leçon modifiée avec succès !',
            ),
            backgroundColor: const Color(0xFF10B981),
          ),
        );
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erreur: ${e.toString()}'),
            backgroundColor: const Color(0xFFEF4444),
          ),
        );
      }
    }
  }
}
