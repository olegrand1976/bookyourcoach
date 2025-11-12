# 📧 MailHog - Test d'Emails en Local

## 🎯 Qu'est-ce que MailHog ?

MailHog est un outil de test d'emails qui capture tous les emails sortants de votre application Laravel et les affiche dans une interface web conviviale.

## 🚀 Utilisation

### Accéder à l'interface web

Une fois les conteneurs Docker lancés, accédez à MailHog via :

**🌐 http://localhost:8035**

Tous les emails envoyés par l'application Laravel seront capturés et affichés ici.

## 📋 Configuration

### Variables d'environnement (.env.local)

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@activibe.com
MAIL_FROM_NAME=ActiVibe
```

### Ports utilisés

- **8035** : Interface web (http://localhost:8035)
- **1025** : Port SMTP (utilisé par le backend Docker via le réseau interne)

## 🧪 Tester l'envoi d'emails

### Via Tinker

```bash
docker compose exec backend php artisan tinker
```

Puis dans Tinker :

```php
Mail::raw('Test email from Laravel', function($message) {
    $message->to('test@example.com')
            ->subject('Email de test');
});
```

### Via les lettres de volontariat

1. Connectez-vous en tant que club
2. Allez sur `/club/volunteer-letter`
3. Cliquez sur "Envoyer par Email" pour un enseignant
4. Vérifiez l'email dans http://localhost:8035

## 🔧 Commandes utiles

### Démarrer MailHog

```bash
docker compose -f docker-compose.local.yml up -d mailhog
```

### Redémarrer le backend (après changement de config)

```bash
docker compose -f docker-compose.local.yml restart backend
```

### Voir les logs de MailHog

```bash
docker compose logs -f mailhog
```

### Arrêter MailHog

```bash
docker compose -f docker-compose.local.yml stop mailhog
```

## 📝 Notes

- MailHog **ne fonctionne qu'en environnement local** (docker-compose.local.yml)
- Les emails **ne sont pas réellement envoyés**, ils sont juste capturés
- L'interface web permet de :
  - Voir le contenu HTML et texte des emails
  - Visualiser les pièces jointes
  - Voir les en-têtes complets
  - Télécharger les emails au format .eml

## 🎨 Fonctionnalités de l'interface

- ✅ Vue liste de tous les emails capturés
- ✅ Recherche et filtrage
- ✅ Vue détaillée avec HTML rendu
- ✅ Téléchargement des pièces jointes (PDF, etc.)
- ✅ Suppression des emails de test
- ✅ API REST pour automatisation

## 🐛 Dépannage

### Les emails n'apparaissent pas

1. Vérifiez que MailHog est bien démarré :
   ```bash
   docker compose ps | grep mailhog
   ```

2. Vérifiez les logs du backend :
   ```bash
   docker compose logs backend | grep -i mail
   ```

3. Vérifiez la configuration dans `.env.local`

### Port déjà utilisé

Si vous voyez une erreur "port already allocated" :

1. Changez le port dans `docker-compose.local.yml`
2. Redémarrez MailHog

## 📚 Ressources

- [Documentation MailHog](https://github.com/mailhog/MailHog)
- [Laravel Mail Documentation](https://laravel.com/docs/mail)

