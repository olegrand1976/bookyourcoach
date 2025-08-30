import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:bookyourcoach_mobile/state/app_state.dart';
import 'package:bookyourcoach_mobile/screens/home_screen.dart';
import 'package:bookyourcoach_mobile/models/user.dart';

void main() {
  testWidgets('Home shows role-based buttons', (tester) async {
    final app = AppState();
    app.setMe(const UserProfile(id: '1', email: 'x', name: 'y', isTeacher: true, isStudent: true));
    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: app,
        child: const MaterialApp(home: HomeScreen()),
      ),
    );
    expect(find.text('Profil Élève'), findsOneWidget);
    expect(find.text('Mes Réservations'), findsOneWidget);
    expect(find.text('Profil Enseignant'), findsOneWidget);
    expect(find.text('Mes Leçons'), findsOneWidget);
  });
}