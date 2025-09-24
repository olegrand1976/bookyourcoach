#!/bin/bash

# Script pour corriger le problème d'email des gestionnaires de clubs en production
# Problème : incohérence entre emails avec/sans accents

echo "🚀 Correction du problème d'email des gestionnaires de clubs en production..."

# Configuration
SERVER_USER="root"
SERVER_HOST="activibe.be"
SERVER_PATH="/var/www/activibe"
DB_NAME="activibe_production"
DB_USER="root"
DB_PASS="your_db_password_here"

echo ""
echo "🔍 Diagnostic du problème sur le serveur de production..."

# 1. Vérifier les emails existants en base
echo ""
echo "📊 Emails de gestionnaires existants en base :"
ssh $SERVER_USER@$SERVER_HOST "mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \"SELECT email, name FROM users WHERE role = 'club' ORDER BY email;\""

echo ""
echo "📊 Gestionnaires liés aux clubs :"
ssh $SERVER_USER@$SERVER_HOST "mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \"SELECT u.email, u.name, c.name as club_name FROM users u JOIN club_managers cm ON u.id = cm.user_id JOIN clubs c ON cm.club_id = c.id WHERE u.role = 'club';\""

echo ""
echo "🔧 Solutions possibles :"
echo "1. Créer un compte de test simple : manager@test-club.fr / password"
echo "2. Modifier le seeder pour créer des emails avec accents"
echo "3. Supprimer les comptes existants et relancer le seeder"

echo ""
echo "💡 Recommandation : Créer un compte de test simple pour éviter les problèmes d'encodage"
echo ""
echo "Voulez-vous créer un compte de test simple en production ? (y/n)"
read -r response

if [[ "$response" =~ ^[Yy]$ ]]; then
    echo ""
    echo "🔨 Création d'un compte de test simple en production..."
    
    # Créer un club de test simple
    ssh $SERVER_USER@$SERVER_HOST "mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \"INSERT INTO clubs (name, description, email, phone, street, street_number, city, postal_code, country, facilities, disciplines, max_students, subscription_price, is_active, created_at, updated_at) VALUES ('Club Test Simple', 'Club de test pour le dashboard', 'test@club-simple.fr', '+33 1 23 45 67 89', 'Rue Test', '1', 'Paris', '75001', 'France', '[\"manège\"]', '[\"dressage\"]', 50, 40.00, 1, NOW(), NOW());\""
    
    # Récupérer l'ID du club créé
    CLUB_ID=$(ssh $SERVER_USER@$SERVER_HOST "mysql -u $DB_USER -p$DB_PASS $DB_NAME -s -N -e \"SELECT id FROM clubs WHERE name = 'Club Test Simple';\"")
    
    if [ ! -z "$CLUB_ID" ]; then
        echo "✅ Club créé avec l'ID: $CLUB_ID"
        
        # Créer un utilisateur de test simple
        ssh $SERVER_USER@$SERVER_HOST "mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \"INSERT INTO users (name, first_name, last_name, email, password, role, phone, city, country, status, is_active, email_verified_at, created_at, updated_at) VALUES ('Manager Test', 'Manager', 'Test', 'manager@test-club.fr', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'club', '+33 1 23 45 67 89', 'Paris', 'France', 'active', 1, NOW(), NOW(), NOW());\""
        
        # Récupérer l'ID de l'utilisateur créé
        USER_ID=$(ssh $SERVER_USER@$SERVER_HOST "mysql -u $DB_USER -p$DB_PASS $DB_NAME -s -N -e \"SELECT id FROM users WHERE email = 'manager@test-club.fr';\"")
        
        if [ ! -z "$USER_ID" ]; then
            echo "✅ Utilisateur créé avec l'ID: $USER_ID"
            
            # Lier l'utilisateur au club
            ssh $SERVER_USER@$SERVER_HOST "mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \"INSERT INTO club_managers (club_id, user_id, role, created_at, updated_at) VALUES ($CLUB_ID, $USER_ID, 'owner', NOW(), NOW());\""
            
            echo "✅ Lien club-utilisateur créé"
            echo ""
            echo "🎉 Compte de test créé avec succès en production !"
            echo "📧 Email: manager@test-club.fr"
            echo "🔑 Mot de passe: password"
            echo ""
            echo "Vous pouvez maintenant vous connecter avec ces identifiants pour tester le dashboard."
        else
            echo "❌ Erreur lors de la création de l'utilisateur"
        fi
    else
        echo "❌ Erreur lors de la création du club"
    fi
else
    echo ""
    echo "ℹ️  Aucune action effectuée."
    echo ""
    echo "Pour résoudre le problème manuellement :"
    echo "1. Connectez-vous avec un email sans accents"
    echo "2. Ou modifiez le seeder pour créer des emails avec accents"
    echo "3. Ou supprimez les comptes existants et relancez le seeder"
fi

echo ""
echo "🔍 Vérification finale des données en production :"
ssh $SERVER_USER@$SERVER_HOST "mysql -u $DB_USER -p$DB_PASS $DB_NAME -e \"SELECT u.email, u.name, c.name as club_name FROM users u JOIN club_managers cm ON u.id = cm.user_id JOIN clubs c ON cm.club_id = c.id WHERE u.role = 'club';\""
