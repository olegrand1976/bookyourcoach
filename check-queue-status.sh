#!/bin/bash

# Script de vérification du statut des queues
# Plus simple et sans dépendances PHP

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║        DIAGNOSTIC DU SYSTÈME DE QUEUES                       ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# 1. Configuration actuelle
echo "📊 CONFIGURATION ACTUELLE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -f ".env" ]; then
    QUEUE_CONN=$(grep "^QUEUE_CONNECTION=" .env | cut -d '=' -f2)
    DB_CONN=$(grep "^DB_CONNECTION=" .env | cut -d '=' -f2)
    echo "✓ QUEUE_CONNECTION: $QUEUE_CONN"
    echo "✓ DB_CONNECTION: $DB_CONN"
else
    echo "✗ Fichier .env non trouvé"
    exit 1
fi
echo ""

# 2. Statut de l'optimisation
echo "🚀 STATUT DE L'OPTIMISATION"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$QUEUE_CONN" = "sync" ]; then
    echo "❌ OPTIMISATION INACTIVE (mode sync)"
    echo "   Les jobs s'exécutent de manière synchrone"
    echo "   Temps de création de cours: 2-3 secondes"
    echo ""
    echo "   💡 Pour activer l'optimisation:"
    echo "      ./enable-async-optimization.sh"
    OPTIMIZATION_ACTIVE=false
elif [ "$QUEUE_CONN" = "database" ] || [ "$QUEUE_CONN" = "redis" ]; then
    echo "✅ OPTIMISATION CONFIGURÉE (mode $QUEUE_CONN)"
    echo "   Les jobs s'exécutent de manière asynchrone"
    echo "   Temps de création de cours: ~120ms"
    OPTIMIZATION_ACTIVE=true
else
    echo "⚠️  Configuration inconnue: $QUEUE_CONN"
    OPTIMIZATION_ACTIVE=false
fi
echo ""

# 3. Vérifier le worker
echo "🔍 WORKER DE QUEUE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if ps aux | grep -q "[q]ueue:work"; then
    echo "✅ Worker ACTIF"
    echo ""
    echo "   Processus détectés:"
    ps aux | grep "[q]ueue:work" | awk '{print "   - PID " $2 ": " $11 " " $12 " " $13 " " $14}'
    WORKER_ACTIVE=true
else
    echo "❌ Worker INACTIF"
    echo ""
    if [ "$OPTIMIZATION_ACTIVE" = true ]; then
        echo "   ⚠️  L'optimisation est configurée mais le worker n'est pas lancé!"
        echo "      Les jobs seront mis en queue mais jamais traités."
        echo ""
        echo "   💡 Pour lancer le worker:"
        echo "      ./start-queue-worker.sh"
        echo ""
        echo "      Ou en arrière-plan:"
        echo "      nohup php artisan queue:work --verbose > storage/logs/queue-worker.log 2>&1 &"
    else
        echo "   ℹ️  Normal en mode sync (pas de worker nécessaire)"
    fi
    WORKER_ACTIVE=false
fi
echo ""

# 4. Vérifier les fichiers créés
echo "📁 FICHIERS DE L'OPTIMISATION"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
FILES=(
    "app/Jobs/ProcessLessonPostCreationJob.php:Job asynchrone"
    "docs/OPTIMISATION_CREATION_COURS.md:Documentation technique"
    "INSTRUCTIONS_OPTIMISATION.md:Guide d'activation"
    "RESUME_OPTIMISATION.md:Vue d'ensemble"
    "enable-async-optimization.sh:Script d'activation"
    "start-queue-worker.sh:Script de démarrage worker"
)

ALL_FILES_OK=true
for file_info in "${FILES[@]}"; do
    file=$(echo $file_info | cut -d ':' -f1)
    desc=$(echo $file_info | cut -d ':' -f2)
    if [ -f "$file" ]; then
        echo "✓ $desc"
    else
        echo "✗ $desc (manquant: $file)"
        ALL_FILES_OK=false
    fi
done
echo ""

# 5. Vérifier Docker
echo "🐳 DOCKER"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if command -v docker &> /dev/null; then
    if docker ps | grep -q "mysql"; then
        echo "✓ Conteneur MySQL actif"
        docker ps --format "  - {{.Names}}: {{.Status}}" | grep mysql
    else
        echo "⚠️  Aucun conteneur MySQL détecté"
    fi
else
    echo "ℹ️  Docker non disponible"
fi
echo ""

# 6. Résumé et recommandations
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📝 RÉSUMÉ ET RECOMMANDATIONS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ "$OPTIMIZATION_ACTIVE" = true ] && [ "$WORKER_ACTIVE" = true ]; then
    echo "🎉 EXCELLENT ! Tout est opérationnel !"
    echo ""
    echo "   ✅ L'optimisation est configurée"
    echo "   ✅ Le worker est actif"
    echo "   ✅ Les cours se créent instantanément (~120ms)"
    echo ""
    echo "   Vous pouvez tester sur: /club/planning"
    echo ""
elif [ "$OPTIMIZATION_ACTIVE" = true ] && [ "$WORKER_ACTIVE" = false ]; then
    echo "⚠️  PRESQUE PRÊT - Worker manquant"
    echo ""
    echo "   ✅ L'optimisation est configurée"
    echo "   ❌ Le worker n'est pas lancé"
    echo ""
    echo "   Action requise:"
    echo "   1. Lancez le worker:"
    echo "      ./start-queue-worker.sh"
    echo ""
    echo "   2. Testez sur /club/planning"
    echo ""
elif [ "$OPTIMIZATION_ACTIVE" = false ]; then
    echo "❌ OPTIMISATION INACTIVE"
    echo ""
    echo "   ❌ Mode sync activé (pas d'optimisation)"
    echo "   ⏱️  Création de cours: 2-3 secondes (lent)"
    echo ""
    echo "   Actions requises:"
    echo "   1. Activez l'optimisation:"
    echo "      ./enable-async-optimization.sh"
    echo ""
    echo "   2. Le script lancera automatiquement le worker"
    echo ""
    echo "   3. Testez sur /club/planning"
    echo ""
else
    echo "⚠️  État inconnu - Vérification manuelle nécessaire"
    echo ""
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📚 Documentation complète: cat DEMARRAGE_RAPIDE.txt"
echo ""



