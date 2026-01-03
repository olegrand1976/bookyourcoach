# Plan de Mise en Place : Créneaux Récurrents avec Blocage Long Terme

## 📋 Table des Matières

1. [Analyse de l'Existant](#analyse-de-lexistant)
2. [Comparaison avec la Proposition](#comparaison-avec-la-proposition)
3. [Architecture Proposée](#architecture-proposée)
4. [Plan de Migration](#plan-de-migration)
5. [Risques et Points d'Attention](#risques-et-points-dattention)
6. [Étapes de Mise en Place](#étapes-de-mise-en-place)
7. [Validation](#validation)

---

## 1. Analyse de l'Existant

### 1.1 Structure Actuelle

**Modèles existants :**
- ✅ `Subscription` : Modèle d'abonnement (template)
- ✅ `SubscriptionInstance` : Instance d'abonnement achetée par un élève
- ✅ `Lesson` : Cours réel (instance de cours)
- ✅ `SubscriptionRecurringSlot` : Créneaux récurrents (existe mais basique)

**Tables de liaison :**
- ✅ `subscription_lessons` : Lie les lessons aux subscription_instances
- ✅ `subscription_instance_students` : Lie les élèves aux instances
- ✅ `subscription_recurring_slots` : Créneaux récurrents (structure simple)

### 1.2 Limitations Actuelles

❌ **Pas de séparation claire entre blocage et consommation**
- Les créneaux récurrents sont liés à une `SubscriptionInstance`
- Si l'abonnement expire, le créneau disparaît (pas de blocage long terme)

❌ **Récurrence limitée**
- Structure basique : `day_of_week`, `start_time`, `end_time`, `start_date`, `end_date`
- Pas de support pour "un samedi sur deux" ou récurrences complexes
- Pas de champ `rrule` (iCalendar RRULE)

❌ **Projection manuelle**
- Les cours doivent être créés manuellement
- Pas de génération automatique basée sur la récurrence et la validité de l'abonnement

❌ **Gestion des pauses**
- Pas de mécanisme pour mettre en pause un créneau récurrent sans le supprimer

---

## 2. Comparaison avec la Proposition

### 2.1 Correspondances

| Proposition | Existant | État |
|------------|----------|------|
| `Abonnement` | `Subscription` + `SubscriptionInstance` | ✅ Existe (mais séparé) |
| `SlotRecurrent` | `SubscriptionRecurringSlot` | ⚠️ Existe mais incomplet |
| `OccurrenceCours` | `Lesson` | ✅ Existe |
| `RegleRecurrence` (RRULE) | ❌ Absent | ❌ À ajouter |
| Blocage long terme | ❌ Absent | ❌ À implémenter |

### 2.2 Écarts Principaux

1. **Séparation Blocage/Consommation**
   - **Proposition** : `SlotRecurrent` appartient à l'élève (indépendant de l'abonnement)
   - **Actuel** : `SubscriptionRecurringSlot` lié à `SubscriptionInstance` (disparaît si abonnement expire)

2. **Récurrence Complexe**
   - **Proposition** : RRULE (iCalendar standard)
   - **Actuel** : Structure simple (jour + heure + dates)

3. **Projection Automatique**
   - **Proposition** : Génération automatique d'`OccurrenceCours` basée sur RRULE + validité abonnement
   - **Actuel** : Création manuelle des cours

---

## 3. Architecture Proposée

### 3.1 Modèle de Données Cible

#### 3.1.1 Table `recurring_slots` (Blocage Long Terme)

```sql
CREATE TABLE recurring_slots (
    id BIGINT PRIMARY KEY,
    student_id BIGINT NOT NULL,           -- L'élève qui "possède" ce créneau
    teacher_id BIGINT NOT NULL,            -- L'enseignant assigné
    club_id BIGINT NOT NULL,                -- Le club
    course_type_id BIGINT,                  -- Type de cours (optionnel)
    
    -- Récurrence (RRULE)
    rrule TEXT NOT NULL,                    -- Ex: "FREQ=WEEKLY;BYDAY=SA"
    reference_start_time DATETIME NOT NULL, -- Date/heure de la première occurrence
    
    -- Durée
    duration_minutes INT NOT NULL,          -- Durée du cours en minutes
    
    -- Statut
    status ENUM('active', 'paused', 'cancelled', 'expired') DEFAULT 'active',
    
    -- Métadonnées
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Index
    INDEX idx_student_status (student_id, status),
    INDEX idx_teacher_status (teacher_id, status),
    INDEX idx_club_status (club_id, status)
);
```

**Points clés :**
- ✅ Appartient à l'élève (pas à l'abonnement)
- ✅ Persiste même si l'abonnement expire
- ✅ Utilise RRULE pour récurrence complexe

#### 3.1.2 Table `recurring_slot_subscriptions` (Liaison Blocage ↔ Abonnement)

```sql
CREATE TABLE recurring_slot_subscriptions (
    id BIGINT PRIMARY KEY,
    recurring_slot_id BIGINT NOT NULL,
    subscription_instance_id BIGINT NOT NULL,
    
    -- Période de validité pour cet abonnement
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    
    -- Statut
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Contraintes
    UNIQUE KEY unique_active_slot_subscription (recurring_slot_id, subscription_instance_id, status),
    FOREIGN KEY (recurring_slot_id) REFERENCES recurring_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_instance_id) REFERENCES subscription_instances(id) ON DELETE CASCADE
);
```

**Points clés :**
- ✅ Un créneau peut être "payé" par plusieurs abonnements successifs
- ✅ Historique des abonnements qui ont utilisé ce créneau

#### 3.1.3 Table `lesson_recurring_slots` (Liaison Cours ↔ Blocage)

```sql
CREATE TABLE lesson_recurring_slots (
    id BIGINT PRIMARY KEY,
    lesson_id BIGINT NOT NULL,
    recurring_slot_id BIGINT NOT NULL,
    subscription_instance_id BIGINT NOT NULL, -- L'abonnement qui "paie" pour ce cours
    
    -- Métadonnées
    generated_at TIMESTAMP,                   -- Quand le cours a été généré
    generated_by ENUM('auto', 'manual') DEFAULT 'auto',
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Contraintes
    UNIQUE KEY unique_lesson_recurring (lesson_id, recurring_slot_id),
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (recurring_slot_id) REFERENCES recurring_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_instance_id) REFERENCES subscription_instances(id) ON DELETE CASCADE
);
```

**Points clés :**
- ✅ Un cours peut être lié à un créneau récurrent
- ✅ Traçabilité de l'abonnement qui a payé pour ce cours

### 3.2 Flux de Données

#### 3.2.1 Création d'un Créneau Récurrent

```
1. Élève achète un abonnement (SubscriptionInstance)
2. Élève choisit un créneau récurrent (ex: Samedi 9h)
3. Système crée :
   - recurring_slot (blocage long terme)
   - recurring_slot_subscriptions (liaison abonnement ↔ créneau)
   - Génération automatique des lessons (OccurrenceCours) jusqu'à expiration
```

#### 3.2.2 Expiration d'un Abonnement

```
1. SubscriptionInstance expire (status = 'expired')
2. recurring_slot_subscriptions.status = 'expired'
3. recurring_slot reste actif (blocage long terme préservé)
4. Plus de génération automatique de lessons
5. Lessons existantes restent liées (historique)
```

#### 3.2.3 Renouvellement d'un Abonnement

```
1. Élève renouvelle son abonnement (nouveau SubscriptionInstance)
2. Système trouve le recurring_slot existant
3. Crée nouvelle liaison recurring_slot_subscriptions
4. Génération automatique reprend pour la nouvelle période
```

---

## 4. Plan de Migration

### 4.1 Phase 1 : Préparation (Sans Impact Production)

**Objectif :** Ajouter les nouvelles tables sans casser l'existant

**Actions :**
1. ✅ Créer migration pour `recurring_slots`
2. ✅ Créer migration pour `recurring_slot_subscriptions`
3. ✅ Créer migration pour `lesson_recurring_slots`
4. ✅ Créer modèles Eloquent (`RecurringSlot`, etc.)
5. ✅ Installer bibliothèque RRULE (PHP : `rlanvin/php-rrule`)

**Durée estimée :** 2-3 jours

**Risque :** Faible (tables vides, pas d'impact sur l'existant)

### 4.2 Phase 2 : Migration des Données Existantes

**Objectif :** Migrer les `SubscriptionRecurringSlot` existants vers le nouveau modèle

**Actions :**
1. Script de migration :
   - Lire tous les `SubscriptionRecurringSlot` actifs
   - Convertir en `RecurringSlot` avec RRULE
   - Créer les liaisons `recurring_slot_subscriptions`
   - Lier les lessons existantes via `lesson_recurring_slots`

2. Validation :
   - Vérifier que tous les créneaux ont été migrés
   - Vérifier que les lessons sont correctement liées
   - Tests de non-régression

**Durée estimée :** 3-5 jours

**Risque :** Moyen (migration de données, nécessite tests approfondis)

### 4.3 Phase 3 : Implémentation de la Logique Métier

**Objectif :** Implémenter la génération automatique et la gestion des blocages

**Actions :**
1. Service `RecurringSlotService` :
   - Génération automatique de lessons basée sur RRULE
   - Gestion des pauses/reprises
   - Gestion des expirations

2. Jobs/Commandes :
   - `GenerateRecurringLessonsJob` : Génère les lessons pour les créneaux actifs
   - `ExpireRecurringSlotSubscriptionsCommand` : Marque les abonnements expirés

3. Contrôleurs :
   - `RecurringSlotController` : CRUD des créneaux récurrents
   - Mise à jour de `SubscriptionController` pour gérer les créneaux

**Durée estimée :** 5-7 jours

**Risque :** Moyen-Élevé (logique complexe, nécessite tests)

### 4.4 Phase 4 : Interface Utilisateur

**Objectif :** Adapter l'UI pour gérer les créneaux récurrents

**Actions :**
1. Frontend :
   - Formulaire de création de créneau récurrent (avec sélecteur RRULE)
   - Affichage des créneaux récurrents dans le planning
   - Gestion des pauses/reprises
   - Indicateur visuel pour les créneaux "en attente de renouvellement"

2. Backend API :
   - Endpoints pour CRUD des créneaux récurrents
   - Endpoints pour génération manuelle de lessons
   - Endpoints pour gestion des pauses

**Durée estimée :** 5-7 jours

**Risque :** Faible-Moyen (UI, moins critique)

### 4.5 Phase 5 : Dépréciation de l'Ancien Modèle

**Objectif :** Retirer `SubscriptionRecurringSlot` (optionnel, peut rester en legacy)

**Actions :**
1. Marquer `SubscriptionRecurringSlot` comme déprécié
2. Rediriger les anciennes routes vers les nouvelles
3. Documentation de migration pour les développeurs

**Durée estimée :** 1-2 jours

**Risque :** Faible (si on garde l'ancien modèle en legacy)

---

## 5. Risques et Points d'Attention

### 5.1 Risques Techniques

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Migration de données échoue | Moyenne | Élevé | Scripts de rollback, tests locaux |
| Performance (génération automatique) | Faible | Moyen | Jobs asynchrones, indexation DB |
| Complexité RRULE | Moyenne | Moyen | Bibliothèque standard, tests unitaires |
| Conflits de créneaux | Moyenne | Élevé | Validation stricte, vérifications avant création |

### 5.2 Risques Métier

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Confusion utilisateurs (nouveau modèle) | Moyenne | Moyen | Documentation, formation, UI intuitive |
| Perte de données historiques | Faible | Élevé | Migration complète, sauvegarde avant migration |
| Blocages non désirés (créneaux "fantômes") | Moyenne | Moyen | Mécanisme de nettoyage automatique, expiration |

### 5.3 Points d'Attention Spécifiques

1. **Gestion des Conflits**
   - Que se passe-t-il si un élève a deux abonnements actifs pour le même créneau ?
   - Solution : Validation stricte, un seul abonnement actif par créneau à la fois

2. **Performance de Génération**
   - Générer 52 cours (1 an) peut être lourd
   - Solution : Génération progressive (ex: 3 mois à l'avance), jobs asynchrones

3. **Annulation de Cours**
   - Si un cours est annulé, doit-on le régénérer ?
   - Solution : Non, l'annulation est définitive (ou option de régénération manuelle)

4. **Modification de RRULE**
   - Que se passe-t-il si on change la récurrence d'un créneau actif ?
   - Solution : Créer un nouveau créneau, marquer l'ancien comme "cancelled"

---

## 6. Étapes de Mise en Place

### 6.1 Étape 1 : Validation du Plan

**Actions :**
- [ ] Revue du plan avec l'équipe
- [ ] Validation des choix techniques (RRULE, structure DB)
- [ ] Estimation des ressources (temps, développeurs)
- [ ] Validation des risques acceptables

**Livrable :** Plan validé et signé

### 6.2 Étape 2 : Préparation Technique

**Actions :**
- [ ] Installation de la bibliothèque RRULE PHP
- [ ] Création des migrations
- [ ] Création des modèles Eloquent
- [ ] Tests unitaires de base (modèles, relations)

**Livrable :** Structure DB prête, modèles fonctionnels

### 6.3 Étape 3 : Migration des Données

**Actions :**
- [ ] Script de migration des `SubscriptionRecurringSlot`
- [ ] Tests de migration en local
- [ ] Validation des données migrées
- [ ] Plan de rollback

**Livrable :** Données migrées, validation OK

### 6.4 Étape 4 : Logique Métier

**Actions :**
- [ ] Service `RecurringSlotService`
- [ ] Jobs de génération automatique
- [ ] Commandes de maintenance
- [ ] Tests d'intégration

**Livrable :** Logique métier fonctionnelle, tests passants

### 6.5 Étape 5 : Interface Utilisateur

**Actions :**
- [ ] API Backend (endpoints)
- [ ] Frontend (formulaires, affichage)
- [ ] Tests E2E
- [ ] Documentation utilisateur

**Livrable :** Interface complète, documentation

### 6.6 Étape 6 : Déploiement

**Actions :**
- [ ] Déploiement en production
- [ ] Tests utilisateurs (bêta)
- [ ] Corrections éventuelles
- [ ] Déploiement en production
- [ ] Monitoring post-déploiement

**Livrable :** Système en production, monitoring actif

---

## 7. Validation

### 7.1 Critères de Validation

**Technique :**
- ✅ Toutes les migrations s'exécutent sans erreur
- ✅ Tous les tests passent (unitaires, intégration, E2E)
- ✅ Performance acceptable (< 2s pour génération 3 mois)
- ✅ Pas de régression sur l'existant

**Fonctionnel :**
- ✅ Création de créneau récurrent fonctionne
- ✅ Génération automatique de lessons fonctionne
- ✅ Expiration d'abonnement préserve le créneau
- ✅ Renouvellement d'abonnement reprend la génération
- ✅ Pause/reprise de créneau fonctionne

**Métier :**
- ✅ Les utilisateurs comprennent le nouveau système
- ✅ Pas de perte de données
- ✅ Historique préservé

### 7.2 Questions à Valider

1. **RRULE vs Structure Simple**
   - ✅ Utiliser RRULE (standard, flexible) ou garder structure simple ?
   - **Recommandation :** RRULE pour flexibilité future

2. **Génération Automatique**
   - ✅ Générer tous les cours d'un coup ou progressivement ?
   - **Recommandation :** Progressif (3 mois à l'avance)

3. **Gestion des Pauses**
   - ✅ Pause = statut "paused" ou création d'un nouveau créneau ?
   - **Recommandation :** Statut "paused" (plus simple)

4. **Compatibilité avec l'Existant**
   - ✅ Garder `SubscriptionRecurringSlot` en legacy ou supprimer ?
   - **Recommandation :** Garder en legacy (moins risqué)

---

## 8. Estimation Globale

| Phase | Durée | Ressources |
|-------|-------|------------|
| Phase 1 : Préparation | 2-3 jours | 1 dev backend |
| Phase 2 : Migration | 3-5 jours | 1 dev backend + 1 QA |
| Phase 3 : Logique Métier | 5-7 jours | 1-2 devs backend |
| Phase 4 : Interface | 5-7 jours | 1 dev frontend + 1 dev backend |
| Phase 5 : Dépréciation | 1-2 jours | 1 dev backend |
| **TOTAL** | **16-24 jours** | **2-3 devs** |

**Durée totale estimée :** 3-5 semaines (selon disponibilité)

---

## 9. Prochaines Étapes

Une fois ce plan validé :

1. **Créer les tickets/étapes dans votre outil de gestion de projet**
2. **Assigner les ressources**
3. **Démarrer la Phase 1 (Préparation)**
4. **Mettre en place un suivi hebdomadaire**

---

## 10. Questions Ouvertes

Avant de démarrer, il serait utile de clarifier :

1. **Priorité** : Cette fonctionnalité est-elle critique pour la prochaine release ?
2. **Ressources** : Combien de développeurs sont disponibles ?
3. **Scope** : Doit-on supporter toutes les récurrences RRULE ou seulement les plus courantes ?
4. **UI** : Comment les utilisateurs vont-ils créer/modifier les créneaux récurrents ? (Formulaire simple ou avancé ?)

---

**Document créé le :** 2025-01-XX  
**Version :** 1.0  
**Auteur :** Assistant IA  
**Statut :** ⏳ En attente de validation

