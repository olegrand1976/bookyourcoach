import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../services/admin_service.dart';

class TeacherApprovalsScreen extends StatefulWidget {
  const TeacherApprovalsScreen({super.key});

  @override
  State<TeacherApprovalsScreen> createState() => _TeacherApprovalsScreenState();
}

class _TeacherApprovalsScreenState extends State<TeacherApprovalsScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _requests = const [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; });
    final client = await ApiFactory.authed();
    final service = AdminService(client);
    final list = await service.listTeacherApprovals();
    setState(() { _requests = list; _loading = false; });
  }

  Future<void> _approve(String id, bool approved) async {
    final client = await ApiFactory.authed();
    final service = AdminService(client);
    await service.approveTeacher(id, approved);
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Approbations enseignants')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView.separated(
              itemCount: _requests.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (_, i) {
                final r = _requests[i];
                return ListTile(
                  leading: const Icon(Icons.school_outlined),
                  title: Text(r['name']?.toString() ?? 'N/A'),
                  subtitle: Text(r['email']?.toString() ?? ''),
                  trailing: Wrap(
                    children: [
                      IconButton(onPressed: () => _approve(r['id'].toString(), true), icon: const Icon(Icons.check_circle_outline, color: Colors.green)),
                      IconButton(onPressed: () => _approve(r['id'].toString(), false), icon: const Icon(Icons.cancel_outlined, color: Colors.red)),
                    ],
                  ),
                );
              },
            ),
    );
  }
}

