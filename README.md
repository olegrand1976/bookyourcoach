# BookYourCoach 🏇

BookYourCoach est une plateforme de coaching équestre moderne et multilingue qui permet aux utilisateurs de trouver et de réserver des sessions avec des instructeurs professionnels certifiés.

## ✨ Fonctionnalités

-   **🌍 Support Multilingue**: Interface disponible en 15 langues (Français, Anglais, Néerlandais, Allemand, Italien, Espagnol, Portugais, Hongrois, Polonais, Chinois, Japonais, Suédois, Norvégien, Finlandais, Danois)
-   **👥 Système Multi-Rôles**: Interfaces distinctes et capacités croisées pour administrateurs, enseignants et étudiants
-   **🔐 Authentification Avancée**: Construit avec Laravel Sanctum avec gestion des capacités utilisateur
-   **📱 Interface Responsive**: Design moderne adaptatif avec thème équestre
-   **⚙️ Configuration Dynamique**: Paramètres système configurables à la volée par les administrateurs
-   **🐳 Déploiement Docker**: Environnement de développement et production containerisé

## 🚀 Stack Technique & Architecture

Architecture frontend/backend découplée avec support multilingue complet.

-   **Backend**:

    -   **Framework**: Laravel 11 (PHP 8.3)
    -   **API**: API JSON RESTful avec capacités utilisateur
    -   **Authentification**: Laravel Sanctum avec système multi-rôles
    -   **Base de données**: SQLite (dev), MySQL (prod)
    -   **Tests**: PHPUnit (127 tests passants)

-   **Frontend**:

    -   **Framework**: Nuxt 3.17.7 (Vue.js 3 + TypeScript)
    -   **Internationalisation**: @nuxtjs/i18n (15 langues)
    -   **Styling**: Tailwind CSS avec thème équestre personnalisé
    -   **Gestion d'état**: Pinia avec stores authentification
    -   **Composants**: Système de composants Vue réutilisables

-   **Infrastructure**:
    -   **Conteneurisation**: Docker Compose (5 services)
    -   **Serveur Web**: Nginx
    -   **Cache**: Redis
    -   **Monitoring**: Logs structurés

## 📦 Installation & Démarrage Rapide

1.  **Cloner le dépôt**:

    ```bash
    git clone https://github.com/olegrand1976/bookyourcoach.git
    cd bookyourcoach
    ```

2.  **Démarrage automatique**:

    ```bash
    # Démarrage complet avec tous les services
    ./start-full-stack.sh

    # Ou démarrage manuel
    docker-compose up -d
    ```

3.  **Vérification du système**:

    ```bash
    # Test complet automatisé
    ./test_complete_system.sh
    ```

4.  **Accès aux services**:
    -   **🎨 Frontend**: [http://localhost:3000](http://localhost:3000)
    -   **🔧 API Backend**: [http://localhost:8081](http://localhost:8081)
    -   **🗄️ Base de données**: Port 3307
    -   **📊 Redis**: Port 6380

## 🧪 Tests et Validation

-   **Tests Backend** (127 tests):

    ```bash
    docker exec bookyourcoach_app php artisan test
    ```

-   **Tests Frontend**:

    ```bash
    docker exec bookyourcoach_frontend npm run test
    ```

-   **Test système complet**:
    ```bash
    ./test_complete_system.sh
    ```

## 👤 Comptes de Test

-   **🔑 Administrateur**:

    -   Email: `admin@bookyourcoach.com`
    -   Mot de passe: `password123`
    -   Capacités: Admin + Enseignant + Étudiant

-   **👨‍🏫 Enseignant**:
    -   Email: `user@example.com`
    -   Mot de passe: `password`
