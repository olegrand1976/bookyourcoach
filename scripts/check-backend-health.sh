#!/bin/bash

# Script de vérification de la santé du conteneur backend
# Usage: ./scripts/check-backend-health.sh [timeout_seconds]

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
CONTAINER_NAME="${CONTAINER_NAME:-activibe-backend}"
TIMEOUT="${1:-120}"  # Timeout par défaut de 120 secondes
INTERVAL=5

echo -e "${BLUE}🔍 Vérification de la santé du conteneur ${CONTAINER_NAME}${NC}"
echo -e "${YELLOW}Timeout: ${TIMEOUT}s | Intervalle: ${INTERVAL}s${NC}"
echo ""

# Fonction pour vérifier si le conteneur est en cours d'exécution
check_container_running() {
    docker ps --filter "name=${CONTAINER_NAME}" --filter "status=running" | grep -q "${CONTAINER_NAME}"
}

# Fonction pour vérifier la santé du conteneur
check_container_health() {
    local health_status=$(docker inspect --format='{{.State.Health.Status}}' "${CONTAINER_NAME}" 2>/dev/null)
    echo "$health_status"
}

# Fonction pour vérifier le endpoint /health
check_health_endpoint() {
    docker exec "${CONTAINER_NAME}" curl -f -s http://localhost:80/health > /dev/null 2>&1
}

# Fonction pour afficher les logs du conteneur
show_container_logs() {
    echo -e "${YELLOW}📋 Dernières lignes des logs du conteneur:${NC}"
    docker logs --tail 20 "${CONTAINER_NAME}" 2>&1
}

# Vérifier si le conteneur existe
if ! docker ps -a --filter "name=${CONTAINER_NAME}" | grep -q "${CONTAINER_NAME}"; then
    echo -e "${RED}❌ Le conteneur ${CONTAINER_NAME} n'existe pas${NC}"
    exit 1
fi

# Attendre que le conteneur démarre
elapsed=0
echo -e "${YELLOW}⏳ Attente du démarrage du conteneur...${NC}"

while [ $elapsed -lt $TIMEOUT ]; do
    if check_container_running; then
        echo -e "${GREEN}✅ Le conteneur est en cours d'exécution${NC}"
        break
    fi
    
    sleep $INTERVAL
    elapsed=$((elapsed + INTERVAL))
    echo -e "${YELLOW}   Temps écoulé: ${elapsed}s / ${TIMEOUT}s${NC}"
done

if [ $elapsed -ge $TIMEOUT ]; then
    echo -e "${RED}❌ Le conteneur n'a pas démarré dans les temps impartis${NC}"
    show_container_logs
    exit 1
fi

# Attendre que le conteneur soit sain
echo ""
echo -e "${YELLOW}⏳ Vérification de la santé du conteneur...${NC}"
elapsed=0

while [ $elapsed -lt $TIMEOUT ]; do
    health_status=$(check_container_health)
    
    if [ "$health_status" = "healthy" ]; then
        echo -e "${GREEN}✅ Le conteneur est sain (healthy)${NC}"
        break
    elif [ "$health_status" = "unhealthy" ]; then
        echo -e "${RED}❌ Le conteneur est en mauvaise santé (unhealthy)${NC}"
        show_container_logs
        exit 1
    else
        echo -e "${YELLOW}   État actuel: ${health_status:-starting} | Temps écoulé: ${elapsed}s / ${TIMEOUT}s${NC}"
    fi
    
    # Si pas de healthcheck configuré, vérifier directement l'endpoint
    if [ -z "$health_status" ] || [ "$health_status" = "none" ]; then
        if check_health_endpoint; then
            echo -e "${GREEN}✅ Le endpoint /health répond correctement${NC}"
            break
        fi
    fi
    
    sleep $INTERVAL
    elapsed=$((elapsed + INTERVAL))
done

if [ $elapsed -ge $TIMEOUT ]; then
    echo -e "${RED}❌ Le conteneur n'a pas atteint l'état healthy dans les temps impartis${NC}"
    show_container_logs
    exit 1
fi

# Afficher les informations du conteneur
echo ""
echo -e "${BLUE}📊 Informations du conteneur:${NC}"
docker inspect "${CONTAINER_NAME}" --format='État: {{.State.Status}}
Santé: {{.State.Health.Status}}
Démarré à: {{.State.StartedAt}}
PID: {{.State.Pid}}'

# Vérifier les erreurs dans les logs
echo ""
echo -e "${BLUE}🔍 Recherche d'erreurs dans les logs...${NC}"
error_count=$(docker logs "${CONTAINER_NAME}" 2>&1 | grep -i -E "(error|alert|fatal|failed)" | wc -l)

if [ $error_count -gt 0 ]; then
    echo -e "${YELLOW}⚠️  ${error_count} erreur(s) ou alerte(s) détectée(s) dans les logs${NC}"
    echo -e "${YELLOW}📋 Dernières erreurs/alertes:${NC}"
    docker logs "${CONTAINER_NAME}" 2>&1 | grep -i -E "(error|alert|fatal|failed)" | tail -10
else
    echo -e "${GREEN}✅ Aucune erreur critique détectée${NC}"
fi

echo ""
echo -e "${GREEN}🎉 Vérification terminée avec succès !${NC}"
exit 0


