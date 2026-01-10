#!/bin/bash

# Script pour configurer MailHog avec docker-compose.local.yml
# Ce script connecte le container MailHog existant (fid-connect-mailhog-1) au réseau app-network
# Le réseau utilisé par docker-compose.local.yml est: bookyourcoach_app-network

set -e

echo "🔧 Configuration de MailHog pour docker-compose.local.yml..."
echo ""

# Vérifier si le container MailHog existant (fid-connect-mailhog-1) existe
EXISTING_MAILHOG=$(docker ps -a --format '{{.Names}}' | grep -E "^(fid-connect-mailhog-1|activibe-mailhog)" | head -1)

if [ -n "$EXISTING_MAILHOG" ]; then
    echo "✅ Container MailHog existant trouvé: $EXISTING_MAILHOG"
    
    # Nom du réseau utilisé par docker-compose.local.yml
    NETWORK_NAME="bookyourcoach_app-network"
    
    # Vérifier si le réseau existe
    if ! docker network ls --format '{{.Name}}' | grep -q "^${NETWORK_NAME}$"; then
        echo "📦 Le réseau $NETWORK_NAME sera créé automatiquement par docker-compose.local.yml"
        echo "   Démarrez d'abord les services: docker compose -f docker-compose.local.yml up -d"
        echo "   Puis relancez ce script pour connecter MailHog"
        exit 0
    fi
    
    # Vérifier si MailHog est déjà connecté
    if docker network inspect "$NETWORK_NAME" 2>/dev/null | grep -q "\"$EXISTING_MAILHOG\""; then
        echo "✅ Container déjà connecté au réseau $NETWORK_NAME"
    else
        # Connecter le container existant au réseau
        echo "🔗 Connexion de $EXISTING_MAILHOG au réseau $NETWORK_NAME..."
        docker network connect "$NETWORK_NAME" "$EXISTING_MAILHOG" 2>/dev/null && echo "✅ Container connecté avec succès" || echo "⚠️ Erreur lors de la connexion"
    fi
    
    echo ""
    echo "✅ Configuration terminée !"
    echo "📧 MailHog est accessible via:"
    echo "   - Interface web: http://localhost:8025"
    echo "   - SMTP: $EXISTING_MAILHOG:1025 (depuis Docker) ou localhost:1025 (depuis le host)"
    echo ""
    echo "⚠️  IMPORTANT: Dans votre .env.local, configurez:"
    echo "   MAIL_HOST=$EXISTING_MAILHOG"
    echo "   MAIL_PORT=1025"
    echo ""
    
else
    echo "⚠️  Aucun container MailHog existant trouvé."
    echo "📦 Un nouveau container MailHog sera créé avec docker-compose.local.yml"
    echo ""
    echo "   Dans votre .env.local, configurez:"
    echo "   MAIL_HOST=mailhog"
    echo "   MAIL_PORT=1025"
    echo ""
    echo "   Puis démarrez avec: docker compose -f docker-compose.local.yml up -d"
fi
