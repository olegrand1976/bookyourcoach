#!/bin/bash

# Script pour démarrer l'environnement local avec docker-compose.local.yml
# Configure automatiquement MailHog selon le container disponible

set -e

COMPOSE_FILE="docker-compose.local.yml"

echo "🚀 Démarrage de l'environnement local avec $COMPOSE_FILE"
echo ""

# Vérifier si docker-compose.local.yml existe
if [ ! -f "$COMPOSE_FILE" ]; then
    echo "❌ Erreur: $COMPOSE_FILE n'existe pas"
    exit 1
fi

# Arrêter les containers existants du projet actuel
echo "🛑 Arrêt des containers existants..."
docker compose down 2>/dev/null || true

# Vérifier si un container MailHog existe déjà
EXISTING_MAILHOG=$(docker ps -a --format '{{.Names}}' | grep -E "^(fid-connect-mailhog-1|activibe-mailhog)" | head -1)

if [ -n "$EXISTING_MAILHOG" ]; then
    echo "📧 Container MailHog existant trouvé: $EXISTING_MAILHOG"
    
    # Vérifier si le réseau app-network existe (sera créé par docker-compose si nécessaire)
    NETWORK_NAME="bookyourcoach_app-network"
    if ! docker network ls --format '{{.Name}}' | grep -q "^${NETWORK_NAME}$"; then
        echo "   Le réseau $NETWORK_NAME sera créé par docker-compose"
    fi
    
    # Connecter automatiquement le container MailHog existant au réseau
    echo "🔗 Connexion automatique de $EXISTING_MAILHOG au réseau $NETWORK_NAME..."
    
    # Attendre que le réseau soit créé par docker-compose
    echo "   Démarrage des services d'abord..."
    docker compose -f "$COMPOSE_FILE" up -d backend mysql-local neo4j 2>/dev/null || true
    sleep 2
    
    # Connecter MailHog au réseau
    docker network connect "$NETWORK_NAME" "$EXISTING_MAILHOG" 2>/dev/null && echo "   ✅ Container MailHog connecté" || echo "   ⚠️ Container déjà connecté au réseau"
    
    echo ""
    echo "⚠️  IMPORTANT: Dans votre .env.local, configurez:"
    echo "   MAIL_HOST=$EXISTING_MAILHOG"
    echo "   MAIL_PORT=1025"
    echo ""
    echo "   Puis démarrez le reste des services:"
    echo "   docker compose -f $COMPOSE_FILE up -d frontend phpmyadmin"
    echo ""
    echo "   Ou si vous préférez utiliser le service mailhog intégré, commentez le service dans $COMPOSE_FILE"
    echo "   et configurez MAIL_HOST=mailhog dans .env.local"
    echo ""
    exit 0
fi

# Vérifier les ports
echo "🔍 Vérification des ports..."
PORTS_TO_CHECK=(8080 3000 8035 3308 7474 7687 8082)
OCCUPIED_PORTS=()

for port in "${PORTS_TO_CHECK[@]}"; do
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
        OCCUPIED_PORTS+=($port)
    fi
done

if [ ${#OCCUPIED_PORTS[@]} -gt 0 ]; then
    echo "⚠️  Attention: Les ports suivants sont déjà utilisés: ${OCCUPIED_PORTS[*]}"
    echo "   Vous devrez peut-être arrêter les containers qui les utilisent."
    echo ""
fi

# Démarrer les services
echo "🚀 Démarrage des services avec $COMPOSE_FILE..."
docker compose -f "$COMPOSE_FILE" up -d

# Attendre que les services soient prêts
echo ""
echo "⏳ Attente du démarrage des services..."
sleep 5

# Vérifier le statut
echo ""
echo "📊 Statut des services:"
docker compose -f "$COMPOSE_FILE" ps

echo ""
echo "✅ Environnement local démarré !"
echo ""
echo "🌐 Accès aux services:"
echo "   - Frontend: http://localhost:3000"
echo "   - Backend API: http://localhost:8080/api"
echo "   - MailHog Web UI: http://localhost:8035"
echo "   - phpMyAdmin: http://localhost:8082"
echo "   - Neo4j Browser: http://localhost:7474"
echo ""
echo "📧 Configuration MailHog dans .env.local:"
echo "   MAIL_HOST=mailhog"
echo "   MAIL_PORT=1025"
echo ""
echo "📝 Voir les logs: docker compose -f $COMPOSE_FILE logs -f"
