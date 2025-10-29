#!/bin/bash
# Script pour configurer MailHog dans .env.local

echo "🔧 Configuration de MailHog pour l'environnement local..."

# Backup du fichier .env.local
cp .env.local .env.local.backup.$(date +%Y%m%d_%H%M%S)

# Mettre à jour les variables MAIL_* pour MailHog
sed -i 's/^MAIL_MAILER=.*/MAIL_MAILER=smtp/' .env.local
sed -i 's/^MAIL_HOST=.*/MAIL_HOST=mailhog/' .env.local
sed -i 's/^MAIL_PORT=.*/MAIL_PORT=1025/' .env.local
sed -i 's/^MAIL_USERNAME=.*/MAIL_USERNAME=null/' .env.local
sed -i 's/^MAIL_PASSWORD=.*/MAIL_PASSWORD=null/' .env.local
sed -i 's/^MAIL_ENCRYPTION=.*/MAIL_ENCRYPTION=null/' .env.local

echo "✅ Configuration MailHog terminée !"
echo ""
echo "📧 MailHog configuré avec :"
echo "   - Host: mailhog"
echo "   - Port: 1025"
echo "   - Interface Web: http://localhost:8025"
echo ""
echo "🔄 Pour appliquer les changements, redémarrez le backend :"
echo "   docker compose -f docker-compose.local.yml restart backend"
