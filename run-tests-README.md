# Script de lancement des tests

Ce script permet de lancer tous les tests du projet et d'obtenir un résumé détaillé avec les statistiques par catégorie.

## Utilisation

### Lancement de base
```bash
./run-tests.sh
```

### Options disponibles

#### Filtrer les tests par pattern
```bash
./run-tests.sh --filter=ClubTest
./run-tests.sh --filter="Subscription|Teacher"
```

#### Arrêter au premier échec
```bash
./run-tests.sh --stop-on-failure
```

#### Combinaison d'options
```bash
./run-tests.sh --filter=ClubTest --stop-on-failure
```

## Résumé généré

Le script génère un résumé complet avec :

### 1. Résumé global
- Total de tests exécutés
- Nombre de tests réussis
- Nombre de tests échoués
- Nombre de tests ignorés (skipped)
- Nombre d'avertissements

### 2. Résultats par type de test
- **Unit** : Tests unitaires
- **Feature** : Tests d'intégration/feature

### 3. Résultats par modèle/service
- **Club** : Tests du modèle Club
- **Teacher** : Tests du modèle Teacher
- **Student** : Tests du modèle Student
- **Subscription** : Tests liés aux abonnements (Subscription, SubscriptionInstance, SubscriptionTemplate, SubscriptionRecurringSlot)
- **Planning** : Tests du contrôleur ClubPlanningController
- **Services** : Tests des services (LegacyRecurringSlotService, etc.)
- **Commands** : Tests des commandes Artisan (ConsumePastLessonsCommand, etc.)

### 4. Liste des tests échoués
Affichage détaillé de tous les tests qui ont échoué avec leur nom complet.

### 5. Liste des tests ignorés
Affichage de tous les tests qui ont été ignorés (skipped) avec leur raison.

### 6. Taux de réussite
Pourcentage de tests réussis par rapport au total.

## Fichiers de sortie

Le script génère un fichier temporaire contenant la sortie complète des tests :
- Emplacement : `/tmp/test_results_YYYYMMDD_HHMMSS.txt`
- Contenu : Sortie complète de la commande `php artisan test`

## Codes de sortie

- `0` : Tous les tests sont passés
- `1` : Au moins un test a échoué

## Exemples d'utilisation

### Lancer tous les tests
```bash
./run-tests.sh
```

### Lancer uniquement les tests du modèle Club
```bash
./run-tests.sh --filter=ClubTest
```

### Lancer les tests et s'arrêter au premier échec
```bash
./run-tests.sh --stop-on-failure
```

### Lancer les tests avec un filtre et arrêter au premier échec
```bash
./run-tests.sh --filter="Subscription|Teacher" --stop-on-failure
```

## Intégration CI/CD

Le script peut être utilisé dans un pipeline CI/CD :

```yaml
# Exemple pour GitHub Actions
- name: Run tests
  run: ./run-tests.sh
```

Le code de sortie du script indiquera si les tests ont réussi ou échoué.

## Personnalisation

Pour modifier les catégories analysées, éditez la section "Analyser par modèle/service" dans le script.

Pour ajouter de nouvelles catégories, ajoutez des lignes similaires à :
```bash
NEW_CATEGORY_PASSED=$(grep -E "NewCategoryTest.*✓" "$OUTPUT_FILE" | wc -l || echo "0")
NEW_CATEGORY_FAILED=$(grep -E "NewCategoryTest.*✗" "$OUTPUT_FILE" | wc -l || echo "0")
```

Puis ajoutez-les dans la section d'affichage des résultats.

