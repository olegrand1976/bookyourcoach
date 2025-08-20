#!/bin/bash

echo "=== Test de connexion BookYourCoach ==="
echo

# Test 1: Backend API disponible
echo "1. Test de disponibilité de l'API Laravel..."
if curl -s http://localhost:8081 > /dev/null; then
    echo "✅ API Laravel accessible"
else
    echo "❌ API Laravel non accessible"
fi

# Test 2: Frontend Nuxt disponible
echo "2. Test de disponibilité du frontend Nuxt..."
if curl -s http://localhost:3000 > /dev/null; then
    echo "✅ Frontend Nuxt accessible"
else
    echo "❌ Frontend Nuxt non accessible"
fi

# Test 3: Connexion admin
echo "3. Test de connexion administrateur..."
LOGIN_RESPONSE=$(curl -s -H "Accept: application/json" -H "Content-Type: application/json" -X POST -d '{"email":"admin@bookyourcoach.com","password":"admin123"}' http://localhost:8081/api/auth/login)

if echo "$LOGIN_RESPONSE" | grep -q "token"; then
    echo "✅ Connexion admin réussie"
    TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    echo "   Token: ${TOKEN:0:20}..."
else
    echo "❌ Connexion admin échouée"
    echo "   Réponse: $LOGIN_RESPONSE"
fi

# Test 4: Accès aux routes admin
if [ ! -z "$TOKEN" ]; then
    echo "4. Test d'accès aux routes d'administration..."
    
    STATS_RESPONSE=$(curl -s -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" http://localhost:8081/api/admin/stats)
    if echo "$STATS_RESPONSE" | grep -q "users"; then
        echo "✅ Accès aux statistiques admin"
    else
        echo "❌ Échec d'accès aux statistiques"
    fi
    
    USERS_RESPONSE=$(curl -s -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" http://localhost:8081/api/admin/users)
    if echo "$USERS_RESPONSE" | grep -q "success"; then
        echo "✅ Accès à la gestion des utilisateurs"
    else
        echo "❌ Échec d'accès à la gestion des utilisateurs"
    fi
fi

echo
echo "=== Résumé ==="
echo "• API Backend: http://localhost:8081"
echo "• Frontend: http://localhost:3000"
echo "• Admin: admin@bookyourcoach.com / admin123"
echo
