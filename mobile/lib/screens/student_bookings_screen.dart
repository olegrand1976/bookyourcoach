import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/student_provider.dart';
import '../models/booking.dart';
import '../widgets/custom_button.dart';

class StudentBookingsScreen extends ConsumerStatefulWidget {
  const StudentBookingsScreen({super.key});

  @override
  ConsumerState<StudentBookingsScreen> createState() => _StudentBookingsScreenState();
}

class _StudentBookingsScreenState extends ConsumerState<StudentBookingsScreen> {
  String _selectedFilter = 'all';

  @override
  void initState() {
    super.initState();
    _loadBookings();
  }

  void _loadBookings() {
    ref.read(studentBookingsProvider.notifier).loadBookings(
      status: _selectedFilter == 'all' ? null : _selectedFilter,
    );
  }

  @override
  Widget build(BuildContext context) {
    final bookingsState = ref.watch(studentBookingsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Réservations'),
        backgroundColor: const Color(0xFF059669),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: () => _showFilterDialog(),
          ),
        ],
      ),
      body: Column(
        children: [
          // Filtres
          Container(
            padding: const EdgeInsets.all(16),
            color: const Color(0xFFF0FDF4),
            child: Row(
              children: [
                Expanded(
                  child: _buildFilterChip('Toutes', 'all', _selectedFilter == 'all'),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildFilterChip('En attente', 'pending', _selectedFilter == 'pending'),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildFilterChip('Confirmées', 'confirmed', _selectedFilter == 'confirmed'),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildFilterChip('Terminées', 'completed', _selectedFilter == 'completed'),
                ),
              ],
            ),
          ),
          
          // Liste des réservations
          Expanded(
            child: bookingsState.isLoading
                ? const Center(child: CircularProgressIndicator())
                : bookingsState.error != null
                    ? _buildErrorState(bookingsState.error!)
                    : bookingsState.bookings.isEmpty
                        ? _buildEmptyState()
                        : _buildBookingsList(bookingsState.bookings),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, String value, bool isSelected) {
    return GestureDetector(
      onTap: () {
        setState(() {
          _selectedFilter = value;
        });
        _loadBookings();
      },
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF059669) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? const Color(0xFF059669) : Colors.grey[300]!,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : Colors.grey[700],
            fontSize: 12,
            fontWeight: FontWeight.w500,
          ),
          textAlign: TextAlign.center,
        ),
      ),
    );
  }

  Widget _buildBookingsList(List<Booking> bookings) {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: bookings.length,
      itemBuilder: (context, index) {
        final booking = bookings[index];
        return _buildBookingCard(booking);
      },
    );
  }

  Widget _buildBookingCard(Booking booking) {
    final lesson = booking.lesson;
    if (lesson == null) return const SizedBox.shrink();

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          // En-tête de la réservation
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: _getStatusColor(booking.status).withOpacity(0.1),
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(12),
                topRight: Radius.circular(12),
              ),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        lesson.title,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF1E293B),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        lesson.description,
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey[600],
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: _getStatusColor(booking.status),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    booking.statusDisplay,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Colors.white,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          // Détails de la réservation
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                _buildDetailRow(Icons.access_time, 'Horaires', lesson.formattedTime),
                const SizedBox(height: 8),
                _buildDetailRow(Icons.calendar_today, 'Date', lesson.formattedDate),
                if (lesson.location != null) ...[
                  const SizedBox(height: 8),
                  _buildDetailRow(Icons.location_on, 'Lieu', lesson.location!),
                ],
                if (lesson.price != null) ...[
                  const SizedBox(height: 8),
                  _buildDetailRow(Icons.euro, 'Prix', '${lesson.price}€'),
                ],
                if (lesson.teacher != null) ...[
                  const SizedBox(height: 8),
                  _buildDetailRow(Icons.person, 'Enseignant', lesson.teacher!.displayName),
                ],
                if (booking.notes != null && booking.notes!.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  _buildDetailRow(Icons.note, 'Notes', booking.notes!),
                ],
                if (booking.bookedAt != null) ...[
                  const SizedBox(height: 8),
                  _buildDetailRow(Icons.schedule, 'Réservé le', _formatDate(booking.bookedAt!)),
                ],
              ],
            ),
          ),
          
          // Actions
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.grey[50],
              borderRadius: const BorderRadius.only(
                bottomLeft: Radius.circular(12),
                bottomRight: Radius.circular(12),
              ),
            ),
            child: Row(
              children: [
                if (booking.isConfirmed && lesson.isUpcoming) ...[
                  Expanded(
                    child: CustomOutlinedButton(
                      onPressed: () => _showBookingActions(booking),
                      text: 'Actions',
                      icon: Icons.more_vert,
                    ),
                  ),
                ] else if (booking.isPending) ...[
                  Expanded(
                    child: CustomOutlinedButton(
                      onPressed: () => _cancelBooking(booking),
                      text: 'Annuler',
                      icon: Icons.cancel,
                    ),
                  ),
                ] else if (booking.isCompleted) ...[
                  Expanded(
                    child: CustomButton(
                      onPressed: () => _rateLesson(booking),
                      text: 'Noter le cours',
                      icon: Icons.star,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, size: 16, color: Colors.grey[600]),
        const SizedBox(width: 8),
        Text(
          '$label: ',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: Colors.grey[700],
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[600],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.book_online,
            size: 64,
            color: Colors.grey[400],
          ),
          const SizedBox(height: 16),
          Text(
            'Aucune réservation trouvée',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.grey[600],
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Réservez votre premier cours pour commencer',
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[500],
            ),
          ),
          const SizedBox(height: 24),
          CustomButton(
            onPressed: () {
              // Navigation vers les cours disponibles
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (context) => const Scaffold(
                    body: Center(
                      child: Text('Écran des leçons disponibles'),
                    ),
                  ),
                ),
              );
            },
            text: 'Découvrir des cours',
            icon: Icons.school,
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState(String error) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.error_outline,
            size: 64,
            color: Colors.red[400],
          ),
          const SizedBox(height: 16),
          Text(
            'Erreur',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.red[600],
            ),
          ),
          const SizedBox(height: 8),
          Text(
            error,
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[600],
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 24),
          CustomButton(
            onPressed: () {
              ref.read(studentBookingsProvider.notifier).clearError();
              _loadBookings();
            },
            text: 'Réessayer',
            icon: Icons.refresh,
          ),
        ],
      ),
    );
  }

  void _showFilterDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Filtres'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              title: const Text('Toutes les réservations'),
              leading: Radio<String>(
                value: 'all',
                groupValue: _selectedFilter,
                onChanged: (value) {
                  setState(() {
                    _selectedFilter = value!;
                  });
                  Navigator.of(context).pop();
                  _loadBookings();
                },
              ),
            ),
            ListTile(
              title: const Text('En attente'),
              leading: Radio<String>(
                value: 'pending',
                groupValue: _selectedFilter,
                onChanged: (value) {
                  setState(() {
                    _selectedFilter = value!;
                  });
                  Navigator.of(context).pop();
                  _loadBookings();
                },
              ),
            ),
            ListTile(
              title: const Text('Confirmées'),
              leading: Radio<String>(
                value: 'confirmed',
                groupValue: _selectedFilter,
                onChanged: (value) {
                  setState(() {
                    _selectedFilter = value!;
                  });
                  Navigator.of(context).pop();
                  _loadBookings();
                },
              ),
            ),
            ListTile(
              title: const Text('Terminées'),
              leading: Radio<String>(
                value: 'completed',
                groupValue: _selectedFilter,
                onChanged: (value) {
                  setState(() {
                    _selectedFilter = value!;
                  });
                  Navigator.of(context).pop();
                  _loadBookings();
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showBookingActions(Booking booking) {
    showModalBottomSheet(
      context: context,
      builder: (context) => Container(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.info),
              title: const Text('Détails du cours'),
              onTap: () {
                Navigator.of(context).pop();
                _showLessonDetails(booking);
              },
            ),
            ListTile(
              leading: const Icon(Icons.cancel),
              title: const Text('Annuler la réservation'),
              onTap: () {
                Navigator.of(context).pop();
                _cancelBooking(booking);
              },
            ),
            ListTile(
              leading: const Icon(Icons.contact_support),
              title: const Text('Contacter l\'enseignant'),
              onTap: () {
                Navigator.of(context).pop();
                _contactTeacher(booking);
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showLessonDetails(Booking booking) {
    final lesson = booking.lesson;
    if (lesson == null) return;

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(lesson.title),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Description: ${lesson.description}'),
            const SizedBox(height: 8),
            Text('Date: ${lesson.formattedDate}'),
            Text('Heure: ${lesson.formattedTime}'),
            if (lesson.location != null) Text('Lieu: ${lesson.location}'),
            if (lesson.price != null) Text('Prix: ${lesson.price}€'),
            if (lesson.teacher != null) Text('Enseignant: ${lesson.teacher!.displayName}'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Fermer'),
          ),
        ],
      ),
    );
  }

  void _cancelBooking(Booking booking) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Annuler la réservation'),
        content: const Text('Êtes-vous sûr de vouloir annuler cette réservation ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Non'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              ref.read(studentBookingsProvider.notifier).cancelBooking(booking.id);
            },
            child: const Text('Oui'),
          ),
        ],
      ),
    );
  }

  void _rateLesson(Booking booking) {
    // Navigation vers l'écran de notation
    // TODO: Implémenter l'écran de notation
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Noter le cours'),
        content: const Text('Fonctionnalité de notation à implémenter'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  void _contactTeacher(Booking booking) {
    final lesson = booking.lesson;
    if (lesson?.teacher == null) return;

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Contacter l\'enseignant'),
        content: Text('Contacter ${lesson!.teacher!.displayName}'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              // TODO: Implémenter la fonctionnalité de contact
            },
            child: const Text('Contacter'),
          ),
        ],
      ),
    );
  }

  String _formatDate(DateTime date) {
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year} à ${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}';
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'pending':
        return const Color(0xFFF59E0B);
      case 'confirmed':
        return const Color(0xFF059669);
      case 'completed':
        return const Color(0xFF7C3AED);
      case 'cancelled':
        return const Color(0xFFDC2626);
      default:
        return Colors.grey;
    }
  }
}

