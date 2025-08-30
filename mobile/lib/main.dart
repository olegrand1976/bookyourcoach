import 'package:flutter/material.dart';
import 'config.dart';

void main() {
  runApp(const BookYourCoachApp());
}

class BookYourCoachApp extends StatelessWidget {
  const BookYourCoachApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'BookYourCoach',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.brown),
        useMaterial3: true,
      ),
      home: const PlaceholderHome(),
    );
  }
}

class PlaceholderHome extends StatelessWidget {
  const PlaceholderHome({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('BookYourCoach Mobile')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('API: ${AppConfig.apiBase}', style: const TextStyle(fontSize: 16)),
            const SizedBox(height: 12),
            const Text('Squelette Flutter prêt. Ajoutez services, routes et i18n.'),
          ],
        ),
      ),
    );
  }
}