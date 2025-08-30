// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for French (`fr`).
class AppLocalizationsFr extends AppLocalizations {
  AppLocalizationsFr([String locale = 'fr']) : super(locale);

  @override
  String get appTitle => 'BookYourCoach';

  @override
  String get commonContinue => 'Continuer';

  @override
  String get commonCancel => 'Annuler';

  @override
  String get homeWelcome => 'Bienvenue sur BookYourCoach Mobile';

  @override
  String get homeNoRole => 'Aucun rôle détecté, connectez-vous ou complétez votre profil.';

  @override
  String get loginTitle => 'Connexion';

  @override
  String get loginEmail => 'Email';

  @override
  String get loginPassword => 'Mot de passe';

  @override
  String get loginSubmit => 'Se connecter';

  @override
  String get loginRegister => 'S\'inscrire';

  @override
  String get loginForgot => 'Mot de passe oublié ?';

  @override
  String get registerTitle => 'Créer un compte';

  @override
  String get registerName => 'Nom complet';

  @override
  String get registerSubmit => 'S\'inscrire';

  @override
  String get forgotTitle => 'Mot de passe oublié';

  @override
  String get forgotSubmit => 'Envoyer';

  @override
  String get roleTitle => 'Sélection du rôle';

  @override
  String get roleChoose => 'Choisissez votre rôle:';

  @override
  String get roleStudent => 'Élève';

  @override
  String get roleTeacher => 'Enseignant';

  @override
  String get homeStudentProfile => 'Profil Élève';

  @override
  String get homeStudentBookings => 'Mes Réservations';

  @override
  String get homeTeacherProfile => 'Profil Enseignant';

  @override
  String get homeTeacherLessons => 'Mes Leçons';

  @override
  String get langTitle => 'Langue';

  @override
  String get langFrench => 'Français';

  @override
  String get langEnglish => 'English';

  @override
  String get adminTitle => 'Administration';

  @override
  String get adminUsers => 'Utilisateurs';

  @override
  String get adminApprovals => 'Approbations enseignants';

  @override
  String get adminLessons => 'Leçons (modération)';

  @override
  String get adminDisciplines => 'Disciplines';
}
