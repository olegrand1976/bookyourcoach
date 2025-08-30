// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appTitle => 'BookYourCoach';

  @override
  String get commonContinue => 'Continue';

  @override
  String get commonCancel => 'Cancel';

  @override
  String get homeWelcome => 'Welcome to BookYourCoach Mobile';

  @override
  String get homeNoRole => 'No role detected, sign in or complete your profile.';

  @override
  String get loginTitle => 'Sign in';

  @override
  String get loginEmail => 'Email';

  @override
  String get loginPassword => 'Password';

  @override
  String get loginSubmit => 'Sign in';

  @override
  String get loginRegister => 'Create account';

  @override
  String get loginForgot => 'Forgot password?';

  @override
  String get registerTitle => 'Create an account';

  @override
  String get registerName => 'Full name';

  @override
  String get registerSubmit => 'Register';

  @override
  String get forgotTitle => 'Forgot password';

  @override
  String get forgotSubmit => 'Send';

  @override
  String get roleTitle => 'Role selection';

  @override
  String get roleChoose => 'Choose your role:';

  @override
  String get roleStudent => 'Student';

  @override
  String get roleTeacher => 'Teacher';

  @override
  String get homeStudentProfile => 'Student Profile';

  @override
  String get homeStudentBookings => 'My Bookings';

  @override
  String get homeTeacherProfile => 'Teacher Profile';

  @override
  String get homeTeacherLessons => 'My Lessons';

  @override
  String get langTitle => 'Language';

  @override
  String get langFrench => 'French';

  @override
  String get langEnglish => 'English';
}
