#!/bin/bash

# Script de développement pour le frontend uniquement
echo "🎨 Mode développement Frontend"

# Vérifier si les services backend sont en cours
if ! docker-compose ps | grep -q "app.*Up"; then
    echo "⚠️  Le backend Laravel n'est pas démarré"
    echo "Voulez-vous le démarrer? (y/n)"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        echo "🔄 Démarrage du backend..."
        docker-compose up -d app mysql redis
        sleep 5
    fi
fi

# Démarrer le frontend en mode développement
echo "🚀 Démarrage du frontend en mode développement..."
cd frontend

# Installer les dépendances si nécessaire
if [ ! -d "node_modules" ]; then
    echo "📚 Installation des dépendances..."
    npm install
fi

# Démarrer le serveur de développement
npm run dev
