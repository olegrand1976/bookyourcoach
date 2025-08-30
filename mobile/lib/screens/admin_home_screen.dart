import 'package:flutter/material.dart';
import 'users_admin_screen.dart';
import 'teacher_approvals_screen.dart';
import 'lessons_admin_screen.dart';
import 'disciplines_admin_screen.dart';

class AdminHomeScreen extends StatelessWidget {
  const AdminHomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Administration')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          ListTile(
            leading: const Icon(Icons.people_outline),
            title: const Text('Utilisateurs'),
            onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const UsersAdminScreen())),
          ),
          ListTile(
            leading: const Icon(Icons.verified_outlined),
            title: const Text('Approbations enseignants'),
            onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const TeacherApprovalsScreen())),
          ),
          ListTile(
            leading: const Icon(Icons.event_note_outlined),
            title: const Text('Leçons (modération)'),
            onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LessonsAdminScreen())),
          ),
          ListTile(
            leading: const Icon(Icons.category_outlined),
            title: const Text('Disciplines'),
            onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const DisciplinesAdminScreen())),
          ),
        ],
      ),
    );
  }
}

