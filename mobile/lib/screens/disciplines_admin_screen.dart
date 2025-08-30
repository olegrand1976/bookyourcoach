import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../services/admin_service.dart';

class DisciplinesAdminScreen extends StatefulWidget {
  const DisciplinesAdminScreen({super.key});

  @override
  State<DisciplinesAdminScreen> createState() => _DisciplinesAdminScreenState();
}

class _DisciplinesAdminScreenState extends State<DisciplinesAdminScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _items = const [];
  final _controller = TextEditingController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; });
    final client = await ApiFactory.authed();
    final service = AdminService(client);
    final list = await service.listDisciplines();
    setState(() { _items = list; _loading = false; });
  }

  Future<void> _create() async {
    final name = _controller.text.trim();
    if (name.isEmpty) return;
    final client = await ApiFactory.authed();
    final service = AdminService(client);
    await service.createDiscipline(name);
    _controller.clear();
    await _load();
  }

  Future<void> _edit(String id, String current) async {
    final controller = TextEditingController(text: current);
    final res = await showDialog<String>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Éditer la discipline'),
        content: TextField(controller: controller),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Annuler')),
          FilledButton(onPressed: () => Navigator.pop(context, controller.text.trim()), child: const Text('Enregistrer')),
        ],
      ),
    );
    if (res != null && res.isNotEmpty) {
      final client = await ApiFactory.authed();
      final service = AdminService(client);
      await service.updateDiscipline(id, res);
      await _load();
    }
  }

  Future<void> _delete(String id) async {
    final client = await ApiFactory.authed();
    final service = AdminService(client);
    await service.deleteDiscipline(id);
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Disciplines')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _controller,
                    decoration: const InputDecoration(
                      hintText: 'Nouvelle discipline',
                      border: OutlineInputBorder(),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                FilledButton(onPressed: _create, child: const Text('Ajouter')),
              ],
            ),
          ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : ListView.separated(
                    itemCount: _items.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (_, i) {
                      final d = _items[i];
                      return ListTile(
                        title: Text(d['name']?.toString() ?? ''),
                        trailing: Wrap(
                          children: [
                            IconButton(onPressed: () => _edit(d['id'].toString(), d['name']?.toString() ?? ''), icon: const Icon(Icons.edit_outlined)),
                            IconButton(onPressed: () => _delete(d['id'].toString()), icon: const Icon(Icons.delete_outline, color: Colors.red)),
                          ],
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}

