import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:bookyourcoach_mobile/state/app_state.dart';
import 'package:bookyourcoach_mobile/screens/home_screen.dart';
import 'package:bookyourcoach_mobile/models/user.dart';

void main() {
  testWidgets('Admin button visible for admin users', (tester) async {
    final app = AppState();
    app.setMe(const UserProfile(id: '1', email: 'admin@x', name: 'Admin', isTeacher: false, isStudent: false, isAdmin: true));
    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: app,
        child: const MaterialApp(home: HomeScreen()),
      ),
    );
    expect(find.text('Administration'), findsOneWidget);
  });
}

