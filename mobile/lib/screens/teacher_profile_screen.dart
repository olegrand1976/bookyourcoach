import 'package:flutter/material.dart';

class TeacherProfileScreen extends StatelessWidget {
  const TeacherProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Profil Enseignant')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const Text('Disciplines (séparées par des virgules)'),
          const SizedBox(height: 8),
          TextFormField(
            decoration: const InputDecoration(border: OutlineInputBorder()),
          ),
          const SizedBox(height: 16),
          const Text('Années d\'expérience'),
          const SizedBox(height: 8),
          TextFormField(
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(border: OutlineInputBorder()),
          ),
          const SizedBox(height: 16),
          const Text('Bio'),
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