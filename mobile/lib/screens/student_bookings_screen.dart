import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/lesson_service.dart';
import '../services/api_client.dart';
import '../state/app_state.dart';
import '../models/lesson.dart';
import 'available_lessons_screen.dart';

class StudentBookingsScreen extends StatefulWidget {
  const StudentBookingsScreen({super.key});

  @override
  State<StudentBookingsScreen> createState() => _StudentBookingsScreenState();
}

class _StudentBookingsScreenState extends State<StudentBookingsScreen> {
  List<Booking> _bookings = const [];
  bool _loading = true;
  final Map<String, bool> _cancelling = {};

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

  Future<void> _cancel(String bookingId) async {
    final app = context.read<AppState>();
    setState(() { _cancelling[bookingId] = true; });
    try {
      final client = await ApiFactory.authed();
      final service = LessonService(client);
      await service.cancelBooking(app.me!.id, bookingId);
      await _load();
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Annulation impossible: $e')),
      );
    } finally {
      if (mounted) setState(() { _cancelling[bookingId] = false; });
    }
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
            trailing: b.status == 'confirmed' ? TextButton.icon(
              onPressed: _cancelling[b.id] == true ? null : () => _cancel(b.id),
              icon: _cancelling[b.id] == true ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.cancel_outlined),
              label: const Text('Annuler'),
            ) : null,
          );
        },
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const AvailableLessonsScreen()),
          );
        },
        icon: const Icon(Icons.explore),
        label: const Text('Explorer'),
      ),
    );
  }
}