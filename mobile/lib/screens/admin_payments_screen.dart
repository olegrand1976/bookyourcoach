import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/payment.dart';
import '../providers/payment_provider.dart';

class AdminPaymentsScreen extends ConsumerStatefulWidget {
  const AdminPaymentsScreen({super.key});

  @override
  ConsumerState<AdminPaymentsScreen> createState() => _AdminPaymentsScreenState();
}

class _AdminPaymentsScreenState extends ConsumerState<AdminPaymentsScreen> {
  String _selectedFilter = 'all';
  String _selectedPeriod = 'month';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(paymentProvider.notifier).loadPaymentHistory();
      ref.read(paymentProvider.notifier).loadPaymentStats(period: _selectedPeriod);
    });
  }

  @override
  Widget build(BuildContext context) {
    final paymentState = ref.watch(paymentProvider);
    final payments = paymentState.payments;
    final stats = paymentState.stats;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Gestion des Paiements'),
        backgroundColor: const Color(0xFF1E3A8A),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () {
              ref.read(paymentProvider.notifier).loadPaymentHistory();
              ref.read(paymentProvider.notifier).loadPaymentStats(period: _selectedPeriod);
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Statistiques
          if (stats != null) _buildStatsSection(stats),

          // Filtres
          Container(
            padding: const EdgeInsets.all(16),
            color: const Color(0xFFF8FAFC),
            child: Column(
              children: [
                // Filtres par statut
                Row(
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
                const SizedBox(height: 12),
                // Filtres par période
                Row(
                  children: [
                    Expanded(
                      child: _buildPeriodChip('Mois', 'month', _selectedPeriod == 'month'),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildPeriodChip('Trimestre', 'quarter', _selectedPeriod == 'quarter'),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildPeriodChip('Année', 'year', _selectedPeriod == 'year'),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildPeriodChip('Tout', 'all', _selectedPeriod == 'all'),
                    ),
                  ],
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

  Widget _buildStatsSection(PaymentStats stats) {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 3,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Statistiques des Paiements',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _buildStatCard(
                  'Total',
                  stats.formattedTotalAmount,
                  '${stats.totalPayments} paiements',
                  Icons.payment,
                  const Color(0xFF3B82F6),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildStatCard(
                  'Moyenne',
                  stats.formattedAverageAmount,
                  '${stats.successRate.toStringAsFixed(1)}% succès',
                  Icons.trending_up,
                  const Color(0xFF10B981),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildStatCard(
                  'Payés',
                  '${stats.successfulPayments}',
                  'Paiements réussis',
                  Icons.check_circle,
                  Colors.green,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildStatCard(
                  'Échoués',
                  '${stats.failedPayments}',
                  'Paiements échoués',
                  Icons.error,
                  Colors.red,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(String title, String value, String subtitle, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: color, size: 20),
              const SizedBox(width: 8),
              Text(
                title,
                style: TextStyle(
                  color: color,
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              color: color,
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
          Text(
            subtitle,
            style: TextStyle(
              color: color.withOpacity(0.7),
              fontSize: 10,
            ),
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
          color: isSelected ? const Color(0xFF1E3A8A) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? const Color(0xFF1E3A8A) : Colors.grey[300]!,
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

  Widget _buildPeriodChip(String label, String value, bool isSelected) {
    return GestureDetector(
      onTap: () {
        setState(() {
          _selectedPeriod = value;
        });
        ref.read(paymentProvider.notifier).loadPaymentStats(period: value);
      },
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF1E3A8A) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? const Color(0xFF1E3A8A) : Colors.grey[300]!,
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
              ref.read(paymentProvider.notifier).loadPaymentStats(period: _selectedPeriod);
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
            'Aucun paiement trouvé pour les critères sélectionnés',
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
        ref.read(paymentProvider.notifier).loadPaymentStats(period: _selectedPeriod);
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
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                if (payment.isSucceeded)
                  TextButton.icon(
                    onPressed: () => _showRefundDialog(payment),
                    icon: const Icon(Icons.refresh, size: 16),
                    label: const Text('Rembourser'),
                    style: TextButton.styleFrom(
                      foregroundColor: Colors.orange,
                    ),
                  ),
              ],
            ),
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

  void _showRefundDialog(Payment payment) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Rembourser le paiement'),
        content: Text(
          'Voulez-vous rembourser le paiement de ${payment.formattedAmount} ?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.of(context).pop();
              await _processRefund(payment);
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.orange,
              foregroundColor: Colors.white,
            ),
            child: const Text('Rembourser'),
          ),
        ],
      ),
    );
  }

  Future<void> _processRefund(Payment payment) async {
    try {
      final success = await ref.read(paymentProvider.notifier).refundPayment(
        paymentIntentId: payment.paymentIntentId,
        reason: 'Remboursement administrateur',
      );

      if (success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Remboursement effectué avec succès'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Erreur lors du remboursement'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erreur: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }
}
