# Configuration de Production pour activibe.be

## Problème résolu : Contenu Mixte (Mixed Content)

Le problème était que le site `activibe.be` utilise HTTPS mais tentait d'accéder à une API en HTTP, ce qui est bloqué par les navigateurs modernes.

## Modifications apportées

### 1. Configuration Frontend (nuxt.config.ts)
- Détection automatique de l'environnement de production
- Utilisation de HTTPS pour l'API en production
- Configuration flexible pour le développement et la production

### 2. Configuration CORS (config/cors.php)
- Ajout des domaines `activibe.be` et `www.activibe.be`
- Support HTTP et HTTPS pour la compatibilité

### 3. Configuration Nginx HTTPS (docker/nginx/activibe.conf)
- Configuration SSL complète
- Redirection HTTP vers HTTPS
- Headers de sécurité
- Rate limiting pour l'API

### 4. Script de déploiement (deploy_activibe_https.sh)
- Déploiement automatisé avec support SSL
- Gestion des certificats
- Vérification de la santé des services

## Configuration requise pour la production

### Variables d'environnement (.env)
```bash
APP_NAME="Acti'Vibe"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://activibe.be

# Configuration CORS
FRONTEND_URL=https://activibe.be

# Configuration Sanctum
SANCTUM_STATEFUL_DOMAINS=activibe.be,www.activibe.be
SESSION_DOMAIN=.activibe.be
```

### Certificats SSL
Les certificats doivent être placés dans :
- `/etc/ssl/certs/activibe.crt`
- `/etc/ssl/private/activibe.key`

### Déploiement
```bash
# Exécuter le script de déploiement
./deploy_activibe_https.sh
```

## Résultat attendu

Après déploiement :
- ✅ Site accessible sur `https://activibe.be`
- ✅ API accessible sur `https://activibe.be/api`
- ✅ Plus de problème de contenu mixte
- ✅ Connexion utilisateur fonctionnelle
- ✅ Création d'utilisateur dans l'admin fonctionnelle

## Vérification

1. Accéder à `https://activibe.be`
2. Tenter une connexion
3. Vérifier que l'API répond en HTTPS
4. Tester la création d'utilisateur dans l'admin
