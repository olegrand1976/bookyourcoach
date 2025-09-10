# BookYourCoach Production Configuration

## Variables d'Environnement Requises

### Application
```bash
APP_NAME="BookYourCoach"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bookyourcoach.com
```

### Base de Données
```bash
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=bookyourcoach_prod
DB_USERNAME=bookyourcoach_user
DB_PASSWORD=your_secure_password
MYSQL_ROOT_PASSWORD=your_root_password
```

### Redis
```bash
REDIS_HOST=redis
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Mail
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_mailgun_username
MAIL_PASSWORD=your_mailgun_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@bookyourcoach.com"
MAIL_FROM_NAME="BookYourCoach"
```

### Sanctum
```bash
SANCTUM_STATEFUL_DOMAINS=bookyourcoach.com,www.bookyourcoach.com
SESSION_DOMAIN=.bookyourcoach.com
```

## Configuration SSL

### Génération des Certificats

```bash
# Certificat auto-signé pour le développement
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/certs/bookyourcoach.key \
  -out /etc/ssl/certs/bookyourcoach.crt \
  -subj "/C=FR/ST=France/L=Paris/O=BookYourCoach/CN=bookyourcoach.com"

# Certificat Let's Encrypt pour la production
certbot certonly --standalone -d bookyourcoach.com -d www.bookyourcoach.com
```

## Déploiement

### 1. Préparation du Serveur

```bash
# Mise à jour du système
sudo apt update && sudo apt upgrade -y

# Installation Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Installation Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 2. Configuration du Repository

```bash
# Cloner le repository
git clone https://github.com/owner/bookyourcoach.git
cd bookyourcoach

# Copier la configuration de production
cp .env.example .env.production

# Éditer les variables d'environnement
nano .env.production
```

### 3. Déploiement avec Docker Compose

```bash
# Déploiement production
docker-compose -f docker-compose.prod.yml up -d

# Vérification des services
docker-compose -f docker-compose.prod.yml ps

# Logs de l'application
docker-compose -f docker-compose.prod.yml logs -f app
```

### 4. Configuration Nginx

```bash
# Copier la configuration Nginx
sudo cp docker/nginx/prod.conf /etc/nginx/sites-available/bookyourcoach
sudo ln -s /etc/nginx/sites-available/bookyourcoach /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Monitoring

### Health Checks

```bash
# Vérification de l'état des services
curl -f http://localhost/health || exit 1

# Vérification de la base de données
docker exec bookyourcoach_mysql_prod mysqladmin ping

# Vérification de Redis
docker exec bookyourcoach_redis_prod redis-cli ping
```

### Logs

```bash
# Logs de l'application Laravel
docker exec bookyourcoach_app_prod tail -f storage/logs/laravel.log

# Logs Nginx
docker exec bookyourcoach_webserver_prod tail -f /var/log/nginx/access.log

# Logs MySQL
docker exec bookyourcoach_mysql_prod tail -f /var/log/mysql/error.log
```

## Maintenance

### Sauvegarde

```bash
# Sauvegarde de la base de données
docker exec bookyourcoach_mysql_prod mysqldump -u root -p bookyourcoach_prod > backup_$(date +%Y%m%d_%H%M%S).sql

# Sauvegarde des fichiers
tar -czf storage_backup_$(date +%Y%m%d_%H%M%S).tar.gz storage/
```

### Mise à Jour

```bash
# Mise à jour via GitHub Actions (automatique)
# Ou manuellement :
git pull origin main
docker-compose -f docker-compose.prod.yml pull
docker-compose -f docker-compose.prod.yml up -d
```

### Nettoyage

```bash
# Nettoyage des images Docker inutilisées
docker system prune -f

# Nettoyage des volumes inutilisés
docker volume prune -f
```

## Sécurité

### Firewall

```bash
# Configuration UFW
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Fail2Ban

```bash
# Installation Fail2Ban
sudo apt install fail2ban

# Configuration pour Nginx
sudo nano /etc/fail2ban/jail.local
```

## Performance

### Optimisations Docker

```bash
# Limitation des ressources
docker update --memory=1g --cpus=1 bookyourcoach_app_prod

# Monitoring des ressources
docker stats bookyourcoach_app_prod
```

### Optimisations Laravel

```bash
# Cache de configuration
docker exec bookyourcoach_app_prod php artisan config:cache

# Cache des routes
docker exec bookyourcoach_app_prod php artisan route:cache

# Cache des vues
docker exec bookyourcoach_app_prod php artisan view:cache
```
