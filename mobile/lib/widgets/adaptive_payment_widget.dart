import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
// import 'package:stripe_platform_interface/stripe_platform_interface.dart';
import '../models/lesson.dart';
import '../providers/payment_provider.dart';
import '../providers/auth_provider.dart';
import '../config/stripe_config.dart';

class AdaptivePaymentWidget extends ConsumerStatefulWidget {
  final Lesson lesson;
  final VoidCallback? onPaymentSuccess;
  final VoidCallback? onPaymentError;

  const AdaptivePaymentWidget({
    super.key,
    required this.lesson,
    this.onPaymentSuccess,
    this.onPaymentError,
  });

  @override
  ConsumerState<AdaptivePaymentWidget> createState() => _AdaptivePaymentWidgetState();
}

class _AdaptivePaymentWidgetState extends ConsumerState<AdaptivePaymentWidget> {
  final _formKey = GlobalKey<FormState>();
  final _cardNumberController = TextEditingController();
  final _expiryController = TextEditingController();
  final _cvcController = TextEditingController();
  final _nameController = TextEditingController();
  
  bool _isProcessing = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _nameController.text = ref.read(authProvider).user?.name ?? '';
    
    // Initialiser Stripe
    StripeConfig.initialize();
  }

  @override
  void dispose() {
    _cardNumberController.dispose();
    _expiryController.dispose();
    _cvcController.dispose();
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _processPayment() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isProcessing = true;
      _errorMessage = null;
    });

    try {
      // Créer l'intention de paiement
      await ref.read(paymentProvider.notifier).createPaymentIntent(
        lessonId: widget.lesson.id,
        amount: widget.lesson.price,
        currency: 'eur',
        customerEmail: ref.read(authProvider).user?.email,
      );

      final paymentState = ref.read(paymentProvider);
      final paymentIntent = paymentState.currentPaymentIntent;

      if (paymentIntent == null) {
        throw Exception('Impossible de créer l\'intention de paiement');
      }

      // Temporairement désactivé pour résoudre les problèmes de build
      // final paymentMethod = await StripePlatform.instance.createPaymentMethod(
      //   StripeConfig.getPaymentMethodParams(
      //     cardNumber: _cardNumberController.text,
      //     expiryMonth: _expiryController.text.split('/')[0],
      //     expiryYear: '20${_expiryController.text.split('/')[1]}',
      //     cvc: _cvcController.text,
      //     name: _nameController.text,
      //     email: ref.read(authProvider).user?.email ?? '',
      //   ),
      // );

      // Confirmer le paiement
      final success = await ref.read(paymentProvider.notifier).confirmPayment(
        paymentIntentId: paymentIntent.id,
        paymentMethodId: paymentMethod.id,
      );

      if (success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Paiement effectué avec succès !'),
              backgroundColor: Colors.green,
            ),
          );
          widget.onPaymentSuccess?.call();
        }
      } else {
        throw Exception('Le paiement a échoué');
      }
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
      });
      widget.onPaymentError?.call();
    } finally {
      setState(() {
        _isProcessing = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Titre adaptatif selon la plateforme
              Text(
                kIsWeb ? 'Paiement en ligne' : 'Paiement',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 16),

              // Résumé de la leçon
              _buildLessonSummary(),
              const SizedBox(height: 16),

              // Formulaire de paiement adaptatif
              if (kIsWeb) _buildWebPaymentForm() else _buildMobilePaymentForm(),
              
              const SizedBox(height: 16),

              // Message d'erreur
              if (_errorMessage != null) _buildErrorMessage(),
              
              const SizedBox(height: 16),

              // Bouton de paiement adaptatif
              _buildPaymentButton(),
              
              const SizedBox(height: 16),

              // Informations de sécurité adaptatives
              _buildSecurityInfo(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLessonSummary() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Expanded(
            child: Text(
              widget.lesson.title,
              style: const TextStyle(fontSize: 16),
            ),
          ),
          Text(
            '${widget.lesson.price.toStringAsFixed(2)} €',
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildWebPaymentForm() {
    return Column(
      children: [
        // Nom du titulaire
        TextFormField(
          controller: _nameController,
          decoration: const InputDecoration(
            labelText: 'Nom du titulaire de la carte',
            border: OutlineInputBorder(),
          ),
          validator: (value) {
            if (value == null || value.isEmpty) {
              return 'Veuillez saisir le nom du titulaire';
            }
            return null;
          },
        ),
        const SizedBox(height: 16),

        // Numéro de carte
        TextFormField(
          controller: _cardNumberController,
          decoration: const InputDecoration(
            labelText: 'Numéro de carte',
            border: OutlineInputBorder(),
            prefixIcon: Icon(Icons.credit_card),
          ),
          keyboardType: TextInputType.number,
          validator: (value) {
            if (value == null || value.isEmpty) {
              return 'Veuillez saisir le numéro de carte';
            }
            if (value.length < 13) {
              return 'Numéro de carte invalide';
            }
            return null;
          },
        ),
        const SizedBox(height: 16),

        // Date d'expiration et CVC
        Row(
          children: [
            Expanded(
              child: TextFormField(
                controller: _expiryController,
                decoration: const InputDecoration(
                  labelText: 'MM/AA',
                  border: OutlineInputBorder(),
                  hintText: '12/25',
                ),
                keyboardType: TextInputType.number,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Veuillez saisir la date d\'expiration';
                  }
                  if (!RegExp(r'^\d{2}/\d{2}$').hasMatch(value)) {
                    return 'Format: MM/AA';
                  }
                  return null;
                },
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: TextFormField(
                controller: _cvcController,
                decoration: const InputDecoration(
                  labelText: 'CVC',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.security),
                ),
                keyboardType: TextInputType.number,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Veuillez saisir le CVC';
                  }
                  if (value.length < 3) {
                    return 'CVC invalide';
                  }
                  return null;
                },
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildMobilePaymentForm() {
    return Column(
      children: [
        // Nom du titulaire
        TextFormField(
          controller: _nameController,
          decoration: const InputDecoration(
            labelText: 'Nom du titulaire',
            border: OutlineInputBorder(),
          ),
          validator: (value) {
            if (value == null || value.isEmpty) {
              return 'Veuillez saisir le nom du titulaire';
            }
            return null;
          },
        ),
        const SizedBox(height: 16),

        // Numéro de carte
        TextFormField(
          controller: _cardNumberController,
          decoration: const InputDecoration(
            labelText: 'Numéro de carte',
            border: OutlineInputBorder(),
            prefixIcon: Icon(Icons.credit_card),
          ),
          keyboardType: TextInputType.number,
          validator: (value) {
            if (value == null || value.isEmpty) {
              return 'Veuillez saisir le numéro de carte';
            }
            if (value.length < 13) {
              return 'Numéro de carte invalide';
            }
            return null;
          },
        ),
        const SizedBox(height: 16),

        // Date d'expiration et CVC
        Row(
          children: [
            Expanded(
              child: TextFormField(
                controller: _expiryController,
                decoration: const InputDecoration(
                  labelText: 'MM/AA',
                  border: OutlineInputBorder(),
                  hintText: '12/25',
                ),
                keyboardType: TextInputType.number,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Veuillez saisir la date d\'expiration';
                  }
                  if (!RegExp(r'^\d{2}/\d{2}$').hasMatch(value)) {
                    return 'Format: MM/AA';
                  }
                  return null;
                },
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: TextFormField(
                controller: _cvcController,
                decoration: const InputDecoration(
                  labelText: 'CVC',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.security),
                ),
                keyboardType: TextInputType.number,
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Veuillez saisir le CVC';
                  }
                  if (value.length < 3) {
                    return 'CVC invalide';
                  }
                  return null;
                },
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildErrorMessage() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.red.shade50,
        border: Border.all(color: Colors.red.shade200),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          Icon(Icons.error, color: Colors.red.shade600),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              _errorMessage!,
              style: TextStyle(color: Colors.red.shade600),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPaymentButton() {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton(
        onPressed: _isProcessing ? null : _processPayment,
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFF1E3A8A),
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
        ),
        child: _isProcessing
            ? const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  ),
                  SizedBox(width: 12),
                  Text('Traitement en cours...'),
                ],
              )
            : Text(
                'Payer ${widget.lesson.price.toStringAsFixed(2)} €',
                style: const TextStyle(fontSize: 18),
              ),
      ),
    );
  }

  Widget _buildSecurityInfo() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          Icon(Icons.security, color: Colors.grey.shade600),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              kIsWeb 
                ? 'Vos informations de paiement sont sécurisées par Stripe (Web)'
                : 'Vos informations de paiement sont sécurisées par Stripe (Mobile)',
              style: TextStyle(
                color: Colors.grey.shade600,
                fontSize: 12,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
