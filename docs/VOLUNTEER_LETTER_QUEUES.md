# 📧 Système d'Envoi de Lettres de Volontariat avec Queues

## 🚀 Optimisation de Performance

Le système d'envoi des lettres de volontariat utilise maintenant les **queues Laravel** pour un traitement asynchrone et performant.

### ⏱️ Amélioration des Performances

**Avant (envoi synchrone)** :
- 5 emails : ~15-20 secondes
- Timeout après 10 emails
- Blocage de l'interface utilisateur

**Après (envoi avec queues)** :
- 5 emails : <200ms (réponse immédiate)
- 100+ emails : même performance
- Interface réactive, envoi en arrière-plan

## 🏗️ Architecture

### 1. **SendVolunteerLetterJob**

Job Laravel qui gère l'envoi d'une lettre à un enseignant :

```php
SendVolunteerLetterJob::dispatch($clubId, $teacherId, $userId);
```

**Caractéristiques** :
- ✅ 3 tentatives automatiques en cas d'échec
- ✅ Timeout de 60 secondes par job
- ✅ Génération PDF + envoi email
- ✅ Logging détaillé des succès/échecs
- ✅ Nettoyage automatique des fichiers temporaires

### 2. **Queue Worker**

Worker en arrière-plan qui traite les jobs :

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=60
```

**Configuration** :
- 2 workers en parallèle (via supervisor)
- Traitement immédiat des jobs
- Retry automatique si échec
- Arrêt gracieux lors du redémarrage

## 🔄 Flux de Traitement

```
1. Utilisateur clique sur "Envoyer à tous"
   ↓
2. API crée un job pour chaque enseignant (< 200ms)
   ↓
3. Réponse immédiate : "X lettre(s) en cours d'envoi"
   ↓
4. Worker traite les jobs en arrière-plan
   ↓
5. Pour chaque job :
   - Génération du PDF
   - Envoi de l'email
   - Mise à jour du statut (sent/failed)
   - Suppression du fichier temporaire
   ↓
6. Enregistrement dans volunteer_letter_sends
```

## 📊 Suivi des Envois

### Table `volunteer_letter_sends`

Chaque envoi est enregistré avec :

```sql
- id
- club_id
- teacher_id
- sent_by_user_id
- recipient_email
- status (pending, sent, failed)
- error_message (si échec)
- sent_at
- created_at, updated_at
```

### Vérifier les envois

```bash
# Dans le conteneur Docker
docker compose exec backend php artisan tinker

# Récupérer les envois récents
VolunteerLetterSend::latest()->limit(10)->get();

# Compter les envois par statut
VolunteerLetterSend::where('club_id', 1)
    ->selectRaw('status, count(*) as count')
    ->groupBy('status')
    ->get();
```

## 🔧 Gestion du Worker

### Démarrage du Worker (Développement Local)

```bash
# Démarrer le worker en arrière-plan
docker compose exec -d backend php artisan queue:work --sleep=3 --tries=3 --timeout=60

# Ou en mode interactif pour voir les logs
docker compose exec backend php artisan queue:work --verbose
```

### Vérifier les Jobs en Queue

```bash
# Voir les jobs en attente
docker compose exec backend php artisan queue:monitor

# Afficher les statistiques
docker compose exec mysql-local mysql -u activibe_user -pactivibe_password book_your_coach_local -e "SELECT COUNT(*) as pending_jobs FROM jobs;"
```

### Relancer les Jobs Échoués

```bash
# Voir les jobs qui ont échoué
docker compose exec backend php artisan queue:failed

# Relancer un job spécifique
docker compose exec backend php artisan queue:retry {job_id}

# Relancer tous les jobs échoués
docker compose exec backend php artisan queue:retry all

# Supprimer les jobs échoués
docker compose exec backend php artisan queue:flush
```

## 🐛 Debugging

### Voir les Logs en Direct

```bash
# Logs Laravel
docker compose exec backend tail -f storage/logs/laravel.log | grep -i "volunteer\|letter\|job"

# Logs du worker (si lancé en mode verbose)
docker compose logs -f backend | grep -i "processing\|processed"
```

### Tester un Envoi

```bash
docker compose exec backend php artisan tinker

# Dans Tinker
$club = App\Models\Club::find(1);
$teacher = App\Models\Teacher::with('user')->first();

App\Jobs\SendVolunteerLetterJob::dispatch($club->id, $teacher->id, 1);
```

### Vider la Queue

```bash
# Supprimer tous les jobs en attente
docker compose exec backend php artisan queue:clear

# Ou directement en base de données
docker compose exec mysql-local mysql -u activibe_user -pactivibe_password book_your_coach_local -e "TRUNCATE TABLE jobs;"
```

## 📧 MailHog - Capture des Emails en Local

Les emails sont capturés par MailHog en développement :

**Interface web** : http://localhost:8026

Vous y verrez :
- ✅ Tous les emails envoyés
- ✅ Le contenu HTML de la lettre
- ✅ Le PDF en pièce jointe
- ✅ Les en-têtes complets

## 🚀 Production

### Configuration Queue en Production

Dans `.env` de production :

```env
QUEUE_CONNECTION=database

# Optionnel : Utiliser Redis pour meilleures performances
# QUEUE_CONNECTION=redis
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379
```

### Démarrer le Worker en Production

Utiliser supervisor pour gérer le worker :

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/activibe/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/activibe/storage/logs/worker.log
stopwaitsecs=3600
```

Redémarrer supervisor :

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## 📈 Monitoring

### Horizon (Optionnel)

Pour un monitoring avancé, installer Laravel Horizon :

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

Interface : `https://activibe.be/horizon`

## 🎯 Bonnes Pratiques

1. **Toujours avoir un worker actif** en production
2. **Monitorer les jobs échoués** régulièrement
3. **Vider les jobs anciens** de temps en temps
4. **Utiliser Redis** en production pour meilleures performances
5. **Configurer les alertes** si trop de jobs échouent

## 🆘 Dépannage

### Les emails ne partent pas

1. Vérifier que le worker est actif :
   ```bash
   docker compose exec backend ps aux | grep queue:work
   ```

2. Vérifier les jobs en queue :
   ```bash
   docker compose exec backend php artisan queue:monitor
   ```

3. Vérifier les logs :
   ```bash
   docker compose exec backend tail -f storage/logs/laravel.log
   ```

### Les emails sont envoyés mais non reçus

1. Vérifier MailHog (local) : http://localhost:8025
2. Vérifier les logs du serveur mail (production)
3. Vérifier la configuration SMTP dans `.env`

### Performance lente

1. Augmenter le nombre de workers
2. Passer à Redis au lieu de database
3. Optimiser les requêtes dans le Job
4. Augmenter les ressources du serveur

## 📚 Ressources

- [Laravel Queues Documentation](https://laravel.com/docs/queues)
- [Laravel Horizon Documentation](https://laravel.com/docs/horizon)
- [Supervisor Documentation](http://supervisord.org/)

