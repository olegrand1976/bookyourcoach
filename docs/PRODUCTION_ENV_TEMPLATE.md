# Template de configuration .env pour la production

## Variables à ajouter/modifier dans votre .env de production

```bash
# Configuration Sanctum (IMPORTANT pour l'authentification)
SANCTUM_STATEFUL_DOMAINS=activibe.be,www.activibe.be,localhost:3000,127.0.0.1:3000

# Configuration CORS
FRONTEND_URL=https://activibe.be
CORS_ALLOWED_ORIGINS=https://activibe.be,https://www.activibe.be

# Configuration de session (modifier)
SESSION_DRIVER=redis
SESSION_DOMAIN=.activibe.be
SESSION_PATH=/
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Configuration de debug (recommandé pour la production)
APP_DEBUG=false
LOG_LEVEL=error
```

## Modifications importantes

### 1. Session Driver
Changez `SESSION_DRIVER=file` vers `SESSION_DRIVER=redis` pour une meilleure performance et compatibilité avec Sanctum.

### 2. Debug Mode
Changez `APP_DEBUG=true` vers `APP_DEBUG=false` pour la production.

### 3. Log Level
Changez `LOG_LEVEL=debug` vers `LOG_LEVEL=error` pour réduire les logs en production.

## Vérification après déploiement

1. **Test de connexion API :**
```bash
curl -X POST https://activibe.be/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"sophie.martin@activibe.com","password":"password"}'
```

2. **Test du frontend :**
```bash
curl -I https://activibe.be
```

3. **Vérification des cookies de session :**
   - Connectez-vous via le frontend
   - Vérifiez dans les outils de développement que les cookies de session sont créés
   - Testez la navigation entre les pages protégées

## Dépannage

Si l'authentification ne fonctionne pas :

1. Vérifiez les logs du backend : `docker-compose logs backend`
2. Vérifiez les logs du frontend : `docker-compose logs frontend`
3. Vérifiez que les domaines stateful correspondent exactement
4. Assurez-vous que les cookies de session sont créés
5. Vérifiez la configuration CORS
