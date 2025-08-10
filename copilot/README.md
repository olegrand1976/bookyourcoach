# BookYourCoach - Plateforme de Réservation de Cours

## Description

API REST Laravel pour une plateforme de réservation de cours avec coaches (équestres ou autres). L'application gère trois types d'utilisateurs : Administrateurs, Enseignants et Élèves.

## Fonctionnalités Principales

### Gestion des Utilisateurs
- Authentification multi-rôles (Admin, Teacher, Student)
- Profils utilisateurs avec informations personnelles
- Système de permissions RBAC

### Gestion des Cours
- Types de cours personnalisables (dressage, obstacle, cross, western, etc.)
- Créneaux de disponibilité des enseignants
- Système de réservation avec verrouillage optimiste
- Gestion des lieux de cours avec calcul de trajets

### Paiements & Facturation
- Intégration Stripe pour les paiements
- Stripe Connect pour les reversements aux enseignants
- Génération automatique de factures
- Gestion des abonnements élèves

## Installation

### Avec Docker (Recommandé)

```bash
# Cloner le repository
git clone <repo-url>
cd bookyourcoach

# Démarrer l'environnement complet
./start.sh
```

L'application sera disponible sur :
- **Application Laravel** : http://localhost:8000
- **PHPMyAdmin** : http://localhost:8080
  - Utilisateur : `bookyourcoach`
  - Mot de passe : `password`

### Installation manuelle

```bash
# Installer les dépendances
composer install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Configurer la base de données
php artisan migrate

# Lancer le serveur de développement
php artisan serve
```

## Configuration Docker

### Services inclus

L'environnement Docker inclut :

- **app** : Application Laravel (PHP 8.2-FPM)
- **webserver** : Nginx (proxy vers l'application)
- **db** : MySQL 8.0 avec base de données `bookyourcoach`
- **redis** : Cache et sessions Redis
- **phpmyadmin** : Interface web pour MySQL
- **queue** : Worker pour les tâches en arrière-plan
- **scheduler** : Tâches cron Laravel

### Commandes Docker Utiles

```bash
# Arrêter les services
./stop.sh

# Voir les logs
docker-compose logs -f

# Accéder au conteneur de l'application
docker-compose exec app bash

# Exécuter des commandes Artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tinker

# Redémarrer un service
docker-compose restart app

# Reconstruire les conteneurs
docker-compose up -d --build
```

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
