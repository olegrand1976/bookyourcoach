class Payment {
  final String id;
  final String paymentIntentId;
  final int lessonId;
  final double amount;
  final String currency;
  final String status;
  final String? customerEmail;
  final String? paymentMethodId;
  final DateTime createdAt;
  final DateTime? updatedAt;
  final String? failureReason;
  final String? refundReason;

  Payment({
    required this.id,
    required this.paymentIntentId,
    required this.lessonId,
    required this.amount,
    required this.currency,
    required this.status,
    this.customerEmail,
    this.paymentMethodId,
    required this.createdAt,
    this.updatedAt,
    this.failureReason,
    this.refundReason,
  });

  factory Payment.fromJson(Map<String, dynamic> json) {
    return Payment(
      id: json['id'],
      paymentIntentId: json['payment_intent_id'],
      lessonId: json['lesson_id'],
      amount: (json['amount'] as num).toDouble() / 100, // Convertir depuis les centimes
      currency: json['currency'],
      status: json['status'],
      customerEmail: json['customer_email'],
      paymentMethodId: json['payment_method_id'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: json['updated_at'] != null ? DateTime.parse(json['updated_at']) : null,
      failureReason: json['failure_reason'],
      refundReason: json['refund_reason'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'payment_intent_id': paymentIntentId,
      'lesson_id': lessonId,
      'amount': (amount * 100).round(), // Convertir en centimes
      'currency': currency,
      'status': status,
      'customer_email': customerEmail,
      'payment_method_id': paymentMethodId,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
      'failure_reason': failureReason,
      'refund_reason': refundReason,
    };
  }

  // Getters pour faciliter l'utilisation
  bool get isPending => status == 'pending';
  bool get isSucceeded => status == 'succeeded';
  bool get isFailed => status == 'failed';
  bool get isRefunded => status == 'refunded';
  bool get isCancelled => status == 'cancelled';

  String get statusDisplay {
    switch (status) {
      case 'pending':
        return 'En attente';
      case 'succeeded':
        return 'Payé';
      case 'failed':
        return 'Échoué';
      case 'refunded':
        return 'Remboursé';
      case 'cancelled':
        return 'Annulé';
      default:
        return 'Inconnu';
    }
  }

  String get formattedAmount => '${amount.toStringAsFixed(2)} $currency';
}

class PaymentIntent {
  final String id;
  final String clientSecret;
  final double amount;
  final String currency;
  final String status;
  final String? customerEmail;
  final DateTime createdAt;

  PaymentIntent({
    required this.id,
    required this.clientSecret,
    required this.amount,
    required this.currency,
    required this.status,
    this.customerEmail,
    required this.createdAt,
  });

  factory PaymentIntent.fromJson(Map<String, dynamic> json) {
    return PaymentIntent(
      id: json['id'],
      clientSecret: json['client_secret'],
      amount: (json['amount'] as num).toDouble() / 100,
      currency: json['currency'],
      status: json['status'],
      customerEmail: json['customer_email'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'client_secret': clientSecret,
      'amount': (amount * 100).round(),
      'currency': currency,
      'status': status,
      'customer_email': customerEmail,
      'created_at': createdAt.toIso8601String(),
    };
  }
}

class PaymentStats {
  final int totalPayments;
  final double totalAmount;
  final int successfulPayments;
  final int failedPayments;
  final int pendingPayments;
  final int refundedPayments;
  final double averageAmount;
  final Map<String, int> paymentsByStatus;
  final Map<String, double> revenueByMonth;

  PaymentStats({
    required this.totalPayments,
    required this.totalAmount,
    required this.successfulPayments,
    required this.failedPayments,
    required this.pendingPayments,
    required this.refundedPayments,
    required this.averageAmount,
    required this.paymentsByStatus,
    required this.revenueByMonth,
  });

  factory PaymentStats.fromJson(Map<String, dynamic> json) {
    return PaymentStats(
      totalPayments: json['total_payments'],
      totalAmount: (json['total_amount'] as num).toDouble(),
      successfulPayments: json['successful_payments'],
      failedPayments: json['failed_payments'],
      pendingPayments: json['pending_payments'],
      refundedPayments: json['refunded_payments'],
      averageAmount: (json['average_amount'] as num).toDouble(),
      paymentsByStatus: Map<String, int>.from(json['payments_by_status']),
      revenueByMonth: Map<String, double>.from(json['revenue_by_month']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'total_payments': totalPayments,
      'total_amount': totalAmount,
      'successful_payments': successfulPayments,
      'failed_payments': failedPayments,
      'pending_payments': pendingPayments,
      'refunded_payments': refundedPayments,
      'average_amount': averageAmount,
      'payments_by_status': paymentsByStatus,
      'revenue_by_month': revenueByMonth,
    };
  }

  double get successRate => totalPayments > 0 ? (successfulPayments / totalPayments) * 100 : 0;
  String get formattedTotalAmount => '${totalAmount.toStringAsFixed(2)} €';
  String get formattedAverageAmount => '${averageAmount.toStringAsFixed(2)} €';
}
