import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/api_client.dart';
import '../services/lesson_service.dart';
import '../state/app_state.dart';
import '../models/lesson.dart';

class AvailableLessonsScreen extends StatefulWidget {
  const AvailableLessonsScreen({super.key});

  @override
  State<AvailableLessonsScreen> createState() => _AvailableLessonsScreenState();
}

class _AvailableLessonsScreenState extends State<AvailableLessonsScreen> {
  bool _loading = true;
  List<Lesson> _lessons = const [];
  final Map<String, bool> _booking = {};

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; });
    final client = await ApiFactory.authed();
    final service = LessonService(client);
    final list = await service.listAvailableLessons();
    setState(() { _lessons = list; _loading = false; });
  }

  Future<void> _book(String lessonId) async {
    final app = context.read<AppState>();
    setState(() { _booking[lessonId] = true; });
    try {
      final client = await ApiFactory.authed();
      final service = LessonService(client);
      await service.bookLesson(app.me!.id, lessonId);
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
      if (mounted) setState(() { _booking[lessonId] = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Leçons disponibles')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: _lessons.length,
                separatorBuilder: (_, __) => const SizedBox(height: 12),
                itemBuilder: (_, i) {
                  final l = _lessons[i];
                  final booked = _booking[l.id] == true;
                  return Card(
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(Icons.pets, color: Colors.brown),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  l.discipline,
                                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: Colors.blue.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text('${l.bookedCount}/${l.capacity}')
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              const Icon(Icons.schedule, size: 18),
                              const SizedBox(width: 6),
                              Text('${l.start} • ${l.durationMinutes} min'),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Row(
                            children: [
                              const Icon(Icons.place_outlined, size: 18),
                              const SizedBox(width: 6),
                              Expanded(child: Text(l.location)),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: FilledButton.tonalIcon(
                                  onPressed: booked ? null : () => _book(l.id),
                                  icon: booked
                                      ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                                      : const Icon(Icons.event_available),
                                  label: const Text('Réserver'),
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
            ),
    );
  }
}

