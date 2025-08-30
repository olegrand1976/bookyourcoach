import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/api_client.dart';
import '../services/lesson_service.dart';
import '../state/app_state.dart';

class LessonAttendeesScreen extends StatefulWidget {
  final String lessonId;
  const LessonAttendeesScreen({super.key, required this.lessonId});

  @override
  State<LessonAttendeesScreen> createState() => _LessonAttendeesScreenState();
}

class _LessonAttendeesScreenState extends State<LessonAttendeesScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _attendees = const [];

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
    final list = await service.lessonAttendees(app.me!.id, widget.lessonId);
    setState(() { _attendees = list; _loading = false; });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Participants')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: _attendees.length,
                separatorBuilder: (_, __) => const Divider(height: 1),
                itemBuilder: (_, i) {
                  final a = _attendees[i];
                  return ListTile(
                    leading: const CircleAvatar(child: Icon(Icons.person)),
                    title: Text(a['name']?.toString() ?? 'Inconnu'),
                    subtitle: Text(a['email']?.toString() ?? ''),
                  );
                },
              ),
            ),
    );
  }
}

