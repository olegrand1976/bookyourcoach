# Données de Test - BookYourCoach

## 📋 Comptes de Test Disponibles

### 👨‍💼 Administrateur
- **Email:** admin@bookyourcoach.com
- **Mot de passe:** password123
- **Rôle:** admin
- **Fonctionnalités:** Accès complet à toutes les fonctionnalités

### 👨‍🏫 Enseignants
- **sophie.martin@bookyourcoach.com** - password123
- **sarah.johnson@test.com** - password123
- **michael.brown@test.com** - password123
- **lisa.davis@test.com** - password123
- **jean.dubois@bookyourcoach.com** - password123
- **marie.leroy@bookyourcoach.com** - password123
- **pierre.bernard@bookyourcoach.com** - password123
- **thomas.dubois@bookyourcoach.com** - password123

### 👨‍🎓 Étudiants
- **alice.durand@email.com** - password123
- **lucas.moreau@test.com** - password123
- **camille.petit@test.com** - password123
- **hugo.simon@test.com** - password123
- **bob.martin@email.com** - password123
- **charlotte.dupont@email.com** - password123
- **david.laurent@email.com** - password123
- **emma.rousseau@email.com** - password123

## 📊 Données Créées

### 🏃‍♂️ Types de Cours (18)
- Tennis
- Football
- Basketball
- Natation
- Yoga
- Fitness
- Danse
- Escalade
- Travail à pied
- Dressage
- Obstacle
- Endurance
- Vitesse
- Agility
- Obéissance
- Rallye
- Canicross
- Frisbee

### 📍 Lieux (15)
- Complexe Sportif Central (Paris)
- Gymnase Municipal (Lyon)
- Piscine Olympique (Marseille)
- Tennis Club Premium (Nice)
- Centre de Fitness (Bordeaux)
- + 10 autres lieux

### 📚 Leçons (177)
- 3-5 leçons par enseignant
- Horaires variés (9h-17h)
- Durées: 60-120 minutes
- Prix: 20-80€
- Statuts: confirmed, pending, cancelled

### ⏰ Disponibilités (139)
- 2-4 disponibilités par enseignant
- Horaires: 8h-21h
- Dates: prochaines 30 jours
- Lieux variés

## 🧪 Scénarios de Test

### 1. Connexion et Authentification
```bash
# Test connexion admin
Email: admin@bookyourcoach.com
Mot de passe: password123

# Test connexion enseignant
Email: sophie.martin@bookyourcoach.com
Mot de passe: password123

# Test connexion étudiant
Email: alice.durand@email.com
Mot de passe: password123
```

### 2. Fonctionnalités par Rôle

#### 👨‍💼 Administrateur
- ✅ Gestion des utilisateurs
- ✅ Gestion des cours
- ✅ Statistiques globales
- ✅ Paramètres de l'application

#### 👨‍🏫 Enseignant
- ✅ Création de leçons
- ✅ Gestion des disponibilités
- ✅ Consultation des réservations
- ✅ Profil et paramètres

#### 👨‍🎓 Étudiant
- ✅ Recherche d'enseignants
- ✅ Réservation de cours
- ✅ Historique des leçons
- ✅ Préférences de cours

## 🔧 Réinitialisation des Données

Pour réinitialiser les données de test :

```bash
# Se connecter au conteneur Laravel
docker exec -it bookyourcoach_app bash

# Vider la base de données
php artisan migrate:fresh

# Recréer les données de test
php artisan db:seed --class=TestDataSeeder
```

## 📱 Test Application Flutter

### Lancement
```bash
cd mobile
flutter run -d chrome --web-port=8080
```

### URL d'accès
- **Application Flutter:** http://localhost:8080
- **API Laravel:** http://localhost:8081/api

### Test de Connexion API
```bash
# Test de santé de l'API
curl http://localhost:8081/api/app-settings/public

# Test de connexion
curl -X POST http://localhost:8081/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@bookyourcoach.com","password":"password123"}'
```

## 🐛 Dépannage

### Problème de Connexion
1. Vérifier que les services Docker sont en cours
2. Vérifier les ports 8080 et 8081
3. Vérifier la base de données MySQL

### Problème d'Authentification
1. Vérifier que les utilisateurs existent dans la base
2. Vérifier les tokens d'authentification
3. Vérifier les permissions des rôles

### Problème de Données
1. Relancer le seeder de test
2. Vérifier les contraintes de clés étrangères
3. Vérifier les modèles Eloquent

## 📝 Notes Importantes

- Tous les mots de passe sont : `password123`
- Les données sont réinitialisées à chaque exécution du seeder
- Les horaires sont générés aléatoirement
- Les prix sont entre 20€ et 80€
- Les durées sont entre 60 et 120 minutes

## 🔄 Mise à Jour des Données

Pour ajouter de nouveaux utilisateurs ou données de test, modifier le fichier :
```
database/seeders/TestDataSeeder.php
```

Puis relancer :
```bash
docker exec bookyourcoach_app php artisan db:seed --class=TestDataSeeder
```
