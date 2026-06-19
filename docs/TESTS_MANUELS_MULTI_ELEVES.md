# Tests manuels — Corrections décompte / multi-élèves

Checklist de validation manuelle suite au plan **Fix décompte multi-élèves** (consommation cours collectifs, couverture d'abonnement, affichage foyer, annulation ciblée, teacher dashboard).

## Pré-requis

- [ ] Club avec au moins un abonnement (template couvrant un type de cours).
- [ ] Compte foyer (parent) avec **2 enfants liés** (`student_family_links`).
- [ ] Comptes enseignant et club opérationnels.
- [ ] Worker de queue actif (la consommation passe par `ProcessLessonPostCreationJob`).

## 1. Consommation des cours collectifs (pivot-only)

- [ ] Cours collectif **sans élève principal** (participants pivot uniquement), bénéficiaires d'un abonnement actif → **1 place débitée**.
- [ ] Passage d'un cours pivot-only en `completed` → la place est consommée.
- [ ] Régression mono-élève (cours avec `student_id`) → 1 place débitée comme avant.
- [ ] Cours collectif sans aucun abonnement actif → aucune consommation, pas d'erreur.

## 2. Couverture d'abonnement (historique élève, vue club)

- [ ] Cours futur déjà rattaché à un abonnement → affiché **« couvert »**.
- [ ] Abonnement **plein** (toutes places réservées) + cours futur non rattaché → **« non couvert »**.
- [ ] Cours collectif débité sur l'abonnement de A → l'enfant B (non débité) n'apparaît **pas** « couvert » à tort.
- [ ] Abonnement expirant **avant** le cours → « non couvert » (non-régression).
- [ ] Type de cours **non couvert** par le template → « non couvert » (non-régression).

## 3. Décompte abonnements (front club `/club/subscriptions`)

- [ ] Nombre de cours « consommés » affiché == `lessons_used` serveur.
- [ ] Annulation comptée (`cancellation_count_in_subscription = true`) → compteur reste incrémenté.
- [ ] Cours futurs réservés → reflétés dans le compteur comme côté serveur.
- [ ] Édition manuelle de `manual_lessons_used` → « nouveau total » recalculé correctement.

## 4. Affichage foyer + attribution élève

- [ ] Vue « tous » (planning + dashboard élève) : chaque cours porte le **nom de l'enfant concerné**.
- [ ] Cours où l'enfant du foyer est **seulement bénéficiaire d'abonnement** (ni `student_id` ni participant pivot) → bon enfant étiqueté.
- [ ] Historique foyer (`StudentHistoryModal` / lesson-history) : même attribution visible.
- [ ] Vue mono-élève (un seul enfant sélectionné) → affichage normal (pas d'étiquette d'attribution requise).

## 5. Annulation ciblée d'un cours collectif (côté élève/parent)

- [ ] Cours collectif 2 enfants, l'un annule → l'enfant est **retiré**, le cours **reste actif** pour l'autre.
- [ ] L'abonnement de l'annulant est **libéré** ; celui de l'autre **intact**.
- [ ] Si l'annulant était l'élève principal (`student_id`) → le cours bascule sur un participant restant.
- [ ] Annulation du **dernier** participant → le cours passe `cancelled`.
- [ ] Cours individuel : annulation classique → `cancelled` + flags (délai 8h, raison médicale/certificat) (non-régression).

### Limites connues à contrôler

- [ ] Le club/enseignant **n'est pas notifié** du retrait d'un participant (à confirmer si acceptable).
- [ ] Retrait collectif **tardif** : aucun justificatif demandé, non compté (asymétrie volontaire vs annulation individuelle).
- [ ] Après retrait du bénéficiaire débité : le cours maintenu n'est **plus rattaché** à un abonnement → vérifier l'affichage de couverture côté club pour l'enfant restant.

## 6. Création de cours côté enseignant (teacher dashboard)

- [ ] Création d'un cours avec élève(s) depuis le dashboard prof → **une place d'abonnement est désormais débitée** (changement de comportement à valider).
- [ ] Création prof sans abonnement actif → cours créé, aucune consommation, pas d'erreur.
- [ ] Cours prof multi-participants → consommation cohérente (1 abonnement débité, modèle mono-débit).

## 7. Transverse

- [ ] Isolation multi-tenant : un parent ne voit/n'annule que les cours des enfants de **son** foyer.
- [ ] Isolation multi-tenant : un club ne voit que **ses** élèves.
- [ ] Queue : worker actif, le débit apparaît bien après traitement asynchrone du job.
