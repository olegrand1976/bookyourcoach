import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../config/stripe_config.dart';

class StripeTestScreen extends ConsumerStatefulWidget {
  const StripeTestScreen({super.key});

  @override
  ConsumerState<StripeTestScreen> createState() => _StripeTestScreenState();
}

class _StripeTestScreenState extends ConsumerState<StripeTestScreen> {
  bool _isInitialized = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _testStripeConfiguration();
  }

  Future<void> _testStripeConfiguration() async {
    try {
      await StripeConfig.initialize();
      setState(() {
        _isInitialized = true;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Test Configuration Stripe'),
        backgroundColor: const Color(0xFF1E3A8A),
        foregroundColor: Colors.white,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Test de Configuration Stripe',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 24),

            // Informations de plateforme
            _buildPlatformInfo(),
            const SizedBox(height: 16),

            // Statut de Stripe
            _buildStripeStatus(),
            const SizedBox(height: 16),

            // Configuration
            _buildConfigurationInfo(),
            const SizedBox(height: 16),

            // Bouton de test
            _buildTestButton(),
          ],
        ),
      ),
    );
  }

  Widget _buildPlatformInfo() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Informations de Plateforme',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text('Plateforme: ${kIsWeb ? 'Web' : 'Mobile'}'),
            Text('Mode Debug: ${kDebugMode ? 'Oui' : 'Non'}'),
            Text('Disponible: ${StripeConfig.isAvailable ? 'Oui' : 'Non'}'),
          ],
        ),
      ),
    );
  }

  Widget _buildStripeStatus() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Statut Stripe',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(
                  _isInitialized ? Icons.check_circle : Icons.error,
                  color: _isInitialized ? Colors.green : Colors.red,
                ),
                const SizedBox(width: 8),
                Text(
                  _isInitialized ? 'Initialisé avec succès' : 'Non initialisé',
                  style: TextStyle(
                    color: _isInitialized ? Colors.green : Colors.red,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            if (_errorMessage != null) ...[
              const SizedBox(height: 8),
              Text(
                'Erreur: $_errorMessage',
                style: const TextStyle(color: Colors.red),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildConfigurationInfo() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Configuration',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text('Mode: ${StripeConfig.isTestMode ? 'Test' : 'Production'}'),
            Text('Clé: ${StripeConfig.publishableKey.substring(0, 20)}...'),
            Text('Options: ${StripeConfig.getPaymentOptions()}'),
          ],
        ),
      ),
    );
  }

  Widget _buildTestButton() {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton(
        onPressed: _testStripeConfiguration,
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFF1E3A8A),
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 16),
        ),
        child: const Text('Tester à nouveau'),
      ),
    );
  }
}
