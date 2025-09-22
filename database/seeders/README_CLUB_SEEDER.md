# Seeder des Données de Test pour les Clubs

## Description

Le `ClubTestDataSeeder` crée des données de test complètes pour tester le dashboard des clubs. Il génère :

- **4 clubs équestres** avec des informations réalistes
- **4 gestionnaires de clubs** (utilisateurs avec le rôle 'club')
- **8-12 enseignants** liés aux clubs via la table `club_teachers`
- **40-60 étudiants** liés aux clubs via la table `club_students`
- **100+ cours** répartis sur 4 semaines pour tester les statistiques

## Clubs Créés

1. **Club Équestre de la Vallée Dorée** (Fontainebleau)
   - Club familial, idéal pour débuter
   - Disciplines: dressage, saut d'obstacles, équitation de loisir
   - Prix abonnement: 45€

2. **Centre Équestre des Étoiles** (Chantilly)
   - Centre moderne spécialisé compétition
   - Disciplines: dressage, saut d'obstacles, concours complet, voltige
   - Prix abonnement: 65€

3. **Poney Club des Petits Cavaliers** (Rouen)
   - Spécialisé dans l'initiation des enfants
   - Disciplines: équitation de loisir, jeux équestres, balades
   - Prix abonnement: 35€

4. **Haras de la Côte d'Azur** (Nice)
   - Haras prestigieux combinant tradition et modernité
   - Disciplines: dressage, saut d'obstacles, équitation western, endurance
   - Prix abonnement: 85€

## Utilisation

### Exécution du seeder

```bash
# Via Docker
docker-compose -f docker-compose.local.yml exec -T backend php artisan db:seed --class=ClubTestDataSeeder

# Via le script dédié
./scripts/seed-club-data.sh
```

### Comptes de test créés

Tous les gestionnaires de clubs ont le mot de passe : `password`

- `manager@club-equestre-de-la-vallee-doree.fr`
- `manager@centre-equestre-des-etoiles.fr`
- `manager@poney-club-des-petits-cavaliers.fr`
- `manager@haras-de-la-cote-dazur.fr`

## Structure des Données

### Tables principales
- `clubs` : Informations des clubs
- `users` : Gestionnaires avec rôle 'club'
- `teachers` : Enseignants liés aux clubs
- `students` : Étudiants liés aux clubs
- `lessons` : Cours créés pour les statistiques

### Tables de liaison
- `club_managers` : Liaison clubs ↔ gestionnaires
- `club_teachers` : Liaison clubs ↔ enseignants
- `club_students` : Liaison clubs ↔ étudiants

## Données Générées

- **Clubs** : 4
- **Gestionnaires** : 4
- **Enseignants** : 8-12
- **Étudiants** : 40-60
- **Cours** : 100+
- **Relations** : Toutes les liaisons nécessaires

## Notes Techniques

- Le seeder vérifie l'existence des données avant création (évite les doublons)
- Les champs JSON (`facilities`, `disciplines`) sont correctement encodés
- Les statuts des cours respectent les contraintes de la base de données
- Les relations many-to-many sont correctement établies

## Intégration

Le seeder est automatiquement appelé par le `DatabaseSeeder` principal lors de l'exécution de :

```bash
php artisan db:seed
```
