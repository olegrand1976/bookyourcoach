#!/bin/bash

# Test de l'API BookYourCoach

echo "=== Test de l'API d'inscription ==="

# Test d'inscription
echo "Test d'inscription d'un nouvel utilisateur..."
RESPONSE=$(curl -s -X POST http://localhost:8081/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }')

echo "Réponse: $RESPONSE"

# Test de connexion
echo -e "\n=== Test de connexion ==="
LOGIN_RESPONSE=$(curl -s -X POST http://localhost:8081/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }')

echo "Réponse: $LOGIN_RESPONSE"

# Test de la liste des utilisateurs avec token
echo -e "\n=== Test de récupération des utilisateurs ==="
if echo "$LOGIN_RESPONSE" | grep -q "token"; then
    TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    USERS_RESPONSE=$(curl -s -X GET http://localhost:8081/api/users \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -H "Authorization: Bearer $TOKEN")
    echo "Réponse: $USERS_RESPONSE"
else
    echo "Échec de récupération du token"
fi
