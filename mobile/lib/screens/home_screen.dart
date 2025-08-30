import 'package:flutter/material.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Accueil')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: const [
          Text('Écran d\'accueil mobile'),
          SizedBox(height: 12),
          Text('À venir: profils élève/enseignant, édition, réservations.'),
        ],
      ),
    );
  }
}