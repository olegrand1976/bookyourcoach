import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/payment.dart';
import '../providers/payment_provider.dart';
import '../providers/auth_provider.dart';

class TeacherPaymentsScreen extends ConsumerStatefulWidget {
  const TeacherPaymentsScreen({super.key});

  @override
  ConsumerState<TeacherPaymentsScreen> createState() => _TeacherPaymentsScreenState();
}

class _TeacherPaymentsScreenState extends ConsumerState<TeacherPaymentsScreen> {
  String _selectedFilter = 'all';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(paymentProvider.notifier).loadPaymentHistory();
    });
  }

  @override
  Widget build(BuildContext context) {
    final paymentState = ref.watch(paymentProvider);
    final payments = paymentState.payments;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Paiements'),
        backgroundColor: const Color(0xFF2563EB),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () {
              ref.read(paymentProvider.notifier).loadPaymentHistory();
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Filtres
          Container(
            padding: const EdgeInsets.all(16),
            color: const Color(0xFFF8FAFC),
            child: Row(
              children: [
                Expanded(
                  child: _buildFilterChip('Tous', 'all', _selectedFilter == 'all'),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildFilterChip('Payés', 'succeeded', _selectedFilter == 'succeeded'),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildFilterChip('En attente', 'pending', _selectedFilter == 'pending'),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _buildFilterChip('Échoués', 'failed', _selectedFilter == 'failed'),
                ),
              ],
            ),
          ),

          // Liste des paiements
          Expanded(
            child: paymentState.isLoading
                ? const Center(child: CircularProgressIndicator())
                : paymentState.error != null
                    ? _buildErrorState(paymentState.error!)
                    : payments.isEmpty
                        ? _buildEmptyState()
                        : _buildPaymentsList(payments),
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
        _loadFilteredPayments();
      },
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF2563EB) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? const Color(0xFF2563EB) : Colors.grey[300]!,
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

  void _loadFilteredPayments() {
    String? statusFilter;
    if (_selectedFilter != 'all') {
      statusFilter = _selectedFilter;
    }
    ref.read(paymentProvider.notifier).loadPaymentHistory(status: statusFilter);
  }

  Widget _buildErrorState(String error) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(
            Icons.error_outline,
            size: 64,
            color: Color(0xFFEF4444),
          ),
          const SizedBox(height: 16),
          Text(
            'Erreur de chargement',
            style: const TextStyle(
              fontSize: 18,
              color: Color(0xFF1E3A8A),
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            error,
            style: const TextStyle(
              fontSize: 14,
              color: Color(0xFF6B7280),
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: () {
              ref.read(paymentProvider.notifier).loadPaymentHistory();
            },
            child: const Text('Réessayer'),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return const Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.payment_outlined,
            size: 64,
            color: Color(0xFF6B7280),
          ),
          SizedBox(height: 16),
          Text(
            'Aucun paiement',
            style: TextStyle(
              fontSize: 18,
              color: Color(0xFF6B7280),
            ),
          ),
          SizedBox(height: 8),
          Text(
            'Vous n\'avez pas encore reçu de paiements',
            style: TextStyle(
              fontSize: 14,
              color: Color(0xFF9CA3AF),
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildPaymentsList(List<Payment> payments) {
    return RefreshIndicator(
      onRefresh: () async {
        ref.read(paymentProvider.notifier).loadPaymentHistory();
      },
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: payments.length,
        itemBuilder: (context, index) {
          final payment = payments[index];
          return _buildPaymentCard(payment);
        },
      ),
    );
  }

  Widget _buildPaymentCard(Payment payment) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  payment.formattedAmount,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                _buildStatusChip(payment.status),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              'Leçon #${payment.lessonId}',
              style: const TextStyle(
                fontSize: 14,
                color: Color(0xFF6B7280),
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Date: ${payment.createdAt.toString().substring(0, 16)}',
              style: const TextStyle(
                fontSize: 12,
                color: Color(0xFF9CA3AF),
              ),
            ),
            if (payment.customerEmail != null) ...[
              const SizedBox(height: 4),
              Text(
                'Client: ${payment.customerEmail}',
                style: const TextStyle(
                  fontSize: 12,
                  color: Color(0xFF9CA3AF),
                ),
              ),
            ],
            if (payment.failureReason != null) ...[
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.red.shade50,
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  'Erreur: ${payment.failureReason}',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.red.shade600,
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildStatusChip(String status) {
    Color color;
    String label;

    switch (status) {
      case 'succeeded':
        color = Colors.green;
        label = 'Payé';
        break;
      case 'pending':
        color = Colors.orange;
        label = 'En attente';
        break;
      case 'failed':
        color = Colors.red;
        label = 'Échoué';
        break;
      case 'refunded':
        color = Colors.grey;
        label = 'Remboursé';
        break;
      default:
        color = Colors.grey;
        label = 'Inconnu';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }
}
