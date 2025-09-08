import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/payment.dart';
import '../services/payment_service.dart';

// État pour les paiements
class PaymentState {
  final bool isLoading;
  final String? error;
  final List<Payment> payments;
  final PaymentStats? stats;
  final PaymentIntent? currentPaymentIntent;

  PaymentState({
    this.isLoading = false,
    this.error,
    this.payments = const [],
    this.stats,
    this.currentPaymentIntent,
  });

  PaymentState copyWith({
    bool? isLoading,
    String? error,
    List<Payment>? payments,
    PaymentStats? stats,
    PaymentIntent? currentPaymentIntent,
  }) {
    return PaymentState(
      isLoading: isLoading ?? this.isLoading,
      error: error ?? this.error,
      payments: payments ?? this.payments,
      stats: stats ?? this.stats,
      currentPaymentIntent: currentPaymentIntent ?? this.currentPaymentIntent,
    );
  }
}

// Notifier pour les paiements
class PaymentNotifier extends StateNotifier<PaymentState> {
  final PaymentService _paymentService;

  PaymentNotifier(this._paymentService) : super(PaymentState());

  // Créer une intention de paiement
  Future<void> createPaymentIntent({
    required int lessonId,
    required double amount,
    required String currency,
    String? customerEmail,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final data = await _paymentService.createPaymentIntent(
        lessonId: lessonId,
        amount: amount,
        currency: currency,
        customerEmail: customerEmail,
      );
      
      final paymentIntent = PaymentIntent.fromJson(data);
      state = state.copyWith(
        isLoading: false,
        currentPaymentIntent: paymentIntent,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  // Confirmer un paiement
  Future<bool> confirmPayment({
    required String paymentIntentId,
    required String paymentMethodId,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final data = await _paymentService.confirmPayment(
        paymentIntentId: paymentIntentId,
        paymentMethodId: paymentMethodId,
      );
      
      state = state.copyWith(isLoading: false);
      
      // Recharger l'historique des paiements
      await loadPaymentHistory();
      
      return data['status'] == 'succeeded';
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
      return false;
    }
  }

  // Charger l'historique des paiements
  Future<void> loadPaymentHistory({
    String? status,
    int? limit,
    int? offset,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final data = await _paymentService.getPaymentHistory(
        status: status,
        limit: limit,
        offset: offset,
      );
      
      final payments = data.map((json) => Payment.fromJson(json)).toList();
      state = state.copyWith(
        isLoading: false,
        payments: payments,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  // Rembourser un paiement
  Future<bool> refundPayment({
    required String paymentIntentId,
    double? amount,
    String? reason,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final data = await _paymentService.refundPayment(
        paymentIntentId: paymentIntentId,
        amount: amount,
        reason: reason,
      );
      
      state = state.copyWith(isLoading: false);
      
      // Recharger l'historique des paiements
      await loadPaymentHistory();
      
      return data['status'] == 'succeeded';
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
      return false;
    }
  }

  // Charger les statistiques de paiement (pour les admins)
  Future<void> loadPaymentStats({
    String? period,
    String? status,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    
    try {
      final data = await _paymentService.getPaymentStats(
        period: period,
        status: status,
      );
      
      final stats = PaymentStats.fromJson(data);
      state = state.copyWith(
        isLoading: false,
        stats: stats,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  // Réinitialiser l'état
  void reset() {
    state = PaymentState();
  }
}

// Providers
final paymentProvider = StateNotifierProvider<PaymentNotifier, PaymentState>((ref) {
  final paymentService = ref.watch(paymentServiceProvider);
  return PaymentNotifier(paymentService);
});

// Provider pour les paiements d'un étudiant spécifique
final studentPaymentsProvider = FutureProvider.family<List<Payment>, String>((ref, studentId) async {
  final paymentService = ref.watch(paymentServiceProvider);
  final data = await paymentService.getPaymentHistory(status: 'succeeded');
  return data.map((json) => Payment.fromJson(json)).toList();
});

// Provider pour les paiements d'un enseignant spécifique
final teacherPaymentsProvider = FutureProvider.family<List<Payment>, String>((ref, teacherId) async {
  final paymentService = ref.watch(paymentServiceProvider);
  final data = await paymentService.getPaymentHistory(status: 'succeeded');
  return data.map((json) => Payment.fromJson(json)).toList();
});
