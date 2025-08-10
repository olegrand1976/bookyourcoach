# BookYourCoach - Environnement Docker

## 🐳 Présentation

Ce projet utilise Docker Compose pour créer un environnement de développement complet avec :

-   **Application Laravel** : Framework PHP moderne
-   **MySQL 8.0** : Base de données relationnelle
-   **Redis** : Cache et gestion des sessions
-   **Nginx** : Serveur web
-   **PHPMyAdmin** : Interface d'administration de la base de données
-   **Queue Worker** : Traitement des tâches en arrière-plan
-   **Scheduler** : Exécution des tâches planifiées

## 🚀 Démarrage rapide

### Prérequis

-   Docker et Docker Compose installés
-   Ports disponibles : 3307, 6380, 8080, 8081, 8082

### Commandes essentielles

```bash
# Démarrer tous les services
docker-compose up -d

# Arrêter tous les services
docker-compose down

# Reconstruire et démarrer
docker-compose up -d --build

# Voir les logs
docker-compose logs -f

# Exécuter des commandes Artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan make:controller NomController
```

## 🌐 URLs de développement

-   **Application principale** : http://localhost:8081
-   **PHPMyAdmin** : http://localhost:8082
-   **Application (port alternatif)** : http://localhost:8080

## 🗄️ Configuration base de données

-   **Host** : mysql (dans Docker) / localhost:3307 (depuis l'hôte)
-   **Base de données** : bookyourcoach
-   **Utilisateur** : laravel
-   **Mot de passe** : laravel_password
-   **Root password** : root_password

## 🔧 Configuration Redis

-   **Host** : redis (dans Docker) / localhost:6380 (depuis l'hôte)
-   **Port** : 6379 (interne) / 6380 (externe)

## 📋 Tâches VS Code disponibles

1. **Docker: Start All Services** - Démarre tous les conteneurs
2. **Docker: Stop All Services** - Arrête tous les conteneurs
3. **Docker: Rebuild and Start** - Reconstruit et démarre
4. **Laravel: Run Migrations** - Exécute les migrations

## 🛠️ Développement

### Structure des conteneurs

-   `bookyourcoach_app` : Application Laravel (PHP-FPM)
-   `bookyourcoach_webserver` : Serveur Nginx
-   `bookyourcoach_mysql` : Base de données MySQL
-   `bookyourcoach_redis` : Cache Redis
-   `bookyourcoach_phpmyadmin` : Interface PHPMyAdmin
-   `bookyourcoach_queue` : Worker pour les queues
-   `bookyourcoach_scheduler` : Scheduler Laravel

### Volumes persistants

-   `mysql_data` : Données MySQL persistantes
-   `redis_data` : Données Redis persistantes

### Réseau

Tous les conteneurs communiquent via le réseau `bookyourcoach_network`.

## 🐛 Dépannage

### Problèmes de ports

Si vous obtenez des erreurs de ports déjà utilisés :

1. Vérifiez les processus actifs : `sudo lsof -i :3307 -i :6380 -i :8081`
2. Modifiez les ports dans `docker-compose.yml` si nécessaire

### Permissions

```bash
# Fixer les permissions si nécessaire
docker-compose exec app chown -R laravel:www-data /var/www/storage
docker-compose exec app chmod -R 755 /var/www/storage
```

### Logs

```bash
# Voir tous les logs
docker-compose logs

# Logs d'un service spécifique
docker-compose logs app
docker-compose logs mysql
```

## 📦 Modèles créés

Le projet inclut tous les modèles Eloquent pour le système de réservation :

-   User (avec rôles : admin, teacher, student)
-   Profile
-   Teacher / Student
-   CourseType
-   Location
-   Lesson
-   Payment / Invoice
-   Subscription
-   Availability / TimeBlock
-   Payout
-   AuditLog

## 🔄 Workflow de développement

1. Démarrer l'environnement : `docker-compose up -d`
2. Exécuter les migrations : `docker-compose exec app php artisan migrate`
3. Développer votre application
4. Tester via http://localhost:8081
5. Gérer la base de données via PHPMyAdmin : http://localhost:8082

## 📝 Notes importantes

-   L'environnement utilise des ports alternatifs pour éviter les conflits
-   Toutes les données de la base sont persistantes
-   Les queues et le scheduler sont automatiquement démarrés
-   Le code source est monté en volume pour le développement en temps réel
