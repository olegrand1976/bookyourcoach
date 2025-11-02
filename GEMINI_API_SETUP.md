# Configuration API Gemini

## Clé API
La clé API Gemini a été configurée dans le projet :
- Clé : `AIzaSyAHo6kb9eAWEABFrBWCujRvF60JU7_XfEc`
- Modèle : `gemini-2.5-flash`

## Fichiers modifiés
- ✅ `env.example` : Clé API et modèle mis à jour
- ✅ `config/services.php` : Modèle par défaut mis à jour à `gemini-2.5-flash`
- ✅ `app/Services/AI/GeminiService.php` : Modèle par défaut mis à jour à `gemini-2.5-flash`

## Configuration nécessaire
⚠️ **Important** : Ajoutez la clé API dans votre fichier `.env` :

```bash
# Google Gemini AI Configuration
GEMINI_API_KEY=AIzaSyAHo6kb9eAWEABFrBWCujRvF60JU7_XfEc
GEMINI_MODEL=gemini-2.5-flash
```

Pour Docker, vous pouvez aussi l'ajouter dans `docker-compose.yml` ou `docker-compose.local.yml` dans la section `environment` du service backend.

## Vérification
Après avoir ajouté la clé dans `.env`, redémarrez le serveur Laravel pour que les changements prennent effet.

```bash
# Si vous utilisez Docker
docker-compose restart activibe-backend-local

# Ou si vous utilisez artisan serve
php artisan config:clear
php artisan cache:clear
```
