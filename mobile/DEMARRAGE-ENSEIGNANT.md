# 🚀 Démarrage Rapide - Fonctionnalités Enseignant

## ⚡ Installation et Lancement Express

### 1️⃣ **Démarrer l'API Laravel**
```bash
# Dans le répertoire racine du projet
./start-full-stack.sh
```

### 2️⃣ **Lancer l'Application Mobile**
```bash
# Dans le répertoire mobile
cd mobile
flutter run -d chrome --web-port 8083
```

### 3️⃣ **Se Connecter**
- **URL** : http://localhost:8083
- **Compte enseignant** : `sophie.martin@activibe.com` / `password123`

## 🎯 Fonctionnalités Disponibles

### 📱 **Tableau de Bord Enseignant**
- ✅ Statistiques rapides (cours, étudiants, revenus)
- ✅ Actions rapides (nouveau cours, disponibilités)
- ✅ Prochains cours à venir
- ✅ Navigation par onglets

### 📚 **Gestion des Cours**
- ✅ Créer un nouveau cours
- ✅ Modifier les détails du cours
- ✅ Changer le statut (planifié → en cours → terminé)
- ✅ Annuler ou supprimer un cours
- ✅ Filtres par statut

### ⏰ **Gestion des Disponibilités**
- ✅ Définir des créneaux horaires
- ✅ Modifier les disponibilités
- ✅ Activer/désactiver des créneaux
- ✅ Vue calendrier hebdomadaire

### 👥 **Gestion des Étudiants**
- ✅ Liste des étudiants
- ✅ Détails des étudiants
- ✅ Historique des cours par étudiant

### 📈 **Statistiques**
- ✅ Métriques de performance
- ✅ Graphiques d'évolution
- ✅ Revenus et heures enseignées

## 🧪 Tests Automatisés

### 🔍 **Test Complet des Fonctionnalités**
```bash
# Dans le répertoire mobile
./test_teacher_features.sh
```

Ce script teste :
- ✅ Authentification enseignant
- ✅ Endpoints API
- ✅ CRUD des cours
- ✅ CRUD des disponibilités
- ✅ Compilation Flutter

### 🔧 **Test Manuel Rapide**
1. **Connexion** : Vérifier l'authentification
2. **Tableau de bord** : Vérifier les statistiques
3. **Création de cours** : Tester le formulaire
4. **Gestion des statuts** : Démarrer/terminer un cours
5. **Disponibilités** : Créer un créneau

## 🔗 URLs d'Accès

| Service | URL | Description |
|---------|-----|-------------|
| **Application Mobile** | http://localhost:8083 | Interface enseignant |
| **API Laravel** | http://localhost:8081 | Backend API |
| **PHPMyAdmin** | http://localhost:8082 | Gestion base de données |
| **DevTools Flutter** | http://127.0.0.1:9100 | Outils de développement |

## 👤 Comptes de Test

| Rôle | Email | Mot de passe | Fonctionnalités |
|------|-------|--------------|-----------------|
| **Enseignant** | `sophie.martin@activibe.com` | `password123` | Toutes les fonctionnalités |
| **Admin** | `admin@activibe.com` | `password123` | Accès complet |
| **Étudiant** | `alice.durand@email.com` | `password123` | Interface étudiant |

## 🎨 Interface Utilisateur

### 📱 **Design Moderne**
- Material Design 3
- Navigation fluide par onglets
- Cartes interactives
- Formulaires intuitifs
- Responsive design

### 🎯 **Expérience Utilisateur**
- Interface intuitive
- Actions rapides
- Feedback visuel
- Gestion d'erreurs
- États de chargement

## 🔧 Configuration Technique

### 📁 **Structure des Fichiers**
```
mobile/lib/
├── models/
│   ├── lesson.dart              # Modèle cours
│   └── availability.dart        # Modèle disponibilités
├── services/
│   └── teacher_service.dart     # Service API enseignant
├── providers/
│   └── teacher_provider.dart    # Gestion d'état
├── screens/
│   ├── teacher_dashboard.dart   # Tableau de bord
│   └── teacher_lessons_screen.dart # Gestion cours
└── widgets/
    ├── custom_button.dart       # Boutons personnalisés
    └── custom_text_field.dart   # Champs texte
```

### 🔌 **Endpoints API**
```
GET    /api/teacher/lessons          # Liste des cours
POST   /api/teacher/lessons          # Créer un cours
PUT    /api/teacher/lessons/{id}     # Modifier un cours
DELETE /api/teacher/lessons/{id}     # Supprimer un cours

GET    /api/teacher/availabilities   # Liste des disponibilités
POST   /api/teacher/availabilities   # Créer une disponibilité
PUT    /api/teacher/availabilities/{id} # Modifier une disponibilité
DELETE /api/teacher/availabilities/{id} # Supprimer une disponibilité

GET    /api/teacher/stats            # Statistiques
GET    /api/teacher/students         # Liste des étudiants
```

## 🚨 Dépannage

### ❌ **Problèmes Courants**

#### **Application ne se lance pas**
```bash
# Vérifier Flutter
flutter doctor

# Réinstaller les dépendances
flutter clean
flutter pub get

# Relancer
flutter run -d chrome --web-port 8083
```

#### **Erreur de connexion API**
```bash
# Vérifier que l'API est démarrée
curl http://localhost:8081/api

# Redémarrer l'API
./start-full-stack.sh
```

#### **Erreur CORS**
```bash
# Vérifier la configuration CORS
./test_cors.sh

# Redémarrer le container Laravel
docker-compose restart app
```

### 🔍 **Logs et Debug**
```bash
# Logs Flutter
flutter logs

# Logs API Laravel
docker-compose logs app

# Logs base de données
docker-compose logs mysql
```

## 📚 Documentation Complète

### 📖 **Guides Détaillés**
- `FONCTIONNALITES-ENSEIGNANT.md` - Guide complet des fonctionnalités
- `RESOLUTION-ENDPOINT.md` - Résolution des problèmes d'endpoints
- `RESOLUTION-CORS.md` - Configuration CORS
- `TROUBLESHOOTING.md` - Guide de dépannage

### 🔗 **Liens Utiles**
- [Flutter Documentation](https://flutter.dev/docs)
- [Riverpod Documentation](https://riverpod.dev/)
- [Material Design](https://material.io/design)

## 🎉 Résumé

L'application mobile activibe offre une solution complète pour les enseignants :

✅ **Interface moderne et intuitive**  
✅ **Gestion complète des cours**  
✅ **Disponibilités flexibles**  
✅ **Statistiques avancées**  
✅ **Architecture robuste**  
✅ **Tests automatisés**  

**Prêt pour la production ! 🚀**

