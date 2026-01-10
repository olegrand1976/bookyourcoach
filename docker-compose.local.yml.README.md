# 📦 Configuration docker-compose.local.yml

## 🎯 Utilisation recommandée pour le développement local

Ce fichier Docker Compose est optimisé pour le développement local avec :
- ✅ Code en écriture (hot-reload activé)
- ✅ Build local du backend et frontend
- ✅ MailHog intégré pour capturer les emails
- ✅ Tous les services nécessaires (MySQL, Neo4j, phpMyAdmin)

## 🚀 Démarrage rapide

```bash
# 1. Arrêter les containers existants (si nécessaire)
docker compose down

# 2. Démarrer tous les services
docker compose -f docker-compose.local.yml up -d

# 3. Vérifier le statut
docker compose -f docker-compose.local.yml ps
```

## 📧 Configuration MailHog

### Option 1 : Utiliser le service MailHog intégré (recommandé pour docker-compose.local.yml)

Le service `mailhog` dans ce fichier créera un nouveau container MailHog.

**Configuration dans `.env.local` :**
```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

**Accès :**
- Interface web : http://localhost:8035
- SMTP : `mailhog:1025` (depuis le réseau Docker)

### Option 2 : Utiliser un container MailHog existant

Si vous avez déjà un container MailHog (ex: `fid-connect-mailhog-1`), vous pouvez l'utiliser :

1. **Connecter le container au réseau :**
   ```bash
   docker network create app-network 2>/dev/null || true
   docker network connect app-network fid-connect-mailhog-1
   ```

2. **Commenter le service mailhog dans docker-compose.local.yml :**
   ```yaml
   # mailhog:
   #   image: mailhog/mailhog:latest
   #   ...
   ```

3. **Configuration dans `.env.local` :**
   ```env
   MAIL_HOST=fid-connect-mailhog-1
   MAIL_PORT=1025
   ```

4. **Démarrer sans le service mailhog :**
   ```bash
   docker compose -f docker-compose.local.yml up -d backend frontend mysql-local neo4j phpmyadmin
   ```

## 🔍 Services disponibles

| Service | Container | Ports | Description |
|---------|-----------|-------|-------------|
| Backend | `activibe-backend-local` | 8080 | API Laravel |
| Frontend | `activibe-frontend-local` | 3000 | Application Nuxt.js |
| MySQL | `activibe-mysql-local` | 3308 | Base de données |
| Neo4j | `activibe-neo4j-local` | 7474, 7687 | Graph database |
| MailHog | `activibe-mailhog-local` | 8035 (web), 1025 (SMTP) | Capture d'emails |
| phpMyAdmin | `activibe-phpmyadmin-local` | 8082 | Administration MySQL |

## 🔄 Différences avec docker-compose.yml

| Aspect | docker-compose.yml | docker-compose.local.yml |
|--------|-------------------|-------------------------|
| Image backend | Pré-construite | Build local |
| Code backend | Lecture seule (`:ro`) | Écriture (hot-reload) |
| Code frontend | Production buildé | Mode développement |
| MailHog | Container externe | Service intégré |
| Usage | Production/test prod | Développement local |

## 📝 Notes importantes

1. **Base de données** : Utilise `book_your_coach_local` sur le port `3308`
2. **Hot-reload** : Les modifications de code sont automatiquement reflétées
3. **Volumes** : Les données persistent dans des volumes Docker nommés
4. **Réseau** : Tous les services sont sur le réseau `app-network`

## 🛠️ Commandes utiles

```bash
# Voir les logs
docker compose -f docker-compose.local.yml logs -f backend
docker compose -f docker-compose.local.yml logs -f frontend

# Redémarrer un service
docker compose -f docker-compose.local.yml restart backend

# Reconstruire un service
docker compose -f docker-compose.local.yml build backend
docker compose -f docker-compose.local.yml up -d --build backend

# Arrêter tout
docker compose -f docker-compose.local.yml down

# Arrêter et supprimer les volumes (⚠️ supprime les données)
docker compose -f docker-compose.local.yml down -v
```

## ⚠️ Dépannage

### Port déjà utilisé
Si un port est déjà utilisé, arrêtez le container qui l'utilise :
```bash
docker ps | grep <port>
docker stop <container_id>
```

### MailHog ne reçoit pas les emails
1. Vérifiez que MailHog est démarré : `docker compose -f docker-compose.local.yml ps mailhog`
2. Vérifiez la configuration dans `.env.local` : `MAIL_HOST=mailhog`, `MAIL_PORT=1025`
3. Testez la connexion depuis le backend : `docker compose -f docker-compose.local.yml exec backend nc -zv mailhog 1025`

### Base de données non accessible
1. Vérifiez que MySQL est démarré : `docker compose -f docker-compose.local.yml ps mysql-local`
2. Vérifiez la configuration dans `.env.local` : `DB_HOST=mysql-local`, `DB_PORT=3306`
3. Testez la connexion : `docker compose -f docker-compose.local.yml exec backend mysql -h mysql-local -u activibe_user -pactivibe_password book_your_coach_local`
