#!/bin/bash

# Script pour diagnostiquer le problème du dashboard des clubs en production
echo "🔍 Diagnostic du problème du dashboard des clubs en production"
echo ""

# Test 1: Vérifier que l'API répond
echo "📡 Test 1: Vérification de l'API..."
API_RESPONSE=$(curl -s -w "\n%{http_code}" -X GET https://activibe.be/api/club/dashboard \
  -H "Authorization: Bearer 115|JZxPNRnZLoAADLRZvPcZOgc1PM2sS91xo002524Z6ce84c93" \
  -H "Content-Type: application/json")

HTTP_CODE=$(echo "$API_RESPONSE" | tail -n1)
BODY=$(echo "$API_RESPONSE" | head -n -1)

echo "Code HTTP: $HTTP_CODE"
echo "Réponse: $BODY"
echo ""

# Test 2: Vérifier que l'API auth fonctionne
echo "📡 Test 2: Vérification de l'API auth..."
AUTH_RESPONSE=$(curl -s -w "\n%{http_code}" -X GET https://activibe.be/api/auth/user \
  -H "Authorization: Bearer 115|JZxPNRnZLoAADLRZvPcZOgc1PM2sS91xo002524Z6ce84c93" \
  -H "Content-Type: application/json")

AUTH_HTTP_CODE=$(echo "$AUTH_RESPONSE" | tail -n1)
AUTH_BODY=$(echo "$AUTH_RESPONSE" | head -n -1)

echo "Code HTTP: $AUTH_HTTP_CODE"
echo "Réponse: $AUTH_BODY"
echo ""

# Test 3: Vérifier les routes disponibles
echo "📡 Test 3: Vérification des routes..."
ROUTES_RESPONSE=$(curl -s -w "\n%{http_code}" -X GET https://activibe.be/api/ \
  -H "Content-Type: application/json")

ROUTES_HTTP_CODE=$(echo "$ROUTES_RESPONSE" | tail -n1)
ROUTES_BODY=$(echo "$ROUTES_RESPONSE" | head -n -1)

echo "Code HTTP: $ROUTES_HTTP_CODE"
echo "Réponse: $ROUTES_BODY"
echo ""

echo "🏁 Diagnostic terminé"
echo ""
echo "💡 Solutions possibles:"
echo "1. Si l'API retourne 404: Le contrôleur n'est pas déployé"
echo "2. Si l'API retourne 401: Problème d'authentification"
echo "3. Si l'API retourne 500: Erreur serveur"
echo "4. Si l'API retourne 200 mais vide: Pas de données de clubs"
