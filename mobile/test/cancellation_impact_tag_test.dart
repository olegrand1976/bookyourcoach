import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:activibe_mobile/models/booking.dart';
import 'package:activibe_mobile/widgets/cancellation_impact_tag.dart';

Booking _booking(Map<String, dynamic> extra) => Booking.fromJson({
      'id': 1,
      'student_id': 2,
      'lesson_id': 3,
      'status': 'cancelled',
      'created_at': '2026-10-01T10:00:00Z',
      'updated_at': '2026-10-01T10:00:00Z',
      ...extra,
    });

Future<void> _pump(WidgetTester tester, Booking booking) => tester.pumpWidget(
      MaterialApp(home: Scaffold(body: CancellationImpactTag(booking: booking))),
    );

void main() {
  testWidgets('déduit → rouge', (tester) async {
    await _pump(tester, _booking({'cancellation_count_in_subscription': true}));
    expect(find.text('✗ Déduit de l\'abonnement'), findsOneWidget);
  });

  testWidgets('non déduit → vert (y compris 0 renvoyé par MySQL)', (tester) async {
    await _pump(tester, _booking({'cancellation_count_in_subscription': 0}));
    expect(find.text('✓ Non déduit de l\'abonnement'), findsOneWidget);
  });

  testWidgets('médical sans certificat accepté → en attente', (tester) async {
    await _pump(tester, _booking({
      'cancellation_reason': 'medical',
      'cancellation_certificate_status': 'pending',
      'cancellation_count_in_subscription': true,
    }));
    expect(find.text('⏳ En attente de validation du certificat'), findsOneWidget);
  });

  testWidgets('cours non annulé → rien', (tester) async {
    await _pump(tester, _booking({'status': 'confirmed'}));
    expect(find.byType(Text), findsNothing);
  });
}
