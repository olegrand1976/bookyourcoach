import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:flutter/material.dart';
import 'package:bookyourcoach_mobile/main.dart' as app;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('Smoke full flow scaffolding', (tester) async {
    app.main();
    await tester.pumpAndSettle();

    // Splash -> Login
    expect(find.textContaining('Connexion').hitTestable(), findsOneWidget);
  });
}

