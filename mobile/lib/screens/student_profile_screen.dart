import 'package:flutter/material.dart';

class StudentProfileScreen extends StatelessWidget {
  const StudentProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Profil Élève')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text('Niveau'),
          const SizedBox(height: 8),
          DropdownButtonFormField<String>(
            items: const [
              DropdownMenuItem(value: 'beginner', child: Text('Débutant')),
              DropdownMenuItem(value: 'intermediate', child: Text('Intermédiaire')),
              DropdownMenuItem(value: 'advanced', child: Text('Avancé')),
            ],
            onChanged: (_) {},
          ),
          const SizedBox(height: 16),
          const Text('Objectifs'),
          const SizedBox(height: 8),
          TextFormField(
            maxLines: 4,
            decoration: const InputDecoration(border: OutlineInputBorder()),
          ),
          const SizedBox(height: 24),
          ElevatedButton(onPressed: () {}, child: const Text('Enregistrer')),
        ],
      ),
    );
  }
}