# Guide de Migration des Créneaux Récurrents

Ce document décrit la procédure pour migrer les anciens `SubscriptionRecurringSlot` vers le nouveau système basé sur RRULE.

## 📋 Prérequis

- Base de données accessible
- Accès au conteneur Docker backend
- Backup de la base de données effectué

## 🔍 Phase 1 : Vérification

### 1.1 Vérifier les données existantes

```bash
# Se connecter au conteneur backend
docker compose exec backend bash

# Vérifier le nombre de SubscriptionRecurringSlot à migrer
php artisan tinker
>>> \App\Models\SubscriptionRecurringSlot::where('status', '!=', 'cancelled')->count();
```

### 1.2 Vérifier que les migrations sont à jour

```bash
php artisan migrate:status
```

Toutes les migrations doivent être à jour, notamment :
- `2025_11_15_183705_create_recurring_slots_table.php`
- `2025_11_15_183706_create_recurring_slot_subscriptions_table.php`
- `2025_11_15_183707_create_lesson_recurring_slots_table.php`

## 🧪 Phase 2 : Test en mode Dry-Run

### 2.1 Exécuter la migration en mode dry-run

```bash
php artisan recurring-slots:migrate --dry-run
```

Cette commande affichera :
- Le nombre de créneaux à migrer
- Les détails de chaque créneau (sans les créer)
- Les éventuelles erreurs

### 2.2 Vérifier les résultats

Vérifiez que :
- ✅ Tous les créneaux actifs sont détectés
- ✅ Les RRULE sont correctement générés
- ✅ Les durées sont correctement calculées
- ✅ Aucune erreur critique n'est affichée

## 🚀 Phase 3 : Migration Réelle

### 3.1 Exécuter la migration

```bash
php artisan recurring-slots:migrate
```

La commande affichera une barre de progression et les statistiques finales.

### 3.2 Vérifier les résultats

```bash
php artisan tinker
```

```php
// Vérifier le nombre de RecurringSlot créés
\App\Models\RecurringSlot::count();

// Vérifier le nombre de liaisons créées
\App\Models\RecurringSlotSubscription::count();

// Vérifier que les lessons existantes sont liées
\App\Models\LessonRecurringSlot::count();

// Vérifier un créneau spécifique
$slot = \App\Models\RecurringSlot::first();
$slot->rrule; // Doit contenir une RRULE valide
$slot->activeSubscription; // Doit retourner une liaison active
```

## 🔄 Phase 4 : Génération Automatique des Lessons

### 4.1 Tester la génération en mode dry-run

```bash
php artisan recurring-slots:generate-lessons --dry-run
```

### 4.2 Générer les lessons pour une période spécifique

```bash
# Générer pour les 2 prochaines semaines
php artisan recurring-slots:generate-lessons \
  --start-date=$(date +%Y-%m-%d) \
  --end-date=$(date -d "+2 weeks" +%Y-%m-%d)
```

### 4.3 Générer pour un créneau spécifique

```bash
php artisan recurring-slots:generate-lessons --slot=1
```

### 4.4 Vérifier les lessons générées

```bash
php artisan tinker
```

```php
// Vérifier les lessons générées automatiquement
\App\Models\LessonRecurringSlot::where('generated_by', 'auto')->count();

// Vérifier une lesson spécifique
$lesson = \App\Models\Lesson::whereHas('lessonRecurringSlot', function($q) {
    $q->where('generated_by', 'auto');
})->first();

$lesson->lessonRecurringSlot->recurringSlot; // Le créneau récurrent
$lesson->lessonRecurringSlot->subscriptionInstance; // L'abonnement
```

## ⏰ Phase 5 : Configuration du Scheduler

### 5.1 Vérifier que le scheduler est actif

Le scheduler est configuré dans `routes/console.php` et s'exécute automatiquement si :
- Le worker `schedule:work` est actif (dans Docker)
- Ou un cron job est configuré : `* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1`

### 5.2 Commandes planifiées

- **Génération des lessons** : Tous les jours à 2h du matin
- **Expiration des liaisons** : Tous les jours à 3h du matin

### 5.3 Vérifier les logs

```bash
# Voir les logs du scheduler
docker compose logs backend | grep "Génération automatique"
docker compose logs backend | grep "Expiration automatique"
```

## 🔧 Dépannage

### Problème : Erreur "Class not found"

```bash
# Vider les caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Problème : RRULE invalide

Vérifiez que la bibliothèque `rlanvin/php-rrule` est installée :

```bash
composer show rlanvin/php-rrule
```

### Problème : Lessons non générées

1. Vérifier que le créneau est actif :
```php
$slot = \App\Models\RecurringSlot::find(1);
$slot->status; // Doit être 'active'
```

2. Vérifier qu'il y a un abonnement actif :
```php
$slot->activeSubscription; // Ne doit pas être null
```

3. Vérifier les dates :
```php
$slot->activeSubscription->start_date; // Doit être <= aujourd'hui
$slot->activeSubscription->end_date; // Doit être >= aujourd'hui
```

### Problème : Doublons de lessons

Les doublons sont automatiquement évités par le service. Si vous en voyez :

1. Vérifier la méthode `lessonExistsForSlotAndDate` dans `RecurringSlotService`
2. Vérifier les contraintes d'unicité dans la base de données

## 📊 Statistiques Post-Migration

### Compter les créneaux migrés

```php
\App\Models\RecurringSlot::count();
```

### Compter les liaisons actives

```php
\App\Models\RecurringSlotSubscription::where('status', 'active')->count();
```

### Compter les lessons générées automatiquement

```php
\App\Models\LessonRecurringSlot::where('generated_by', 'auto')->count();
```

## ✅ Checklist de Migration

- [ ] Backup de la base de données effectué
- [ ] Migrations à jour
- [ ] Test dry-run réussi
- [ ] Migration réelle exécutée
- [ ] Vérification des données migrées
- [ ] Test de génération de lessons
- [ ] Scheduler configuré et actif
- [ ] Logs vérifiés
- [ ] Documentation mise à jour

## 🆘 Support

En cas de problème, consulter :
- Les logs Laravel : `storage/logs/laravel.log`
- Les logs Docker : `docker compose logs backend`
- La documentation technique : `docs/PLAN_MISE_EN_PLACE_CRENEAUX_RECURRENTS.md`

