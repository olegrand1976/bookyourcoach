import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/lesson_service.dart';
import '../services/api_client.dart';
import '../state/app_state.dart';
import '../models/lesson.dart';
import 'create_lesson_screen.dart';

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
      body: _loading ? const Center(child: CircularProgressIndicator()) : ListView.builder(
        itemCount: _lessons.length,
        itemBuilder: (_, i) {
          final l = _lessons[i];
          return ListTile(
            title: Text('${l.discipline} • ${l.start}'),
            subtitle: Text('${l.location} • ${l.bookedCount}/${l.capacity}'),
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