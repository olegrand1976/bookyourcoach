#!/bin/bash

# Script pour déployer les améliorations de gestion d'erreur du dashboard des clubs
echo "🚀 Déploiement des améliorations de gestion d'erreur du dashboard des clubs"
echo ""

# Vérifier que nous sommes sur la branche main
echo "📋 Vérification de la branche..."
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "main" ]; then
    echo "❌ Vous n'êtes pas sur la branche main (actuellement sur: $CURRENT_BRANCH)"
    echo "💡 Veuillez basculer sur la branche main avant de déployer"
    exit 1
fi

echo "✅ Branche main confirmée"
echo ""

# Vérifier que le fichier a été modifié
echo "📁 Vérification des modifications..."
if [ ! -f "frontend/pages/club/dashboard.vue" ]; then
    echo "❌ Le fichier dashboard.vue n'existe pas"
    exit 1
fi

echo "✅ Fichier dashboard.vue trouvé"
echo ""

# Ajouter les fichiers modifiés
echo "📝 Ajout des fichiers modifiés..."
git add frontend/pages/club/dashboard.vue
echo ""

# Commit des changements
echo "💾 Commit des changements..."
git commit -m "feat: Amélioration de la gestion d'erreur du dashboard des clubs

- Ajout de la gestion spécifique des erreurs HTTP (401, 403, 404, 500)
- Affichage de toasts informatifs selon le type d'erreur
- Ajout d'un indicateur de chargement
- Ajout d'un message d'erreur avec bouton de réessai
- Redirection automatique vers /login en cas d'erreur 401
- Messages d'erreur plus lisibles pour l'utilisateur"
echo ""

# Push vers le serveur
echo "🌐 Push vers le serveur de production..."
git push origin main
echo ""

echo "✅ Déploiement terminé !"
echo ""
echo "📋 Améliorations déployées:"
echo "   - Gestion spécifique des erreurs HTTP"
echo "   - Toasts informatifs selon le type d'erreur"
echo "   - Indicateur de chargement"
echo "   - Message d'erreur avec bouton de réessai"
echo "   - Redirection automatique en cas de session expirée"
echo ""
echo "🔍 Prochaines étapes:"
echo "1. Attendre que le serveur redémarre automatiquement (2-3 minutes)"
echo "2. Tester le dashboard des clubs"
echo "3. Vérifier que les erreurs sont maintenant gérées proprement"
echo ""
echo "🧪 Pour tester:"
echo "   - Aller sur https://activibe.be/club/dashboard"
echo "   - Vérifier que les erreurs affichent des toasts informatifs"
echo "   - Vérifier que l'indicateur de chargement s'affiche"
echo "   - Vérifier que le bouton 'Réessayer' fonctionne"
