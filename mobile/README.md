# BookYourCoach Mobile App 🐎

Application mobile Flutter pour la plateforme de coaching équestre BookYourCoach.

## 🚀 Fonctionnalités

- **🔐 Authentification sécurisée** avec Laravel Sanctum
- **👥 Système multi-rôles** (Admin, Enseignant, Étudiant)
- **🎨 Interface moderne** avec design équestre
- **📱 Responsive** sur tous les appareils
- **🌐 Support web** et mobile
- **🔒 Stockage sécurisé** des tokens

## 📋 Prérequis

- Flutter SDK (version 3.24.5+)
- Dart SDK (version 3.5.4+)
- API Laravel BookYourCoach en cours d'exécution
- Android Studio / VS Code (recommandé)

## 🛠️ Installation

### 1. Installer Flutter

```bash
# Télécharger Flutter
curl -O https://storage.googleapis.com/flutter_infra_release/releases/stable/linux/flutter_linux_3.24.5-stable.tar.xz

# Extraire
tar xf flutter_linux_3.24.5-stable.tar.xz

# Ajouter au PATH
echo 'export PATH="$PATH:$HOME/flutter/bin"' >> ~/.bashrc
source ~/.bashrc

# Vérifier l'installation
flutter doctor
```

### 2. Configurer l'API

Assurez-vous que l'API Laravel est en cours d'exécution :

```bash
# Depuis la racine du projet
./start-full-stack.sh
```

### 3. Installer les dépendances

```bash
cd mobile
flutter pub get
```

## 🚀 Lancement

### Script automatique (recommandé)

```bash
./start_mobile.sh
```

### Lancement manuel

```bash
# Web (Chrome)
flutter run -d chrome

# Android
flutter run -d android

# Linux
flutter run -d linux

# Mode debug (détection automatique)
flutter run
```

## 🧪 Tests

```bash
# Tests automatiques
./test_mobile.sh

# Tests Flutter uniquement
flutter test

# Tests avec couverture
flutter test --coverage
```

## 📱 Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| **Admin** | `admin@bookyourcoach.com` | `password123` |
| **Enseignant** | `sophie.martin@bookyourcoach.com` | `password123` |
| **Étudiant** | `alice.durand@email.com` | `password123` |

## 🏗️ Architecture

```
lib/
├── models/          # Modèles de données
│   └── user.dart    # Modèle utilisateur
├── services/        # Services API
│   └── auth_service.dart
├── providers/       # Gestion d'état (Riverpod)
│   └── auth_provider.dart
├── screens/         # Écrans de l'application
│   ├── login_screen.dart
│   └── home_screen.dart
├── widgets/         # Widgets personnalisés
│   ├── custom_button.dart
│   └── custom_text_field.dart
├── utils/           # Utilitaires
│   └── api_config.dart
└── main.dart        # Point d'entrée
```

## 🎨 Design System

### Couleurs
- **Primaire** : `#1E3A8A` (Bleu foncé)
- **Secondaire** : `#3B82F6` (Bleu moyen)
- **Accent** : `#60A5FA` (Bleu clair)
- **Succès** : `#059669` (Vert)
- **Erreur** : `#DC2626` (Rouge)
- **Avertissement** : `#D97706` (Orange)

### Typographie
- **Famille** : Inter
- **Tailles** : 12px, 14px, 16px, 20px, 24px, 28px
- **Poids** : 400 (Regular), 500 (Medium), 600 (SemiBold), 700 (Bold)

## 🔧 Configuration

### API Configuration

Modifiez `lib/utils/api_config.dart` pour changer l'URL de l'API :

```dart
static const String baseUrl = 'http://localhost:8081/api';
```

### Variables d'environnement

Créez un fichier `.env` pour les variables sensibles :

```env
API_URL=http://localhost:8081/api
DEBUG_MODE=true
```

## 📦 Build

### Android APK

```bash
flutter build apk --release
```

### Web

```bash
flutter build web --release
```

### Linux

```bash
flutter build linux --release
```

## 🐛 Debug

### Mode debug

```bash
flutter run --debug
```

### Hot reload

Appuyez sur `r` dans le terminal pour recharger l'application.

### Hot restart

Appuyez sur `R` dans le terminal pour redémarrer l'application.

## 📊 Performance

### Optimisations

- **Lazy loading** des images
- **Caching** des données API
- **Compression** des assets
- **Minification** du code

### Monitoring

```bash
# Profilage
flutter run --profile

# Analyse des performances
flutter run --trace-startup
```

## 🔒 Sécurité

- **Tokens JWT** stockés de manière sécurisée
- **Validation** des entrées utilisateur
- **HTTPS** pour les communications API
- **Chiffrement** des données sensibles

## 🤝 Contribution

1. Fork le projet
2. Créez une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 📞 Support

Pour toute question ou problème :

- **Issues** : Utilisez le système d'issues GitHub
- **Documentation** : Consultez les commentaires dans le code
- **Tests** : Exécutez `./test_mobile.sh` pour diagnostiquer

---

**BookYourCoach Mobile** - Votre plateforme équestre mobile 🐎📱
