# BookYourCoach

BookYourCoach est une plateforme de coaching moderne et complète qui permet aux utilisateurs de trouver et de réserver des sessions avec des coachs professionnels.

## ✨ Fonctionnalités

-   **Rôles Utilisateurs**: Interfaces distinctes pour les utilisateurs réguliers, les coachs et les administrateurs.
-   **Configuration Système Dynamique**: Les administrateurs peuvent configurer les paramètres du système tels que le nom de la plateforme et les coordonnées à la volée.
-   **Authentification Sécurisée**: Construit avec Laravel Sanctum pour une authentification utilisateur robuste et sécurisée.
-   **Stack Technologique Moderne**: Un frontend et un backend découplés pour une meilleure évolutivité et maintenabilité.

## 🚀 Stack Technique & Architecture

Le projet est construit avec une architecture frontend et backend séparée, conteneurisée avec Docker.

-   **Backend**:

    -   **Framework**: Laravel 11 (PHP 8.3)
    -   **API**: API JSON RESTful
    -   **Authentification**: Laravel Sanctum
    -   **Base de données**: SQLite pour le développement, MySQL pour la production.
    -   **Tests**: PHPUnit

-   **Frontend**:

    -   **Framework**: Nuxt 3 (Vue.js 3)
    -   **Langage**: TypeScript
    -   **Styling**: Tailwind CSS
    -   **Gestion d'état**: Pinia
    -   **Tests**: Vitest & Playwright

-   **Environnement**:
    -   **Conteneurisation**: Docker Compose
    -   **Serveur Web**: Nginx

## 📦 Installation & Configuration

1.  **Cloner le dépôt**:

    ```bash
    git clone https://github.com/olegrand1976/bookyourcoach.git
    cd bookyourcoach/copilot
    ```

2.  **Construire et démarrer les conteneurs Docker**:
    Cette commande construira les images et démarrera les services frontend, backend et base de données.

    ```bash
    docker-compose up --build -d
    ```

3.  **Installer les dépendances**:

    -   **Backend (Laravel)**:
        ```bash
        docker-compose exec app composer install
        ```
    -   **Frontend (Nuxt)**:
        ```bash
        docker-compose exec frontend npm install
        ```

4.  **Configurer Laravel**:

    -   Copier le fichier d'environnement :
        ```bash
        docker-compose exec app cp .env.example .env
        ```
    -   Générer la clé d'application :
        ```bash
        docker-compose exec app php artisan key:generate
        ```
    -   Exécuter les migrations de la base de données :
        ```bash
        docker-compose exec app php artisan migrate --seed
        ```

5.  **Accéder à l'application**:
    -   **Frontend**: [http://localhost:3000](http://localhost:3000)
    -   **API Backend**: [http://localhost:8081](http://localhost:8081)

## 🧪 Exécution des Tests

-   **Tests Backend**:

    ```bash
    docker-compose exec app php artisan test
    ```

-   **Tests Frontend**:
    ```bash
    docker-compose exec frontend npm run test
    ```

## 👤 Identifiants par Défaut

-   **Admin**:
    -   Email: `admin@example.com`
    -   Mot de passe: `password`
-   **Utilisateur**:
    -   Email: `user@example.com`
    -   Mot de passe: `password`
