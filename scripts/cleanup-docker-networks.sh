#!/bin/bash

# =============================================================================
# Script de nettoyage des réseaux Docker pour BookYourCoach
# =============================================================================

echo "🧹 Nettoyage des réseaux Docker pour BookYourCoach"
echo ""

# Vérifier que nous sommes dans un projet Laravel
if [ ! -f "artisan" ]; then
    echo "❌ Ce script doit être exécuté dans le répertoire racine du projet Laravel"
    echo "💡 Naviguez vers le répertoire du projet et relancez le script"
    exit 1
fi

echo "✅ Projet Laravel détecté"
echo ""

# Afficher les réseaux existants
echo "📋 Réseaux Docker existants:"
docker network ls --format "table {{.Name}}\t{{.Driver}}\t{{.Scope}}"
echo ""

# Afficher les sous-réseaux utilisés
echo "📋 Sous-réseaux utilisés:"
for network in $(docker network ls --format "{{.Name}}" | grep -E "(bookyourcoach|app-network)"); do
    echo "=== $network ==="
    docker network inspect $network --format "{{range .IPAM.Config}}{{.Subnet}}{{end}}" 2>/dev/null || echo "N/A"
done
echo ""

# Arrêter les conteneurs BookYourCoach
echo "🛑 Arrêt des conteneurs BookYourCoach..."
docker compose down
echo ""

# Nettoyer les réseaux BookYourCoach orphelins
echo "🧹 Nettoyage des réseaux BookYourCoach orphelins..."
docker network prune -f --filter "label=com.docker.compose.project=bookyourcoach"
echo ""

# Vérifier les sous-réseaux libres
echo "🔍 Recherche de sous-réseaux libres..."
used_subnets=()
for network in $(docker network ls --format "{{.Name}}" | grep -v bridge | grep -v none | grep -v host); do
    subnet=$(docker network inspect $network --format "{{range .IPAM.Config}}{{.Subnet}}{{end}}" 2>/dev/null)
    if [ ! -z "$subnet" ]; then
        used_subnets+=("$subnet")
    fi
done

echo "Sous-réseaux utilisés:"
for subnet in "${used_subnets[@]}"; do
    echo "  - $subnet"
done

# Trouver un sous-réseau libre
free_subnet=""
for i in {18..30}; do
    candidate="172.$i.0.0/16"
    if [[ ! " ${used_subnets[@]} " =~ " ${candidate} " ]]; then
        free_subnet="$candidate"
        break
    fi
done

if [ ! -z "$free_subnet" ]; then
    echo ""
    echo "✅ Sous-réseau libre trouvé: $free_subnet"
    echo "💡 Vous pouvez l'utiliser dans votre docker-compose.yml:"
    echo "   subnet: $free_subnet"
else
    echo ""
    echo "⚠️  Aucun sous-réseau libre trouvé dans la plage 172.18-30.0.0/16"
    echo "💡 Vous devrez peut-être nettoyer d'autres projets Docker"
fi

echo ""
echo "🎉 Nettoyage terminé !"
echo ""
echo "💡 Pour redémarrer BookYourCoach:"
echo "   docker compose up -d"
echo ""
echo "💡 Pour voir tous les réseaux:"
echo "   docker network ls"
