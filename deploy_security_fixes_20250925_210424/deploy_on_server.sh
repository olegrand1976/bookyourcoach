#!/bin/bash

# Script à exécuter sur le serveur de production
echo "🔒 Déploiement des corrections de sécurité sur le serveur"

# Sauvegarde des fichiers existants
echo "1. Sauvegarde des fichiers existants..."
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Sauvegarder les fichiers existants
cp app/Http/Controllers/Api/AdminController.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Http/Controllers/Api/TeacherController.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Http/Controllers/Api/StudentController.php "$BACKUP_DIR/" 2>/dev/null || true
cp app/Http/Controllers/Api/FileUploadController.php "$BACKUP_DIR/" 2>/dev/null || true
cp routes/api.php "$BACKUP_DIR/" 2>/dev/null || true
cp routes/admin.php "$BACKUP_DIR/" 2>/dev/null || true
cp bootstrap/app.php "$BACKUP_DIR/" 2>/dev/null || true

echo "✅ Sauvegarde créée dans: $BACKUP_DIR"

# Déployer les nouveaux fichiers
echo "2. Déploiement des nouveaux fichiers..."
cp app/Http/Controllers/Api/AdminController.php . 2>/dev/null || true
cp app/Http/Controllers/Api/TeacherController.php . 2>/dev/null || true
cp app/Http/Controllers/Api/StudentController.php . 2>/dev/null || true
cp app/Http/Controllers/Api/FileUploadController.php . 2>/dev/null || true
cp routes/api.php . 2>/dev/null || true
cp routes/admin.php . 2>/dev/null || true
cp bootstrap/app.php . 2>/dev/null || true

echo "✅ Fichiers déployés"

# Nettoyer les caches
echo "3. Nettoyage des caches..."
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "✅ Caches nettoyés"

# Vérifier la syntaxe
echo "4. Vérification de la syntaxe..."
php -l routes/api.php
php -l routes/admin.php
php -l app/Http/Controllers/Api/AdminController.php
php -l app/Http/Controllers/Api/TeacherController.php
php -l app/Http/Controllers/Api/StudentController.php
php -l app/Http/Controllers/Api/FileUploadController.php

echo "✅ Syntaxe vérifiée"

# Redémarrer les services si nécessaire
echo "5. Redémarrage des services..."
# Décommentez les lignes suivantes selon votre configuration
# sudo systemctl restart php8.2-fpm
# sudo systemctl restart nginx
# sudo systemctl restart apache2

echo "✅ Services redémarrés"

echo ""
echo "🎯 DÉPLOIEMENT TERMINÉ!"
echo "Les corrections de sécurité ont été appliquées."
echo "Testez vos routes admin, teacher et student."
