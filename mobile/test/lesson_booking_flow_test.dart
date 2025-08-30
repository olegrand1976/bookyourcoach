import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:bookyourcoach_mobile/state/app_state.dart';
import 'package:bookyourcoach_mobile/models/user.dart';
import 'package:bookyourcoach_mobile/screens/available_lessons_screen.dart';

void main() {
  testWidgets('Shows loading and renders list container', (tester) async {
    final app = AppState();
    app.setAuthenticated(true);
    app.setMe(const UserProfile(id: '1', email: 'x', name: 'y', isTeacher: false, isStudent: true));
    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: app,
        child: const MaterialApp(home: AvailableLessonsScreen()),
      ),
    );
    expect(find.byType(CircularProgressIndicator), findsOneWidget);
  });
}

