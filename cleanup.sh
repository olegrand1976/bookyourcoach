#!/bin/bash

# Script de nettoyage complet pour résoudre les conflits
# Supprime tous les conteneurs et redémarre proprement

echo "🧹 NETTOYAGE COMPLET SYSTÈME"
echo "============================"

# Arrêter TOUS les conteneurs
echo "🛑 Arrêt de tous les conteneurs..."
docker stop $(docker ps -q) 2>/dev/null || true

# Supprimer tous les conteneurs
echo "🗑️  Suppression de tous les conteneurs..."
docker rm $(docker ps -aq) 2>/dev/null || true

# Nettoyer les images non utilisées
echo "🧽 Nettoyage des images non utilisées..."
docker image prune -f

# Nettoyer le système
echo "🔧 Nettoyage du système Docker..."
docker system prune -f

echo ""
echo "✅ Nettoyage terminé!"
echo "💡 Vous pouvez maintenant relancer le déploiement:"
echo "   ./fix-deployment.sh"