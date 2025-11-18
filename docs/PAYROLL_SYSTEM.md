# Système de Calcul de Paie des Enseignants

## Vue d'ensemble

Ce système permet de générer des rapports de paie mensuels pour les enseignants en calculant les commissions dues sur les abonnements Type 1 (standard) et Type 2 (legacy).

## Structure de la Base de Données

### Migration : `add_commission_fields_to_subscription_instances_table`

Ajoute les champs suivants à la table `subscription_instances` :

- **`est_legacy`** (boolean, default: false) : Flag pour distinguer Type 1 (false) et Type 2 (true)
- **`date_paiement`** (date, nullable) : Date de paiement/renouvellement de l'abonnement
- **`montant`** (decimal 10,2, nullable) : Montant payé pour cet abonnement (base de calcul de commission)
- **`teacher_id`** (foreign key, nullable) : Enseignant qui doit recevoir la commission

### Index créés

- Index composite sur `(date_paiement, est_legacy)` pour les recherches par période
- Index sur `teacher_id` pour les recherches par enseignant

## Service : `CommissionCalculationService`

### Règles de Calcul

#### Type 1 (Standard) - `est_legacy = false`
- **Taux de commission** : 100% (configurable via `TYPE1_COMMISSION_RATE`)
- **Formule** : `Commission = montant × 1.00` (montant complet)

#### Type 2 (Legacy) - `est_legacy = true`
- **Taux de commission** : 100% (configurable via `TYPE2_COMMISSION_RATE`)
- **Formule** : `Commission = montant × 1.00` (montant complet)
- **Note** : Identique au Type 1. Le code Type 2 peut être supprimé lorsque tous les abonnements legacy seront expirés.

### Méthodes principales

#### `calculateCommission(SubscriptionInstance $instance): float`
Calcule la commission pour un abonnement selon son type.

#### `generatePayrollReport(int $year, int $month): array`
Génère un rapport complet pour une période donnée.

**Logique de collecte** :
1. Sélectionne tous les `SubscriptionInstance` avec `date_paiement` dans la période
2. Filtre les abonnements avec `montant > 0` et `status` actif/complété
3. Agrége les commissions par enseignant

**Détermination de l'enseignant** :
1. Priorité 1 : `teacher_id` direct sur l'abonnement
2. Priorité 2 : Enseignant du premier cours lié à l'abonnement

## Commande Artisan : `payroll:generate`

### Usage

```bash
# Générer le rapport pour le mois/année courants
php artisan payroll:generate

# Générer le rapport pour novembre 2025
php artisan payroll:generate --year=2025 --month=11

# Générer en format CSV et sauvegarder dans un fichier
php artisan payroll:generate --year=2025 --month=11 --output=csv --file=rapport_novembre_2025.csv

# Générer en format JSON
php artisan payroll:generate --year=2025 --month=11 --output=json
```

### Options

- `--year` : Année (ex: 2025). Par défaut : année courante
- `--month` : Mois (1-12). Par défaut : mois courant
- `--output` : Format de sortie (`json`, `table`, `csv`). Par défaut : `json`
- `--file` : Chemin du fichier de sortie (optionnel). Si non spécifié, affiche dans la console

### Format de Sortie JSON

```json
{
  "enseignant_id_123": {
    "enseignant_id": 123,
    "nom_enseignant": "Jean Dupont",
    "total_commissions_type1": 1250.75,
    "total_commissions_type2": 300.00,
    "total_a_payer": 1550.75
  },
  "enseignant_id_456": {
    "enseignant_id": 456,
    "nom_enseignant": "Marie Durand",
    "total_commissions_type1": 800.00,
    "total_commissions_type2": 0.00,
    "total_a_payer": 800.00
  }
}
```

## Mise en Place

### 1. Exécuter la migration

```bash
php artisan migrate
```

### 2. Remplir les données existantes (si nécessaire)

Si vous avez des abonnements existants, vous devrez :

1. Définir `est_legacy` selon le type d'abonnement
2. Définir `date_paiement` (date de création ou de renouvellement)
3. Définir `montant` (prix de l'abonnement)
4. Définir `teacher_id` (optionnel, sera déterminé automatiquement si null)

Exemple de script de migration des données :

```php
// Dans tinker ou une commande artisan
$instances = SubscriptionInstance::whereNull('date_paiement')->get();

foreach ($instances as $instance) {
    $instance->update([
        'est_legacy' => false, // ou true selon votre logique
        'date_paiement' => $instance->started_at ?? $instance->created_at,
        'montant' => $instance->subscription->price ?? 0,
        // teacher_id sera déterminé automatiquement si null
    ]);
}
```

## Maintenance et Évolution

### Modifier les taux de commission

Modifier les constantes dans `CommissionCalculationService` :

```php
private const TYPE1_COMMISSION_RATE = 1.00; // 100% (montant complet)
private const TYPE2_COMMISSION_RATE = 1.00; // 100% (montant complet)
```

**Note actuelle** : Les deux types utilisent 100% (montant complet). Les taux sont identiques mais la distinction Type 1/Type 2 est maintenue pour des raisons comptables et de suivi.

### Supprimer le support Type 2 (Legacy)

Lorsque tous les abonnements Type 2 seront expirés :

1. Supprimer la méthode `calculateType2Commission()`
2. Supprimer la constante `TYPE2_COMMISSION_RATE`
3. Simplifier la méthode `calculateCommission()` pour ne gérer que Type 1
4. Supprimer le champ `est_legacy` de la base de données (migration de rollback)

### Ajouter de nouveaux types d'abonnements

1. Ajouter un nouveau champ dans la migration (ex: `subscription_type` enum)
2. Ajouter une nouvelle méthode de calcul dans le service
3. Modifier la logique de `calculateCommission()` pour gérer le nouveau type

## Tests

Pour tester le système :

```bash
# Créer des données de test
php artisan tinker

# Créer un abonnement Type 1
$instance = SubscriptionInstance::create([
    'subscription_id' => 1,
    'est_legacy' => false,
    'date_paiement' => '2025-11-15',
    'montant' => 1000.00,
    'teacher_id' => 1,
    'started_at' => '2025-11-01',
    'status' => 'active',
]);

# Générer le rapport
php artisan payroll:generate --year=2025 --month=11
```

## Notes Importantes

1. **Séparation Type 1 / Type 2** : Le rapport sépare toujours les totaux Type 1 et Type 2 pour des raisons comptables, même si le total final est calculé.

2. **Détermination de l'enseignant** : Si `teacher_id` n'est pas défini sur l'abonnement, le système cherche l'enseignant du premier cours lié. Si aucun enseignant n'est trouvé, l'abonnement est ignoré dans le rapport.

3. **Précision des calculs** : Tous les montants sont arrondis à 2 décimales pour éviter les erreurs de précision.

4. **Performance** : Le service utilise des index sur `date_paiement` et `teacher_id` pour optimiser les requêtes.

