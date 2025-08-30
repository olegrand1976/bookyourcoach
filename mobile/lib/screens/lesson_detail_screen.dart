import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/lesson.dart';
import '../services/api_client.dart';
import '../services/lesson_service.dart';
import '../state/app_state.dart';

class LessonDetailScreen extends StatefulWidget {
  final Lesson lesson;
  const LessonDetailScreen({super.key, required this.lesson});

  @override
  State<LessonDetailScreen> createState() => _LessonDetailScreenState();
}

class _LessonDetailScreenState extends State<LessonDetailScreen> {
  bool _booking = false;

  Future<void> _book() async {
    final app = context.read<AppState>();
    setState(() { _booking = true; });
    try {
      final client = await ApiFactory.authed();
      final service = LessonService(client);
      await service.bookLesson(app.me!.id, widget.lesson.id);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Réservation confirmée')),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Échec de la réservation: $e')),
      );
    } finally {
      if (mounted) setState(() { _booking = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = widget.lesson;
    return Scaffold(
      appBar: AppBar(title: const Text('Détail de la leçon')),
      body: SingleChildScrollView(
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
                    Row(
                      children: [
                        const Icon(Icons.pets, color: Colors.brown),
                        const SizedBox(width: 8),
                        Text(l.discipline, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(children: [const Icon(Icons.schedule), const SizedBox(width: 8), Text('${l.start} • ${l.durationMinutes} min')]),
                    const SizedBox(height: 8),
                    Row(children: [const Icon(Icons.place_outlined), const SizedBox(width: 8), Expanded(child: Text(l.location))]),
                    const SizedBox(height: 8),
                    Row(children: [const Icon(Icons.people_alt_outlined), const SizedBox(width: 8), Text('${l.bookedCount}/${l.capacity} places')]),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            Card(
              child: ListTile(
                leading: const Icon(Icons.info_outline),
                title: const Text('Description'),
                subtitle: Text('Leçon de ${l.discipline} au ${l.location}.'),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
          child: Row(
            children: [
              Expanded(
                child: FilledButton.icon(
                  onPressed: _booking ? null : _book,
                  icon: _booking
                      ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Icon(Icons.event_available),
                  label: const Text('Réserver'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

