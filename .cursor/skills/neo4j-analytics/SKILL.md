---
name: neo4j-analytics
description: Manipule les données analytiques via Neo4j. Utilise Neo4jService pour les requêtes Cypher, Neo4jSyncService pour la synchro MySQL→Neo4j. Optimise les requêtes pour analyser les relations utilisateurs, clubs et spécialités des enseignants. À utiliser pour analyses graphe, métriques relationnelles, recommandations ou quand l'utilisateur mentionne Neo4j, Cypher ou données analytiques.
---

# Données analytiques Neo4j (BookYourCoach)

## Règle principale

Pour toute **manipulation de données analytiques** (métriques, relations, recommandations, analyses graphe) :

1. **Requêtes et analyses** → `App\Services\Neo4jService` (méthode `run($query, $parameters)`).
2. **Synchronisation des données** → `App\Services\Neo4jSyncService` (MySQL → Neo4j). Les données sources sont dans MySQL ; Neo4j est une copie pour l’analyse.
3. **Analyses prédéfinies** → réutiliser ou étendre `App\Services\Neo4jAnalysisService` (relations User/Club, enseignants par spécialité, contrats, etc.).

Ne pas interroger MySQL pour des analyses de relations complexes : passer par Neo4j.

## Modèle graphe (synchronisé par Neo4jSyncService)

| Nœud    | Label     | Propriétés utiles |
|---------|-----------|-------------------|
| User    | `User`    | id, email, name, role, first_name, last_name, city, … |
| Club    | `Club`    | id, name, city, postal_code, … |
| Teacher | `Teacher`| id, user_id, bio, experience_years, hourly_rate, **specialties** (tableau) |
| Contract| `Contract`| id, teacher_id, club_id, type, status, start_date, end_date, hourly_rate, hours_per_week |

**Relations :**

- `(User)-[:MEMBERSHIP]->(Club)`
- `(Teacher)-[:IS_TEACHER]->(User)`
- `(Contract)-[:HAS_CONTRACT]->(Teacher)`
- `(Contract)-[:WORKING_FOR]->(Club)`

## Optimisation des requêtes Cypher

- **Index** : privilégier les propriétés indexées dans `config/neo4j.php` (User : email, role ; Club : name, city) dans les `WHERE` / `MATCH`.
- **Paramètres** : toujours passer les valeurs via `$param` et le 2e argument de `Neo4jService::run($query, $parameters)` pour éviter l’injection et permettre le cache de plan.
- **Limiter le volume** : utiliser `LIMIT` (et éventuellement `SKIP`) sur les requêtes exploratoires ou listes.
- **Éviter les full scans** : ancrer les chemins sur un nœud identifié (ex. `MATCH (c:Club {id: $club_id})`) avant de traverser les relations.
- **Spécialités enseignants** : `Teacher.specialties` est un tableau ; utiliser `UNWIND t.specialties AS specialty` pour agréger par spécialité, ou `ANY(s IN t.specialties WHERE s IN $list)` pour filtrer.

## Exemples d’usage

**Injection du service (Laravel) :**

```php
use App\Services\Neo4jService;

public function __construct(Neo4jService $neo4j)
{
    $this->neo4j = $neo4j;
}
```

**Requête Cypher paramétrée :**

```php
$results = $this->neo4j->run(
    "MATCH (u:User)-[:MEMBERSHIP]->(c:Club {id: \$club_id})
     RETURN u.name AS name, u.email AS email
     LIMIT \$limit",
    ['club_id' => $clubId, 'limit' => 50]
);
```

**Analyses existantes (Neo4jAnalysisService) :**

- `analyzeUserClubRelations()` — membres par club
- `analyzeTeachersBySpecialty()` — enseignants par spécialité (UNWIND sur `specialties`)
- `analyzeTeacherClubRelations()` — enseignants par club
- `analyzeMostDemandedSpecialties()` — spécialités les plus demandées
- `recommendTeachersForClub($clubId, $preferredSpecialties)` — recommandations

Pour une nouvelle métrique relationnelle, ajouter une méthode dans `Neo4jAnalysisService` qui s’appuie sur `Neo4jService::run()`.

## Synchronisation

- **Full sync** : `Neo4jSyncService::syncAll()` (users, clubs, teachers, contracts, puis relations).
- **Sync ciblée** : `syncUser(User $user)`, `deleteUser(int $userId)`.
- Commande console : `Neo4jSyncCommand` / `SyncNeo4jData` (voir `app/Console/Commands`).

Les données analytiques à jour dépendent de cette synchro ; pour du temps réel sur une entité précise, utiliser MySQL.
