# 📚 Documentation BookYourCoach

Bienvenue dans la documentation complète de BookYourCoach, la plateforme de réservation de cours de sports.

## 🎯 Vue d'ensemble

BookYourCoach est une application web complète permettant aux clubs sportifs de gérer leurs cours, enseignants et étudiants. L'application comprend :

- **Frontend** : Interface utilisateur moderne avec Nuxt.js 3
- **Backend** : API REST avec Laravel 11
- **Base de données** : MySQL pour les données relationnelles
- **Graph Database** : Neo4j pour les relations complexes
- **Mobile** : Application Flutter pour iOS et Android

## 📖 Documentation par catégorie

### 🚀 Démarrage rapide
- **[README principal](../README.md)** - Guide de démarrage rapide
- **[Scripts](../scripts/README.md)** - Scripts utilitaires et tests

### 🔧 Configuration et déploiement
- **[Déploiement production](PRODUCTION_DEPLOYMENT.md)** - Guide de déploiement en production
- **[Configuration Sanctum](PRODUCTION_SANCTUM_CONFIG.md)** - Configuration d'authentification
- **[Template environnement](PRODUCTION_ENV_TEMPLATE.md)** - Variables d'environnement
- **[GitHub Actions](GITHUB_ACTIONS_CONFIG.md)** - Configuration CI/CD

### 🔐 Authentification et sécurité
- **[Solution d'authentification](AUTH_SOLUTION.md)** - Architecture d'authentification complète
- **[Configuration CORS](../config/cors.php)** - Configuration Cross-Origin Resource Sharing

### 🔗 Intégrations
- **[Intégration Google Calendar](GOOGLE_CALENDAR_INTEGRATION.md)** - Synchronisation calendrier
- **[Intégration calendrier étudiant](student-calendar-integration.md)** - Fonctionnalités calendrier

### 📱 Applications mobiles
- **[Démarrage élève](../mobile/DEMARRAGE-ELEVE.md)** - Guide utilisateur mobile pour les élèves
- **[Démarrage enseignant](../mobile/DEMARRAGE-ENSEIGNANT.md)** - Guide utilisateur mobile pour les enseignants
- **[Fonctionnalités élève](../mobile/FONCTIONNALITES-ELEVE.md)** - Fonctionnalités disponibles pour les élèves
- **[Fonctionnalités enseignant](../mobile/FONCTIONNALITES-ENSEIGNANT.md)** - Fonctionnalités disponibles pour les enseignants

### 🏗️ Architecture technique
- **[Documentation technique](TECHNICAL_DOCUMENTATION.md)** - Architecture détaillée du système

## 🛠️ Outils de développement

### Scripts utilitaires
```bash
# Tests complets
./scripts/test-all.sh

# Maintenance Docker
./scripts/docker-maintenance.sh start

# Déploiement
./scripts/deploy.sh local
```

### URLs de développement
- **Frontend** : http://localhost:3000
- **Backend** : http://localhost:8080
- **phpMyAdmin** : http://localhost:8082
- **Neo4j** : http://localhost:7474

## 🏃‍♂️ Démarrage rapide

1. **Cloner le projet**
   ```bash
   git clone <repository-url>
   cd bookyourcoach
   ```

2. **Démarrer avec Docker**
   ```bash
   ./scripts/docker-maintenance.sh start
   ```

3. **Tester l'installation**
   ```bash
   ./scripts/test-all.sh
   ```

4. **Accéder à l'application**
   - Frontend : http://localhost:3000
   - Backend : http://localhost:8080

## 📞 Support

Pour toute question ou problème :
- 📧 Email : o.legrand@ll-it-sc.be
- 📞 Téléphone : +32 478.02.33.77
- 🏠 Localisation : Waudrez, Belgique

## 📄 Licence

© 2025 BookYourCoach. Tous droits réservés.

---

*Dernière mise à jour : Septembre 2025*
