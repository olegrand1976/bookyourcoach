---
description: Traiter les issues « veille-planning » (incohérences du planning détectées la veille) en analysant la prod en lecture seule
---

Traite les issues GitHub ouvertes portant le label `veille-planning` (créées par `planning:check-consistency`).
Procédure détaillée : [docs/VEILLE_PLANNING.md](../../docs/VEILLE_PLANNING.md).

1. Lister : `gh issue list --label veille-planning --state open`.
2. Pour chaque issue, ouvrir un accès **lecture seule** à la base de prod (cloud-sql-proxy, session
   `SET SESSION TRANSACTION READ ONLY`, uniquement des `SELECT`) et analyser les cours listés :
   `lessons`, `lesson_action_logs`, `lesson_movement_histories`, `subscription_recurring_slots`, `club_open_slots`.
3. Identifier la cause (génération automatique, action club, action élève, bug) et proposer le correctif :
   code (avec test) et/ou données. **Aucune écriture en base sans validation explicite de l'utilisateur**,
   et toujours après une sauvegarde (`gcloud sql export`), via une commande artisan tracée — jamais en SQL brut.
4. Commenter l'issue avec la cause et la décision, **sans aucune donnée personnelle** (dépôt public :
   ni nom, ni e-mail ; uniquement identifiants, heures, types). Fermer quand c'est corrigé.
