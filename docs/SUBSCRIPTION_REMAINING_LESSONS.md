# Cours restants par abonnement

## Deux métriques distinctes

| Champ API | Formule | Usage |
|-----------|---------|-------|
| `remaining_consumed` / `remaining_lessons` | `total_available_lessons - lessons_used` | Cours non encore **consommés** (passés débités + `manual_lessons_used`) |
| `remaining_bookable` | `getRemainingAttachmentSlots()` | Places encore **réservables** (inclut les cours futurs déjà attachés) |

`lessons_used = manual_lessons_used + cours passés consommés + annulations tardives comptées`.

Les cours futurs attachés **bloquent** la réservation sans augmenter `lessons_used` tant qu'ils ne sont pas passés.

## Recalcul

- Méthode centrale : `SubscriptionInstance::recalculateLessonsUsed()` (idempotente, source = pivot `subscription_lessons`).
- API club : `POST /api/club/subscriptions/recalculate`
  - Par défaut : instances `active` uniquement + liaison des cours orphelins.
  - `?include_inactive=1` : recalcule aussi `completed`, `expired`, `cancelled` (sans liaison automatique).
  - Réponse : `past_overflow[]` si les cours passés seuls dépassent la capacité (arbitrage manuel).
- Artisan : `subscriptions:consume-past-lessons`, `subscriptions:repair-counters`.

## Abonnement familial

Plusieurs élèves sur **une** instance (`subscription_instance_students`) : pool partagé. Seuls les cours dont un participant est bénéficiaire sont comptés.

## Cours collectif — limite structurelle

- Contrainte DB : `UNIQUE(lesson_id)` sur `subscription_lessons` → un cours ne peut débiter qu'**un seul** abonnement.
- Consommation FIFO par date de création d'instance.
- **Retrait anticipé** d'un participant sur cours maintenu : l'abonnement de l'élève qui part est libéré, le cours continue pour les autres — non compté comme annulation tardive.
- **Annulation tardive comptée** (`cancellation_count_in_subscription`) : appliquée au niveau du **cours entier**, pas par participant. On ne peut pas compter tardivement l'annulation d'un seul enfant si le cours collectif est maintenu pour les autres.

Évolution possible : compteur par participant (hors périmètre actuel).

## Annulations

| Situation | Effet |
|-----------|-------|
| Annulation à temps | Pivot détaché → crédit rendu |
| Annulation tardive sans certificat valide | Pivot conservé → compté |
| Certificat médical refusé / dossier clos | Compté après revue club |

Voir `LessonObserver`, `DashboardController`, `CancellationCertificateReviewService`.
