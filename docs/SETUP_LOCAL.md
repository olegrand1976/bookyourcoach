# 🚀 Guide de configuration pour l'environnement local

Ce guide vous explique comment configurer et utiliser l'environnement de développement local avec `docker-compose.local.yml`.

## 📋 Fichiers Docker Compose disponibles

Le projet contient plusieurs fichiers Docker Compose pour différents environnements :

1. **`docker-compose.yml`** - Configuration de production/production-like
   - Utilise l'image pré-construite
   - Code en lecture seule
   - MailHog : utilise le container existant `fid-connect-mailhog-1`

2. **`docker-compose.local.yml`** ⭐ **RECOMMANDÉ pour le développement local**
   - Build local du backend et frontend
   - Code en écriture (hot-reload activé)
   - MailHog intégré
   - Configuration optimisée pour le développement

3. **`docker-compose.dev.yml`** - Configuration de développement alternative
   - Services avec suffixe `-dev`
   - Ports différents pour éviter les conflits

4. **`docker-compose.e2e.yml`** - Tests end-to-end avec Playwright

## 🎯 Démarrage avec docker-compose.local.yml

### Prérequis

1. Assurez-vous que les ports suivants sont libres :
   - `8080` : Backend API
   - `3000` : Frontend
   - `8025` : MailHog Web UI (ou `8035` si vous utilisez le service intégré)
   - `3308` : MySQL
   - `7474` : Neo4j Web
   - `7687` : Neo4j Bolt
   - `8082` : phpMyAdmin

2. Vérifiez que votre fichier `.env.local` est correctement configuré :
   ```bash
   DB_HOST=mysql-local
   DB_PORT=3306
   MAIL_HOST=mailhog
   MAIL_PORT=1025
   ```

### Démarrage

```bash
# Arrêter les containers existants (si nécessaire)
docker compose down

# Option 1 : Utiliser le container MailHog existant (fid-connect-mailhog-1)
# Connectez-le d'abord au réseau
docker network create app-network 2>/dev/null || true
docker network connect app-network fid-connect-mailhog-1 2>/dev/null || true

# Puis démarrez les services (sans le service mailhog)
docker compose -f docker-compose.local.yml up -d backend frontend mysql-local neo4j phpmyadmin

# Option 2 : Utiliser le service MailHog intégré (crée un nouveau container)
docker compose -f docker-compose.local.yml up -d
```

### Configuration MailHog

#### Option A : Utiliser le container MailHog existant (fid-connect-mailhog-1)

Si vous avez déjà un container MailHog qui tourne (par exemple `fid-connect-mailhog-1`), vous pouvez l'utiliser :

```bash
# 1. Connecter le container au réseau app-network
docker network create app-network 2>/dev/null || echo "Réseau existe déjà"
docker network connect app-network fid-connect-mailhog-1

# 2. Dans .env.local, configurez :
# MAIL_HOST=fid-connect-mailhog-1
# MAIL_PORT=1025

# 3. Commentez le service mailhog dans docker-compose.local.yml ou utilisez un profil
```

#### Option B : Utiliser le service MailHog intégré

Le service `mailhog` dans `docker-compose.local.yml` créera un nouveau container :

```bash
# Dans .env.local, configurez :
# MAIL_HOST=mailhog
# MAIL_PORT=1025

# L'interface web sera accessible sur http://localhost:8035
```

### Accès aux services

Une fois démarré, accédez aux services via :

- **Frontend** : http://localhost:3000
- **Backend API** : http://localhost:8080/api
- **MailHog Web UI** : 
  - Si container existant : http://localhost:8025
  - Si service intégré : http://localhost:8035
- **phpMyAdmin** : http://localhost:8082
- **Neo4j Browser** : http://localhost:7474

### Vérification

```bash
# Vérifier le statut des services
docker compose -f docker-compose.local.yml ps

# Vérifier les logs
docker compose -f docker-compose.local.yml logs -f backend
docker compose -f docker-compose.local.yml logs -f frontend

# Tester la connexion au backend
curl http://localhost:8080/api/health

# Vérifier que MailHog reçoit les emails
curl http://localhost:8025/api/v2/messages  # Container existant
# ou
curl http://localhost:8035/api/v2/messages  # Service intégré
```

### Arrêt

```bash
# Arrêter tous les services
docker compose -f docker-compose.local.yml down

# Arrêter et supprimer les volumes (⚠️ supprime les données)
docker compose -f docker-compose.local.yml down -v
```

## 🔧 Scripts utilitaires

Un script est disponible pour faciliter la configuration de MailHog :

```bash
./scripts/setup-mailhog-local.sh
```

Ce script :
- Détecte si un container MailHog existant est disponible
- Le connecte au réseau `app-network` si nécessaire
- Vous indique la configuration à utiliser dans `.env.local`

## ⚠️ Notes importantes

1. **Conflits de ports** : Si des ports sont déjà utilisés, arrêtez les containers qui les utilisent ou modifiez les ports dans `docker-compose.local.yml`

2. **Variables d'environnement** : Le fichier `.env.local` est partagé entre les différentes configurations. Assurez-vous que les valeurs correspondent à la configuration Docker Compose utilisée.

3. **Réseaux Docker** : Si vous utilisez le container MailHog existant, assurez-vous qu'il est bien connecté au réseau `app-network` utilisé par `docker-compose.local.yml`

4. **Hot-reload** : Avec `docker-compose.local.yml`, les modifications de code sont reflétées automatiquement grâce aux volumes montés.
