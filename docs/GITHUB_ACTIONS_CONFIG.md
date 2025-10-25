# Configuration GitHub Actions - BookYourCoach

## Secrets Requis

Pour que le pipeline CI/CD fonctionne correctement, vous devez configurer les secrets suivants dans votre repository GitHub :

### Secrets de Déploiement

#### Staging
- `STAGING_HOST` - Adresse IP ou hostname du serveur staging
- `STAGING_USERNAME` - Nom d'utilisateur SSH pour le serveur staging
- `STAGING_SSH_KEY` - Clé privée SSH pour accéder au serveur staging

#### Production
- `PRODUCTION_HOST` - Adresse IP ou hostname du serveur production
- `PRODUCTION_USERNAME` - Nom d'utilisateur SSH pour le serveur production
- `PRODUCTION_SSH_KEY` - Clé privée SSH pour accéder au serveur production

### Secrets Optionnels

#### Notifications
- `SLACK_WEBHOOK` - URL du webhook Slack pour les notifications (optionnel)

## Variables d'Environnement

Le pipeline utilise automatiquement les variables GitHub suivantes :

- `GITHUB_TOKEN` - Token automatique pour GitHub Container Registry
- `GITHUB_ACTOR` - Nom d'utilisateur qui a déclenché l'action
- `GITHUB_REPOSITORY` - Nom du repository (owner/repo)
- `GITHUB_SHA` - Hash du commit
- `GITHUB_REF_NAME` - Nom de la branche

## Configuration des Environnements

### Environnement Staging
- **Branche:** `develop`
- **Déclenchement:** Push sur `develop`
- **Docker Compose:** `docker-compose.yml`

### Environnement Production
- **Branche:** `main`
- **Déclenchement:** Push sur `main`
- **Docker Compose:** `docker-compose.prod.yml`

## GitHub Container Registry

Le pipeline utilise GitHub Container Registry (ghcr.io) pour stocker les images Docker :

- **Registry:** `ghcr.io`
- **Authentification:** Automatique avec `GITHUB_TOKEN`
- **Images:** `ghcr.io/owner/repository`

## Workflow de Déploiement

### 1. Tests
- Tests unitaires avec PHP 8.3
- Tests avec MySQL 8.0 et Redis 7
- Audit de sécurité Composer

### 2. Build
- Construction d'image Docker multi-architecture
- Push vers GitHub Container Registry
- Cache Docker optimisé

### 3. Déploiement
- Déploiement automatique selon la branche
- Mise à jour des containers Docker
- Nettoyage automatique des images inutilisées

### 4. Notifications
- Notifications GitHub (automatiques)
- Notifications Slack (optionnelles)

## Commandes Utiles

### Déclencher manuellement le pipeline
```bash
# Via GitHub CLI
gh workflow run "BookYourCoach CI/CD Pipeline"

# Via l'interface GitHub
# Actions > BookYourCoach CI/CD Pipeline > Run workflow
```

### Vérifier le statut des déploiements
```bash
# Via GitHub CLI
gh run list --workflow="BookYourCoach CI/CD Pipeline"
gh run view <run-id>
```

## Dépannage

### Problèmes Courants

1. **Échec de connexion SSH**
   - Vérifier que la clé SSH est correctement configurée
   - Tester la connexion manuellement : `ssh -i key user@host`

2. **Échec de build Docker**
   - Vérifier que Dockerfile est présent
   - Vérifier les permissions GitHub Container Registry

3. **Tests qui échouent**
   - Vérifier la configuration MySQL/Redis
   - Vérifier les variables d'environnement

### Logs et Debug

- **Logs GitHub Actions:** Disponibles dans l'onglet Actions
- **Logs Docker:** `docker logs <container-name>`
- **Logs Application:** `docker exec -it <container> tail -f storage/logs/laravel.log`

## Sécurité

- Les secrets sont chiffrés et ne sont jamais exposés dans les logs
- Les tokens GitHub ont des permissions limitées
- Les clés SSH sont spécifiques aux environnements
- Les images Docker sont signées et vérifiées
