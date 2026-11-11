import 'package:flutter/material.dart';

import '../models/booking.dart';

/// Cours annulé : tag « Déduit » (rouge) / « Non déduit » (vert) de l'abonnement,
/// ou « En attente » (ambre) tant qu'un certificat médical n'est pas accepté.
/// Mêmes libellés et couleurs que le web (frontend/composables/useCancellationLabels.ts).
class CancellationImpactTag extends StatelessWidget {
  const CancellationImpactTag({super.key, required this.booking});

  final Booking booking;

  static const _deducted = ('✗ Déduit de l\'abonnement', Color(0xFFDC2626), Colors.white);
  static const _notDeducted = ('✓ Non déduit de l\'abonnement', Color(0xFF059669), Colors.white);
  static const _pending = ('⏳ En attente de validation du certificat', Color(0xFFFCD34D), Color(0xFF451A03));

  @override
  Widget build(BuildContext context) {
    if (!booking.isCancelled) return const SizedBox.shrink();

    final (label, background, foreground) = switch (booking.cancellationImpact) {
      CancellationImpact.pending => _pending,
      CancellationImpact.deducted => _deducted,
      CancellationImpact.notDeducted => _notDeducted,
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        label,
        style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: foreground),
      ),
    );
  }
}
