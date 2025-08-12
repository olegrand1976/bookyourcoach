#!/bin/sh

# Script de démarrage pour l'application Nuxt
echo "🚀 Démarrage de l'application Nuxt..."
echo "📁 Répertoire de travail: $(pwd)"
echo "📂 Contenu du répertoire:"
ls -la
echo "📄 Contenu de .output:"
ls -la .output/
echo "📄 Contenu de .output/server:"
ls -la .output/server/
echo "▶️ Démarrage de Node.js..."

exec node .output/server/index.mjs
