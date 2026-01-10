#!/bin/bash

# Script pour connecter automatiquement MailHog au bon réseau selon le docker-compose utilisé
# Résout les problèmes de connexion MailHog <-> Backend

set -e

echo "🔧 Configuration automatique de MailHog pour docker-compose.local.yml"
echo ""

# Détecter quel container MailHog est actif
ACTIVE_MAILHOG=$(docker ps --format '{{.Names}}' | grep -E "mailhog" | head -1)

if [ -z "$ACTIVE_MAILHOG" ]; then
    echo "❌ Aucun container MailHog actif trouvé"
    echo "   Démarrez d'abord MailHog ou utilisez docker-compose.local.yml qui créera un service mailhog"
    exit 1
fi

echo "✅ Container MailHog actif trouvé: $ACTIVE_MAILHOG"

# Nom du réseau utilisé par docker-compose.local.yml
NETWORK_NAME="bookyourcoach_app-network"

# Vérifier si le réseau existe
if ! docker network ls --format '{{.Name}}' | grep -q "^${NETWORK_NAME}$"; then
    echo "📦 Le réseau $NETWORK_NAME n'existe pas encore"
    echo "   Démarrez d'abord les services: docker compose -f docker-compose.local.yml up -d"
    echo "   Le réseau sera créé automatiquement"
    exit 1
fi

# Vérifier si MailHog est déjà connecté
if docker network inspect "$NETWORK_NAME" 2>/dev/null | grep -q "\"$ACTIVE_MAILHOG\""; then
    echo "✅ Container $ACTIVE_MAILHOG est déjà connecté au réseau $NETWORK_NAME"
else
    echo "🔗 Connexion de $ACTIVE_MAILHOG au réseau $NETWORK_NAME..."
    docker network connect "$NETWORK_NAME" "$ACTIVE_MAILHOG" 2>/dev/null && echo "✅ Container connecté avec succès" || {
        echo "❌ Erreur lors de la connexion"
        exit 1
    }
fi

# Obtenir l'adresse IP de MailHog sur le réseau
MAILHOG_IP=$(docker network inspect "$NETWORK_NAME" 2>/dev/null | grep -A 5 "\"$ACTIVE_MAILHOG\"" | grep "IPv4Address" | head -1 | sed 's/.*"\([0-9.]*\)\/.*/\1/')

echo ""
echo "✅ Configuration terminée !"
echo ""
echo "📧 Configuration pour .env.local:"
echo "   MAIL_HOST=$ACTIVE_MAILHOG"
echo "   MAIL_PORT=1025"
echo ""
echo "🌐 Accès MailHog:"
echo "   - Interface web: http://localhost:8025"
echo "   - SMTP (Docker): $ACTIVE_MAILHOG:1025"
echo "   - SMTP (Host): localhost:1025"
if [ -n "$MAILHOG_IP" ]; then
    echo "   - IP sur réseau: $MAILHOG_IP"
fi
echo ""
echo "🔄 Redémarrez le backend pour prendre en compte la configuration:"
echo "   docker compose -f docker-compose.local.yml restart backend"
