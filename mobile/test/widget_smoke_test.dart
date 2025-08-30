import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:bookyourcoach_mobile/state/app_state.dart';
import 'package:bookyourcoach_mobile/screens/login_screen.dart';
import 'package:bookyourcoach_mobile/screens/home_screen.dart';

void main() {
  testWidgets('Login screen renders', (tester) async {
    await tester.pumpWidget(
      ChangeNotifierProvider(
        create: (_) => AppState(),
        child: const MaterialApp(home: LoginScreen()),
      ),
    );
    expect(find.text('Connexion'), findsOneWidget);
    expect(find.byType(ElevatedButton), findsOneWidget);
  });

  testWidgets('Home screen shows buttons when roles present', (tester) async {
    final app = AppState();
    app.setMe(null);
    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: app,
        child: const MaterialApp(home: HomeScreen()),
      ),
    );
    expect(find.text('Accueil'), findsOneWidget);
  });
}