#!/bin/bash

# Script pour tester l'API du dashboard des clubs
# Usage: ./scripts/test-club-api.sh

echo "🏇 Test de l'API du dashboard des clubs..."
echo ""

# Récupérer un gestionnaire de club
echo "📋 Récupération d'un gestionnaire de club..."
MANAGER_EMAIL=$(docker-compose -f docker-compose.local.yml exec -T backend php artisan tinker --execute="
\$manager = DB::table('users')->where('role', 'club')->first();
if (\$manager) {
    echo \$manager->email;
} else {
    echo 'AUCUN_MANAGER';
}
")

if [ "$MANAGER_EMAIL" = "AUCUN_MANAGER" ]; then
    echo "❌ Aucun gestionnaire de club trouvé. Exécutez d'abord le seeder des clubs."
    exit 1
fi

echo "✅ Gestionnaire trouvé: $MANAGER_EMAIL"
echo ""

# Test de connexion
echo "🔐 Test de connexion..."
LOGIN_RESPONSE=$(curl -s -X POST http://localhost:8081/api/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$MANAGER_EMAIL\",\"password\":\"password\"}")

echo "Réponse de connexion: $LOGIN_RESPONSE"
echo ""

# Extraire le token
TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "❌ Échec de la connexion. Token non récupéré."
    exit 1
fi

echo "✅ Token récupéré: ${TOKEN:0:20}..."
echo ""

# Test de l'API dashboard
echo "📊 Test de l'API dashboard..."
DASHBOARD_RESPONSE=$(curl -s -X GET http://localhost:8081/api/club/dashboard \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

echo "Réponse du dashboard:"
echo "$DASHBOARD_RESPONSE" | jq . 2>/dev/null || echo "$DASHBOARD_RESPONSE"
echo ""

# Vérifier le succès
if echo "$DASHBOARD_RESPONSE" | grep -q '"success":true'; then
    echo "✅ API du dashboard fonctionne correctement !"
    
    # Afficher les statistiques
    echo ""
    echo "📈 Statistiques du club:"
    echo "$DASHBOARD_RESPONSE" | jq '.data.stats' 2>/dev/null || echo "Impossible d'afficher les stats"
    
else
    echo "❌ L'API du dashboard a échoué."
    exit 1
fi

echo ""
echo "🎉 Test terminé avec succès !"
