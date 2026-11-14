# Veille planning et génération des séries

## Contexte : incident du 12/11/2026

La nuit du 12/11/2026, les tâches `recurring-slots:generate-lessons` (02:00) et `recurring-slots:prolong` (02:30),
relancées la veille, ont créé 170 cours et repoussé la fin de 114 séries :

- séries **annulées** régénérées (le générateur ne filtrait pas le statut) ;
- séries **terminées depuis des mois** mais restées `active` rouvertes jusqu'en 2027 (prolong traitait toute date de fin
  comme un horizon glissant) — d'où des cours d'enseignants qui ne donnent plus cours ;
- aucun contrôle de conflit : créneaux surchargés (6 cours pour 4 places), élèves et enseignants en double ;
- rien de tracé dans l'historique des cours.

## Règles en place

**Génération** (`LegacyRecurringSlotService::generateLessonsForSlot`) — jamais de cours :
- pour une série non `active` ;
- à une date sautée (`skipped_dates`, posée par la suppression d'un cours) ou déjà occupée par un cours de la série ;
- en conflit : élève déjà pris, enseignant déjà pris, créneau du club complet (pic de cours simultanés ≥ `max_slots`).
  Les conflits sont renvoyés dans `conflicts` (type, date, heure, message) et journalisés.
- Chaque cours généré est tracé dans l'historique (`generated`, rôle `system`).

**Prolongation** (`ProlongRecurringSlotsService`) :
- série en cours : date de fin repoussée à l'horizon de 26 semaines (ou à la fin du « dernier abonnement ») ;
- série terminée : rouverte seulement si elle s'est arrêtée il y a au plus 8 semaines **et** que l'élève n'a pas de
  série active commencée depuis (changement d'enseignant ou d'horaire) ;
- série dont la fin a été voulue (`end_locked_at`, posé par `truncateFromDate` : suppression « toutes les suivantes »,
  fin de série depuis le planning) : jamais prolongée.

**Planification** : seule `recurring-slots:prolong` (02:30) génère la nuit. `recurring-slots:generate-lessons` n'est plus
planifiée (doublon) ; son déclencheur Cloud Scheduler `bookyourcoach-cron-generate-lessons` reste en pause.

**Simulation** : `--dry-run` de `recurring-slots:prolong` et `recurring-slots:generate-lessons` exécute le vrai traitement
dans une transaction annulée et liste les séries prolongées et les cours qui seraient créés. À lancer hors des heures
d'usage (verrous le temps du calcul).

## Veille planning (`planning:check-consistency`)

Chaque jour à 18:00 (Europe/Brussels), contrôle du planning du lendemain, club par club :

| Niveau | Contrôle |
|---|---|
| à corriger | créneau au-delà de `max_slots` ; enseignant ou élève à deux cours en même temps ; enseignant inactif dans le club ; cours généré (resté intact) sans série active ; cours généré réapparu après une suppression |
| à vérifier | cours hors des créneaux ouverts ou hors grille ; élève avec plusieurs cours le jour même dont un généré ; cours un jour de congé |

S'il y a au moins un point « à corriger » :
- e-mail aux responsables du club (`Club::stakeholderUsersNotifiableByMail`) ;
- issue GitHub (label `veille-planning`) **sans aucune donnée personnelle** (dépôt public), si
  `GITHUB_PLANNING_ISSUES_TOKEN` est configuré. Les issues sont traitées par Claude avec `/traiter-veille-planning`.

```bash
php artisan planning:check-consistency --dry-run                       # demain, sans envoi
php artisan planning:check-consistency --date=2026-11-14 --days=7 --dry-run   # rapport sur 7 jours
```

## Remise en état après une génération erronée

`recurring-slots:repair-night-run` (simulation par défaut, `--apply` pour écrire) :
- `--lessons=` : cours générés à supprimer, seulement s'ils sont intacts (note de génération, statut confirmé, jamais
  modifiés, aucune action tracée) — trace « Suppression », date sautée sur la série active, détachement de l'abonnement,
  recalcul des compteurs ;
- `--slot-ends=id:AAAA-MM-JJ,…` : séries à refermer à leur ancienne date de fin (verrouillée), jamais avant le dernier
  cours futur restant de la série.

Sur Cloud Run (séparateur `@` car les listes contiennent des virgules) :

```bash
gcloud run jobs execute bookyourcoach-cron-prolong --region=europe-west9 --wait \
  --args='^@^artisan@recurring-slots:repair-night-run@--lessons=1,2@--slot-ends=10:2026-03-08'
```

Toujours faire une sauvegarde avant `--apply` (`gcloud sql export sql …`).

## Rappels de la veille au soir (21h30)

Commande `lessons:send-eve-reminders` (Cloud Run Job `bookyourcoach-cron-eve-reminders`, 21:30 Europe/Brussels) :

- **Moniteurs** : chaque moniteur actif dans le club reçoit ses cours du lendemain (heure, élèves). Si un seul cours
  est prévu au premier horaire de la journée, son moniteur est prévenu qu'il sera seul : arriver un peu plus tôt pour
  ouvrir et préparer (objet préfixé « [Ouverture] »).
- **Élèves** : un e-mail par adresse (foyer regroupé) avec chaque cours du lendemain, l'heure et le moniteur. Les élèves
  ayant désactivé les notifications (`notifications_enabled = false`) ne reçoivent rien.
- Rien un jour de congé du club ni pour les cours annulés ; un destinataire n'est servi qu'une fois par jour (cache 36 h,
  `--force` pour renvoyer).
- `--dry-run` liste les destinataires (adresses masquées) sans rien envoyer ; `--date=AAAA-MM-JJ`, `--club=`,
  `--only=teachers|students`.

Remplace l'ancien rappel « 24 h avant » programmé à la création du cours : avec la file `sync` de la prod, il partait en
réalité au moment de la réservation.
