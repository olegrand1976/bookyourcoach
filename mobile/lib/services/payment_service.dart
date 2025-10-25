import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../config/api_config.dart';

class PaymentService {
  final Dio _dio;

  PaymentService() : _dio = Dio(BaseOptions(
    baseUrl: ApiConfig.baseUrl,
    connectTimeout: const Duration(seconds: 30),
    receiveTimeout: const Duration(seconds: 30),
  ));

  // Créer une intention de paiement
  Future<Map<String, dynamic>> createPaymentIntent({
    required int lessonId,
    required double amount,
    required String currency,
    String? customerEmail,
  }) async {
    try {
      final response = await _dio.post(
        '/api/payments/create-intent',
        data: {
          'lesson_id': lessonId,
          'amount': (amount * 100).round(), // Stripe utilise les centimes
          'currency': currency,
          'customer_email': customerEmail,
        },
      );
      return response.data;
    } catch (e) {
      throw Exception('Erreur lors de la création de l\'intention de paiement: $e');
    }
  }

  // Confirmer un paiement
  Future<Map<String, dynamic>> confirmPayment({
    required String paymentIntentId,
    required String paymentMethodId,
  }) async {
    try {
      final response = await _dio.post(
        '/api/payments/confirm',
        data: {
          'payment_intent_id': paymentIntentId,
          'payment_method_id': paymentMethodId,
        },
      );
      return response.data;
    } catch (e) {
      throw Exception('Erreur lors de la confirmation du paiement: $e');
    }
  }

  // Récupérer l'historique des paiements
  Future<List<Map<String, dynamic>>> getPaymentHistory({
    String? status,
    int? limit,
    int? offset,
  }) async {
    try {
      final response = await _dio.get(
        '/api/payments/history',
        queryParameters: {
          if (status != null) 'status': status,
          if (limit != null) 'limit': limit,
          if (offset != null) 'offset': offset,
        },
      );
      return List<Map<String, dynamic>>.from(response.data['payments']);
    } catch (e) {
      throw Exception('Erreur lors de la récupération de l\'historique: $e');
    }
  }

  // Rembourser un paiement
  Future<Map<String, dynamic>> refundPayment({
    required String paymentIntentId,
    double? amount,
    String? reason,
  }) async {
    try {
      final response = await _dio.post(
        '/api/payments/refund',
        data: {
          'payment_intent_id': paymentIntentId,
          if (amount != null) 'amount': (amount * 100).round(),
          if (reason != null) 'reason': reason,
        },
      );
      return response.data;
    } catch (e) {
      throw Exception('Erreur lors du remboursement: $e');
    }
  }

  // Récupérer les statistiques de paiement (pour les admins)
  Future<Map<String, dynamic>> getPaymentStats({
    String? period,
    String? status,
  }) async {
    try {
      final response = await _dio.get(
        '/api/payments/stats',
        queryParameters: {
          if (period != null) 'period': period,
          if (status != null) 'status': status,
        },
      );
      return response.data;
    } catch (e) {
      throw Exception('Erreur lors de la récupération des statistiques: $e');
    }
  }
}

// Provider pour le service de paiement
final paymentServiceProvider = Provider<PaymentService>((ref) {
  return PaymentService();
});
