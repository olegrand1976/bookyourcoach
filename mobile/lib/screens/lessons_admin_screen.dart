import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../services/admin_service.dart';

class LessonsAdminScreen extends StatefulWidget {
  const LessonsAdminScreen({super.key});

  @override
  State<LessonsAdminScreen> createState() => _LessonsAdminScreenState();
}

class _LessonsAdminScreenState extends State<LessonsAdminScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _lessons = const [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; });
    final client = await ApiFactory.authed();
    final service = AdminService(client);
    final list = await service.listLessonsModeration();
    setState(() { _lessons = list; _loading = false; });
  }

  Future<void> _delete(String id) async {
    final client = await ApiFactory.authed();
    final service = AdminService(client);
    await service.deleteLesson(id);
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Leçons (modération)')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView.builder(
              itemCount: _lessons.length,
              itemBuilder: (_, i) {
                final l = _lessons[i];
                return ListTile(
                  leading: const Icon(Icons.event_note_outlined),
                  title: Text('${l['discipline']} • ${l['start']}'),
                  subtitle: Text('${l['location']} • ${l['booked_count']}/${l['capacity']}'),
                  trailing: IconButton(
                    icon: const Icon(Icons.delete_outline, color: Colors.red),
                    onPressed: () => _delete(l['id'].toString()),
                  ),
                );
              },
            ),
    );
  }
}

