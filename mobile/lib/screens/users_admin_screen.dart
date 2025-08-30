import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/api_client.dart';
import '../services/admin_service.dart';
import '../state/app_state.dart';

class UsersAdminScreen extends StatefulWidget {
  const UsersAdminScreen({super.key});

  @override
  State<UsersAdminScreen> createState() => _UsersAdminScreenState();
}

class _UsersAdminScreenState extends State<UsersAdminScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _users = const [];
  final _search = TextEditingController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; });
    final client = await ApiFactory.authed();
    final service = AdminService(client);
    final list = await service.listUsers(query: _search.text.trim().isEmpty ? null : _search.text.trim());
    setState(() { _users = list; _loading = false; });
  }

  Future<void> _toggleActive(String userId, bool active) async {
    final client = await ApiFactory.authed();
    final service = AdminService(client);
    await service.setUserActive(userId, active);
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    final isAdmin = context.watch<AppState>().isAdmin;
    return Scaffold(
      appBar: AppBar(title: const Text('Utilisateurs')),
      body: !isAdmin
          ? const Center(child: Text('Accès refusé'))
          : Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _search,
                          decoration: const InputDecoration(
                            hintText: 'Rechercher par email/nom…',
                            prefixIcon: Icon(Icons.search),
                            border: OutlineInputBorder(),
                          ),
                          onSubmitted: (_) => _load(),
                        ),
                      ),
                      const SizedBox(width: 12),
                      FilledButton(onPressed: _load, child: const Text('Filtrer')),
                    ],
                  ),
                ),
                Expanded(
                  child: _loading
                      ? const Center(child: CircularProgressIndicator())
                      : ListView.separated(
                          itemCount: _users.length,
                          separatorBuilder: (_, __) => const Divider(height: 1),
                          itemBuilder: (_, i) {
                            final u = _users[i];
                            final active = u['active'] == true;
                            return ListTile(
                              leading: const CircleAvatar(child: Icon(Icons.person_outline)),
                              title: Text(u['name']?.toString() ?? ''),
                              subtitle: Text(u['email']?.toString() ?? ''),
                              trailing: Switch(
                                value: active,
                                onChanged: (v) => _toggleActive(u['id'].toString(), v),
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

