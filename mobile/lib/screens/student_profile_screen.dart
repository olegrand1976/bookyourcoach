import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/profile_service.dart';
import '../services/api_client.dart';
import '../state/app_state.dart';
import '../models/user.dart';

class StudentProfileScreen extends StatefulWidget {
  const StudentProfileScreen({super.key});

  @override
  State<StudentProfileScreen> createState() => _StudentProfileScreenState();
}

class _StudentProfileScreenState extends State<StudentProfileScreen> {
  String _level = 'beginner';
  final _objectivesCtrl = TextEditingController();
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final app = context.read<AppState>();
      final client = await ApiFactory.authed();
      final service = ProfileService(client);
      final StudentProfile sp = await service.fetchStudent(app.me!.id);
      setState(() {
        _level = sp.level;
        _objectivesCtrl.text = sp.objectives;
      });
    } catch (e) {
      setState(() { _error = 'Erreur de chargement'; });
    } finally {
      setState(() { _loading = false; });
    }
  }

  Future<void> _save() async {
    setState(() { _loading = true; _error = null; });
    try {
      final app = context.read<AppState>();
      final client = await ApiFactory.authed();
      final service = ProfileService(client);
      await service.updateStudent(app.me!.id, {
        'level': _level,
        'objectives': _objectivesCtrl.text,
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profil sauvegardé')));
    } catch (e) {
      setState(() { _error = 'Erreur de sauvegarde'; });
    } finally {
      setState(() { _loading = false; });
    }
  }

  @override
  void dispose() {
    _objectivesCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Profil Élève')),
      body: _loading ? const Center(child: CircularProgressIndicator()) : ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_error != null) ...[
            Text(_error!, style: const TextStyle(color: Colors.red)),
            const SizedBox(height: 8),
          ],
          const Text('Niveau'),
          const SizedBox(height: 8),
          DropdownButtonFormField<String>(
            value: _level,
            items: const [
              DropdownMenuItem(value: 'beginner', child: Text('Débutant')),
              DropdownMenuItem(value: 'intermediate', child: Text('Intermédiaire')),
              DropdownMenuItem(value: 'advanced', child: Text('Avancé')),
            ],
            onChanged: (v) => setState(() => _level = v ?? 'beginner'),
          ),
          const SizedBox(height: 16),
          const Text('Objectifs'),
          const SizedBox(height: 8),
          TextFormField(
            controller: _objectivesCtrl,
            maxLines: 4,
            decoration: const InputDecoration(border: OutlineInputBorder()),
          ),
          const SizedBox(height: 24),
          ElevatedButton(onPressed: _loading ? null : _save, child: const Text('Enregistrer')),
        ],
      ),
    );
  }
}