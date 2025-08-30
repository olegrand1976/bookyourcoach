import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:bookyourcoach_mobile/state/app_state.dart';
import 'package:bookyourcoach_mobile/screens/student_profile_screen.dart';
import 'package:bookyourcoach_mobile/screens/teacher_profile_screen.dart';
import 'package:bookyourcoach_mobile/models/user.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:bookyourcoach_mobile/services/api_client.dart';
import 'mocks.dart';

void main() {
  testWidgets('Student profile renders and has save button', (tester) async {
    SharedPreferences.setMockInitialValues({});
    ApiFactory.setClientOverride(ApiClient(adapter: TestMockAdapter()));
    final app = AppState();
    app.setMe(const UserProfile(id: '1', email: 'x', name: 'y', isTeacher: false, isStudent: true, isAdmin: false));
    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: app,
        child: const MaterialApp(home: StudentProfileScreen()),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('Profil'), findsOneWidget);
    expect(find.textContaining('Niveau'), findsOneWidget);
    expect(find.textContaining('Objectifs'), findsOneWidget);
    expect(find.textContaining('Enregistrer'), findsOneWidget);
  });

  testWidgets('Teacher profile renders and has save button', (tester) async {
    SharedPreferences.setMockInitialValues({});
    ApiFactory.setClientOverride(ApiClient(adapter: TestMockAdapter()));
    final app = AppState();
    app.setMe(const UserProfile(id: '1', email: 'x', name: 'y', isTeacher: true, isStudent: false, isAdmin: false));
    await tester.pumpWidget(
      ChangeNotifierProvider.value(
        value: app,
        child: const MaterialApp(home: TeacherProfileScreen()),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('Profil'), findsOneWidget);
    expect(find.textContaining('Disciplines'), findsOneWidget);
    expect(find.textContaining('Bio'), findsOneWidget);
    expect(find.textContaining('Enregistrer'), findsOneWidget);
  });
}

