# BookYourCoach Mobile (Flutter)

Ce dossier contiendra l'application mobile Flutter connectée à l'API web.

Pré-requis locaux:
- Flutter SDK 3.19+
- Dart 3.3+
- Android Studio / Xcode (optionnel pour build mobile)

Démarrage:
1. Installer Flutter: https://docs.flutter.dev/get-started/install
2. Depuis ce répertoire:
```
flutter create .
flutter pub add dio shared_preferences flutter_localizations intl
```
3. Configurer l'API dans `lib/config.dart` (créé ci-dessous)
4. Lancer:
```
flutter run -d chrome
# ou
flutter run -d emulator-5554
```

Arborescence cible:
- lib/
  - main.dart
  - config.dart
  - routes.dart
  - services/
    - api_client.dart
    - auth_service.dart
  - screens/
    - login_screen.dart
    - home_screen.dart
  - l10n/
    - intl_en.arb
    - intl_fr.arb

Note: Flutter n'est pas installé dans l'environnement CI actuel. Utilisez vos outils locaux pour créer/build l'app; commitez ensuite les sources Flutter dans ce dossier.