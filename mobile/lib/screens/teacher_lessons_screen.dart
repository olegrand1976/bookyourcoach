import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/lesson_service.dart';
import '../services/api_client.dart';
import '../state/app_state.dart';
import '../models/lesson.dart';
import 'create_lesson_screen.dart';
import 'edit_lesson_screen.dart';

class TeacherLessonsScreen extends StatefulWidget {
  const TeacherLessonsScreen({super.key});

  @override
  State<TeacherLessonsScreen> createState() => _TeacherLessonsScreenState();
}

class _TeacherLessonsScreenState extends State<TeacherLessonsScreen> {
  List<Lesson> _lessons = const [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; });
    final app = context.read<AppState>();
    final client = await ApiFactory.authed();
    final service = LessonService(client);
    final list = await service.listTeacherLessons(app.me!.id);
    setState(() { _lessons = list; _loading = false; });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes Leçons')),
      body: _loading ? const Center(child: CircularProgressIndicator()) : ListView.separated(
        itemCount: _lessons.length,
        separatorBuilder: (_, __) => const Divider(height: 1),
        itemBuilder: (_, i) {
          final l = _lessons[i];
          return Dismissible(
            key: ValueKey(l.id),
            direction: DismissDirection.endToStart,
            background: Container(
              color: Colors.red,
              alignment: Alignment.centerRight,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: const Icon(Icons.delete, color: Colors.white),
            ),
            confirmDismiss: (_) async {
              return await showDialog<bool>(
                context: context,
                builder: (_) => AlertDialog(
                  title: const Text('Supprimer la leçon'),
                  content: const Text('Confirmer la suppression ?'),
                  actions: [
                    TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Annuler')),
                    FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Supprimer')),
                  ],
                ),
              ) ?? false;
            },
            onDismissed: (_) async {
              final app = context.read<AppState>();
              final client = await ApiFactory.authed();
              final service = LessonService(client);
              await service.deleteLesson(app.me!.id, l.id);
              _load();
            },
            child: ListTile(
              title: Text('${l.discipline} • ${l.start}'),
              subtitle: Text('${l.location} • ${l.bookedCount}/${l.capacity}'),
              trailing: IconButton(
                icon: const Icon(Icons.edit_outlined),
                onPressed: () async {
                  final updated = await Navigator.of(context).push<bool>(
                    MaterialPageRoute(builder: (_) => EditLessonScreen(lesson: l)),
                  );
                  if (updated == true) _load();
                },
              ),
            ),
          );
        },
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          final created = await Navigator.of(context).push<bool>(
            MaterialPageRoute(builder: (_) => const CreateLessonScreen()),
          );
          if (created == true) {
            // Reload the list after successful creation
            _load();
          }
        },
        icon: const Icon(Icons.add),
        label: const Text('Créer'),
      ),
    );
  }
}