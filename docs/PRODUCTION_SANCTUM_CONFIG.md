# Configuration Sanctum pour la Production

## Variables d'environnement requises

Pour que l'authentification Sanctum fonctionne correctement en production, ajoutez ces variables à votre fichier `.env` :

```bash
# Configuration Sanctum pour la production
SANCTUM_STATEFUL_DOMAINS=activibe.be,www.activibe.be,localhost:3000,127.0.0.1:3000

# Configuration CORS
FRONTEND_URL=https://activibe.be
CORS_ALLOWED_ORIGINS=https://activibe.be,https://www.activibe.be

# Configuration de session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.activibe.be
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

## Configuration du frontend

Le frontend doit être configuré avec l'URL de production de l'API :

```bash
# Dans docker-compose.yml ou variables d'environnement du frontend
NUXT_PUBLIC_API_BASE=https://activibe.be/api
NUXT_API_BASE=https://activibe.be/api
```

## Configuration CORS

Assurez-vous que le fichier `config/cors.php` contient les domaines de production :

```php
'allowed_origins' => [
    env('FRONTEND_URL', 'https://activibe.be'),
    'https://activibe.be',
    'https://www.activibe.be',
    'http://activibe.be',
    'http://www.activibe.be',
    // ... autres domaines
],
```

## Vérifications importantes

1. **Domaines stateful** : Les domaines dans `SANCTUM_STATEFUL_DOMAINS` doivent correspondre exactement aux domaines utilisés par le frontend
2. **Cookies de session** : Le domaine de session doit être configuré pour permettre le partage entre sous-domaines
3. **HTTPS** : En production, utilisez toujours HTTPS pour la sécurité des cookies
4. **CORS** : Vérifiez que les origines CORS incluent tous les domaines frontend

## Test de la configuration

Pour tester la configuration en production :

1. Connectez-vous via le frontend
2. Vérifiez que les cookies de session sont créés
3. Testez la navigation entre les pages protégées
4. Vérifiez que la déconnexion fonctionne correctement

## Dépannage

Si vous rencontrez des problèmes :

1. Vérifiez les logs du backend pour les erreurs d'authentification
2. Inspectez les cookies dans les outils de développement du navigateur
3. Vérifiez que les domaines stateful correspondent exactement
4. Assurez-vous que les en-têtes CORS sont correctement configurés
