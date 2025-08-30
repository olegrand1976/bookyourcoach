import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/profile_service.dart';
import '../services/api_client.dart';
import '../state/app_state.dart';
import '../l10n/app_localizations.dart' as app_l10n;

class TeacherProfileScreen extends StatefulWidget {
  const TeacherProfileScreen({super.key});

  @override
  State<TeacherProfileScreen> createState() => _TeacherProfileScreenState();
}

class _TeacherProfileScreenState extends State<TeacherProfileScreen> {
  final _disciplinesCtrl = TextEditingController();
  final _yearsCtrl = TextEditingController();
  final _bioCtrl = TextEditingController();
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
      final tp = await service.fetchTeacher(app.me!.id);
      setState(() {
        _disciplinesCtrl.text = tp.disciplines.join(', ');
        _yearsCtrl.text = tp.yearsExperience.toString();
        _bioCtrl.text = tp.bio;
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
      await service.updateTeacher(app.me!.id, {
        'disciplines': _disciplinesCtrl.text.split(',').map((e) => e.trim()).where((e) => e.isNotEmpty).toList(),
        'years_experience': int.tryParse(_yearsCtrl.text) ?? 0,
        'bio': _bioCtrl.text,
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
    _disciplinesCtrl.dispose();
    _yearsCtrl.dispose();
    _bioCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final t = app_l10n.AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(t?.teacherProfileTitle ?? 'Profil Enseignant')),
      body: _loading ? const Center(child: CircularProgressIndicator()) : ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_error != null) ...[
            Text(t?.errorLoad ?? _error!, style: const TextStyle(color: Colors.red)),
            const SizedBox(height: 8),
          ],
          Text(t?.teacherDisciplines ?? 'Disciplines (séparées par des virgules)'),
          const SizedBox(height: 8),
          TextFormField(
            controller: _disciplinesCtrl,
            decoration: const InputDecoration(border: OutlineInputBorder()),
          ),
          const SizedBox(height: 16),
          Text(t?.teacherYears ?? "Années d'expérience"),
          const SizedBox(height: 8),
          TextFormField(
            controller: _yearsCtrl,
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(border: OutlineInputBorder()),
            validator: (v) {
              final n = int.tryParse(v ?? '');
              if (n == null || n < 0) return '0+';
              return null;
            },
          ),
          const SizedBox(height: 16),
          Text(t?.teacherBio ?? 'Bio'),
          const SizedBox(height: 8),
          TextFormField(
            controller: _bioCtrl,
            maxLines: 4,
            decoration: const InputDecoration(border: OutlineInputBorder()),
            validator: (v) => (v == null || v.trim().isEmpty) ? 'Requis' : null,
          ),
          const SizedBox(height: 24),
          ElevatedButton(onPressed: _loading ? null : _save, child: Text(t?.commonSave ?? 'Enregistrer')),
        ],
      ),
    );
  }
}