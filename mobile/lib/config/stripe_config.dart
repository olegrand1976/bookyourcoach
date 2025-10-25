import 'package:flutter/foundation.dart';
// import 'package:stripe_platform_interface/stripe_platform_interface.dart';

class StripeConfig {
  static const String _publishableKeyTest = 'pk_test_your_stripe_publishable_key';
  static const String _publishableKeyLive = 'pk_live_your_stripe_publishable_key';
  
  // Configuration pour le développement
  static const bool _isTestMode = true;
  
  static String get publishableKey {
    return _isTestMode ? _publishableKeyTest : _publishableKeyLive;
  }
  
  static bool get isTestMode => _isTestMode;
  
  // Initialiser Stripe selon la plateforme
  static Future<void> initialize() async {
    try {
      // Temporairement désactivé pour résoudre les problèmes de build
      // await StripePlatform.instance.initialise(
      //   publishableKey: publishableKey,
      // );
      
      if (kDebugMode) {
        print('✅ Stripe temporairement désactivé');
        print('📱 Plateforme: ${kIsWeb ? 'Web' : 'Mobile'}');
        print('🔑 Mode: ${_isTestMode ? 'Test' : 'Production'}');
      }
    } catch (e) {
      if (kDebugMode) {
        print('❌ Erreur lors de l\'initialisation de Stripe: $e');
      }
      rethrow;
    }
  }
  
  // Vérifier si Stripe est disponible sur la plateforme
  static bool get isAvailable {
    // Temporairement désactivé
    return false;
  }
  
  // Obtenir les paramètres de paiement selon la plateforme
  static dynamic getPaymentMethodParams({
    required String cardNumber,
    required String expiryMonth,
    required String expiryYear,
    required String cvc,
    required String name,
    required String email,
  }) {
    // Temporairement désactivé
    return null;
  }
  
  // Obtenir les options de paiement selon la plateforme
  static String getPaymentOptions() {
    return 'Temporairement désactivé';
  }
}
