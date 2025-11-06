#!/bin/bash

# Script d'activation de l'optimisation asynchrone
# Ce script configure automatiquement le système pour utiliser les queues

echo "🚀 Configuration de l'optimisation asynchrone pour la création de cours"
echo "======================================================================="
echo ""

# 1. Vérifier que nous sommes dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo "❌ Erreur: Le fichier 'artisan' n'a pas été trouvé."
    echo "   Veuillez exécuter ce script depuis la racine du projet."
    exit 1
fi

# 2. Vérifier la configuration actuelle
CURRENT_CONFIG=$(grep "^QUEUE_CONNECTION=" .env | cut -d '=' -f2)
echo "📊 Configuration actuelle: QUEUE_CONNECTION=$CURRENT_CONFIG"
echo ""

if [ "$CURRENT_CONFIG" = "sync" ]; then
    echo "⚠️  Le système est configuré en mode SYNC (synchrone)"
    echo "   L'optimisation ne sera pas active avec ce mode."
    echo ""
    read -p "Voulez-vous passer en mode DATABASE (asynchrone) ? [O/n] " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Oo]$ ]] || [[ -z $REPLY ]]; then
        # Modifier le .env
        sed -i 's/^QUEUE_CONNECTION=sync/QUEUE_CONNECTION=database/' .env
        echo "✅ Configuration mise à jour: QUEUE_CONNECTION=database"
        echo ""
    else
        echo "❌ Configuration non modifiée. L'optimisation ne sera pas active."
        exit 0
    fi
else
    echo "✅ Le système est déjà configuré pour utiliser les queues ($CURRENT_CONFIG)"
    echo ""
fi

# 3. Vérifier les migrations
echo "📋 Vérification des migrations de queue..."
php artisan migrate:status 2>&1 | grep -q "create_jobs_table"
if [ $? -ne 0 ]; then
    echo "⚠️  Les migrations de queue n'ont pas été exécutées."
    read -p "Voulez-vous exécuter les migrations maintenant ? [O/n] " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Oo]$ ]] || [[ -z $REPLY ]]; then
        php artisan migrate --force
        echo "✅ Migrations exécutées"
        echo ""
    fi
else
    echo "✅ Les migrations de queue sont déjà exécutées"
    echo ""
fi

# 4. Vérifier si un worker est déjà lancé
if ps aux | grep -q "[q]ueue:work"; then
    echo "✅ Un worker de queue est déjà en cours d'exécution"
    echo ""
else
    echo "⚠️  Aucun worker de queue n'est en cours d'exécution"
    echo ""
    echo "Pour que l'optimisation fonctionne, vous devez lancer le worker:"
    echo ""
    echo "   Option 1 (recommandé):"
    echo "   ./start-queue-worker.sh"
    echo ""
    echo "   Option 2:"
    echo "   php artisan queue:work --verbose"
    echo ""
    read -p "Voulez-vous lancer le worker maintenant ? [O/n] " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Oo]$ ]] || [[ -z $REPLY ]]; then
        echo ""
        echo "🚀 Lancement du worker de queue..."
        echo "   (Appuyez sur Ctrl+C pour arrêter)"
        echo ""
        php artisan queue:work --verbose --tries=3 --timeout=120
    else
        echo ""
        echo "✅ Configuration terminée !"
        echo ""
        echo "N'oubliez pas de lancer le worker manuellement pour activer l'optimisation:"
        echo "   ./start-queue-worker.sh"
        echo ""
    fi
fi

echo ""
echo "✨ L'optimisation est maintenant configurée !"
echo ""
echo "📖 Pour plus d'informations, consultez:"
echo "   - INSTRUCTIONS_OPTIMISATION.md"
echo "   - docs/OPTIMISATION_CREATION_COURS.md"
echo ""



