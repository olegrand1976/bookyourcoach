import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/lesson_service.dart';
import '../services/api_client.dart';
import '../state/app_state.dart';
import '../models/lesson.dart';

class StudentBookingsScreen extends StatefulWidget {
  const StudentBookingsScreen({super.key});

  @override
  State<StudentBookingsScreen> createState() => _StudentBookingsScreenState();
}

class _StudentBookingsScreenState extends State<StudentBookingsScreen> {
  List<Booking> _bookings = const [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; });
    final app = context.read<AppState>();
    final client = await ApiFactory.authed();
    final service = LessonService(client);
    final list = await service.listStudentBookings(app.me!.id);
    setState(() { _bookings = list; _loading = false; });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes Réservations')),
      body: _loading ? const Center(child: CircularProgressIndicator()) : ListView.builder(
        itemCount: _bookings.length,
        itemBuilder: (_, i) {
          final b = _bookings[i];
          return ListTile(
            leading: const Icon(Icons.event_available),
            title: Text('Leçon ${b.lessonId}'),
            subtitle: Text('Statut: ${b.status}'),
          );
        },
      ),
    );
  }
}