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

> **Démarrage en cours (frontend)** — Après `up -d`, le conteneur Nuxt lance Vite en mode développement. Pendant **quelques secondes à une minute** (premier lancement ou après un `build`), l’URL http://localhost:3000 peut répondre **503** ou charger lentement : c’est attendu, pas une panne. Attendez les messages `✔ Vite client built` / `✔ Vite server built` dans les logs (`logs -f frontend`), puis rechargez la page.

## 📧 Configuration MailHog

### Option 1 : Utiliser le service MailHog intégré (recommandé pour docker-compose.local.yml)

Le service `mailhog` dans ce fichier créera un nouveau container MailHog.

**Comportement Laravel (depuis le code versionné) :** avec `APP_ENV=local`, l’application **utilise automatiquement MailHog** (mailer `mailhog`), même si `.env.local` contient encore des variables SMTP type Mailjet. Aucune ligne `MAIL_HOST=mailhog` n’est obligatoire.

- Désactiver ce forçage : `MAIL_USE_MAILHOG=false` dans `.env.local`.
- Ajoutez côté service **backend** : `MAIL_USE_MAILHOG=true` (obligatoire si `APP_ENV` n’est pas `local`, ex. `.env` copié de la prod), plus `MAIL_MAILHOG_HOST=mailhog` et `MAIL_MAILHOG_PORT=1025`. Le backend doit dépendre du service `mailhog`.

**Accès :**
- Interface web : http://localhost:8035
- SMTP : `mailhog:1025` (réseau Docker, conteneur **backend** — inchangé) ou `localhost:11025` depuis la machine hôte (Laravel hors Docker), car **1025** sur l’hôte est souvent déjà pris par un autre service.

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
| MailHog | `activibe-mailhog-local` | 8035 (web), **11025→1025** (SMTP hôte) | Capture d'emails |
| phpMyAdmin | `activibe-phpmyadmin-local` | 8082 | Administration MySQL |

## 🔄 Différences avec docker-compose.yml

| Aspect | docker-compose.yml | docker-compose.local.yml |
|--------|-------------------|-------------------------|
| Image backend | Pré-construite | Build local |
| Code backend | Lecture seule (`:ro`) | Écriture (hot-reload) |
| Code frontend | Production buildé | Mode développement |
| MailHog | Container externe | Service intégré |
| Certificat `cert.pem` | Montage optionnel côté hôte | Aucun (non requis en HTTP local) |
| Usage | Production/test prod | Développement local |

## 📝 Notes importantes

1. **Base de données** : Utilise `book_your_coach_local` sur le port `3308`
2. **Hot-reload** : Les modifications de code sont automatiquement reflétées
3. **Volumes** : Les données persistent dans des volumes Docker nommés
4. **Réseau** : Tous les services sont sur le réseau `app-network`
5. **Frontend** : délai possible et **503** au premier chargement — voir l’encadré *Démarrage en cours* après le démarrage rapide
6. **Certificat PEM** : en local, aucun `cert.pem` n’est monté dans le backend (HTTP sur le port 8080). En production / `docker-compose.yml`, le montage du PEM reste prévu si besoin.

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

### Frontend : erreur 503 ou page blanche juste après le démarrage

1. **Comportement normal** : voir l’encadré *Démarrage en cours (frontend)* au-dessus du chapitre MailHog.
2. Vérifiez les logs : `docker compose -f docker-compose.local.yml logs -f frontend` jusqu’à ce que Nuxt affiche une URL locale (ex. `Local: http://0.0.0.0:3000/`).
3. Test rapide : `curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:3000/` — attendez un **200** avant de conclure à un problème.

### Port déjà utilisé
Si un port est déjà utilisé, arrêtez le container qui l'utilise :
```bash
docker ps | grep <port>
docker stop <container_id>
```

**`Bind for 0.0.0.0:1025 failed`** : un autre service (autre MailHog, SMTP, etc.) occupe déjà **1025** sur l’hôte. Le compose mappe désormais **11025:1025** : le backend dans Docker utilise toujours `mailhog:1025` ; depuis l’hôte uniquement, pointez `localhost:11025` si vous lancez Laravel en dehors des conteneurs.

### MailHog ne reçoit pas les emails
1. Vérifiez que MailHog est démarré : `docker compose -f docker-compose.local.yml ps mailhog`
2. Backend **dans Docker** : `MAIL_MAILHOG_HOST=mailhog`, `MAIL_MAILHOG_PORT=1025` (réseau interne). Pas besoin d’exposer 1025 sur l’hôte pour l’API dans le conteneur.
3. Testez depuis le backend : `docker compose -f docker-compose.local.yml exec backend nc -zv mailhog 1025`

### Base de données non accessible
1. Vérifiez que MySQL est démarré : `docker compose -f docker-compose.local.yml ps mysql-local`
2. Vérifiez la configuration dans `.env.local` : `DB_HOST=mysql-local`, `DB_PORT=3306`
3. Testez la connexion : `docker compose -f docker-compose.local.yml exec backend mysql -h mysql-local -u activibe_user -pactivibe_password book_your_coach_local`

### Backend `unhealthy` / frontend bloqué en `Created` (en attente du backend)

Le frontend attend `backend` **healthy**. Si le healthcheck échoue, vérifiez :

1. **`APP_KEY`** : dans `.env.local`, `APP_KEY=` ne doit pas être vide (`php artisan key:generate --show` sur l’hôte). Sinon l’entrypoint du backend **quitte** avec un message explicite.
2. **Erreur `CollisionServiceProvider` not found** : le volume `app_bootstrap_cache` peut contenir un `packages.php` / `services.php` générés avec **`composer install` incluant les dev** (Collision, etc.). L’image Docker installe **`--no-dev`** : Laravel ne peut pas charger ces classes. L’entrypoint supprime d’abord les `*.php` du cache **sans** passer par Artisan, puis relance `package:discover`. En dernier recours : `docker volume rm <nom>_app_bootstrap_cache` puis `up -d`.
3. **Healthcheck** : le compose utilise `GET /up` (route Laravel légère). `/api/health` doit aussi répondre **200** une fois le cache cohérent.
4. **Logs** : `docker compose -f docker-compose.local.yml exec backend tail -n 80 storage/logs/laravel.log`

### Neo4j : `The client is unauthorized due to authentication failure`

Le mot de passe effectif est celui enregistré **la première fois** que le volume `neo4j_data` a été créé. Si `NEO4J_AUTH=neo4j/secret_password` dans le compose ne correspond pas à ce qui est dans le volume, alignez `NEO4J_PASSWORD` (et URI utilisateur) dans `.env.local`, **ou** supprimez le volume Neo4j pour repartir à zéro : `docker compose -f docker-compose.local.yml down` puis `docker volume rm …neo4j_data…` (voir `docker volume ls`), puis `up -d` (perte des données graphe locales).
