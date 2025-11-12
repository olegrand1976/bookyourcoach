# 📄 Intégration de la Lettre de Volontariat

## Vue d'ensemble

Implémentation complète d'un système de génération de lettres d'information pour les volontaires (enseignants), conforme à la **Loi belge du 3 juillet 2005 relative aux droits des volontaires**.

## ✅ Fonctionnalités implémentées

### 1. **Stockage des informations légales**
Les clubs peuvent maintenant renseigner toutes les informations nécessaires pour générer les lettres :
- Représentant légal (nom et fonction)
- Assurance RC (obligatoire)
- Assurance complémentaire (optionnelle)
- Régime de défraiement (forfait, frais réels, ou aucun)

### 2. **Formulaire de profil club enrichi**
Nouvelle section "Informations Légales" dans `/club/profile` avec :
- Interface intuitive pour saisir toutes les données
- Validation et aide contextuelle
- Sauvegarde automatique avec les autres données du profil

### 3. **Page de génération des lettres**
Nouvelle page `/club/volunteer-letter` permettant de :
- Lister tous les enseignants affiliés au club
- Générer une lettre pré-remplie pour chaque enseignant
- Prévisualiser la lettre dans un modal
- Imprimer ou télécharger la lettre en PDF
- Alertes si les informations légales sont incomplètes

### 4. **Template de lettre professionnel**
Composant `VolunteerLetterTemplate.vue` qui :
- Génère automatiquement la lettre avec toutes les données
- Remplit dynamiquement les sections selon le type de défraiement
- Format professionnel et conforme à la législation
- Optimisé pour l'impression et le PDF

## 🗄️ Modifications de la base de données

### Migration 1: `add_company_number_to_clubs_table`
```sql
ALTER TABLE clubs ADD COLUMN company_number VARCHAR(255) NULL;
```

### Migration 2: `add_legal_fields_to_clubs_table`
Ajout des champs suivants à la table `clubs` :
- `legal_representative_name` (varchar, nullable)
- `legal_representative_role` (varchar, nullable)
- `insurance_rc_company` (varchar, nullable)
- `insurance_rc_policy_number` (varchar, nullable)
- `insurance_additional_company` (varchar, nullable)
- `insurance_additional_policy_number` (varchar, nullable)
- `insurance_additional_details` (text, nullable)
- `expense_reimbursement_type` (enum: 'forfait', 'reel', 'aucun', default: 'aucun')
- `expense_reimbursement_details` (text, nullable)

### Migration 3: `add_address_fields_to_users_table`
Ajout du champ `address` à la table `users` (les autres champs existaient déjà).

## 📁 Fichiers créés/modifiés

### Backend

#### **Migrations**
- ✅ `database/migrations/2025_10_28_205730_add_company_number_to_clubs_table.php`
- ✅ `database/migrations/2025_10_28_210643_add_legal_fields_to_clubs_table.php`
- ✅ `database/migrations/2025_10_28_210644_add_address_fields_to_users_table.php`

#### **Modèles**
- ✅ `app/Models/Club.php` - Ajout des nouveaux champs dans `$fillable`
- ✅ `app/Models/User.php` - Ajout du champ `address` dans `$fillable`

#### **Controllers**
- ✅ `app/Http/Controllers/Api/ClubController.php` - Mise à jour de `updateProfile()` pour gérer les nouveaux champs

### Frontend

#### **Pages**
- ✅ `frontend/pages/club/profile.vue` - Nouvelle section "Informations Légales"
- ✅ `frontend/pages/club/volunteer-letter.vue` - **NOUVEAU** - Page de génération des lettres
- ✅ `frontend/pages/club/dashboard.vue` - Ajout du bouton "Lettres" dans le header

#### **Composants**
- ✅ `frontend/components/VolunteerLetterTemplate.vue` - **NOUVEAU** - Template de la lettre

## 🎯 Utilisation

### 1. Configuration initiale (Club)

1. Accéder à **Profil du Club** (`/club/profile`)
2. Faire défiler jusqu'à la section **"Informations Légales"**
3. Remplir tous les champs obligatoires :
   - Représentant légal (nom et fonction)
   - Assurance RC (compagnie et numéro de police)
   - Régime de défraiement et ses détails
4. Sauvegarder

### 2. Génération d'une lettre

1. Depuis le **Dashboard Club**, cliquer sur le bouton **"Lettres"** (violet/rose)
2. Ou accéder directement à `/club/volunteer-letter`
3. Si les informations légales sont incomplètes, une alerte s'affiche
4. Cliquer sur un enseignant dans la liste
5. La lettre pré-remplie s'affiche dans un modal
6. Options disponibles :
   - **Imprimer** : imprimer directement
   - **Télécharger PDF** : génère un PDF (utilise actuellement l'impression du navigateur)
   - **Fermer** : fermer sans action

### 3. Signature et distribution

1. Imprimer la lettre en **double exemplaire**
2. Faire signer par le représentant légal de l'ASBL
3. Faire lire, approuver et signer par le volontaire (avec mention "Lu et approuvé")
4. Remettre un exemplaire à chaque partie

## 📋 Contenu de la lettre

La lettre générée contient automatiquement :

### En-tête
- Titre : "Note d'Information au Volontaire"
- Référence légale : Loi du 3 juillet 2005

### Section 1 : Les parties
- **L'ASBL** : Nom, adresse, numéro BCE, représentant
- **Le Volontaire** : Nom, adresse de l'enseignant

### Section 2 : Informations sur l'Organisation
- Mission/objectif social de l'ASBL (description du club)

### Section 3 : Assurances
- **Assurance RC** (obligatoire) : compagnie et numéro de police
- **Assurance complémentaire** (si applicable) : détails de couverture

### Section 4 : Régime des Défraiements
Selon le type choisi :
- **Forfaitaire** : montant et fréquence, rappel des plafonds légaux
- **Frais réels** : types de frais remboursables, modalités
- **Aucun** : mention explicite de l'absence de défraiement

### Section 5 : Devoir de Discrétion
- Obligation de confidentialité
- Référence à l'article 458 du Code pénal (secret professionnel)

### Section 6 : Déclaration du Volontaire
- Attestation de réception et d'acceptation
- Obligation d'informer l'organisme de paiement (ONEM, mutuelle, CPAS)

### Signatures
- Lieu et date automatiques
- Cadre pour signature de l'ASBL (représentant)
- Cadre pour signature du Volontaire (avec mention "Lu et approuvé")

## 🎨 Design et UX

### Palette de couleurs
- **Gradient Violet/Rose** (`from-purple-500 to-pink-600`) : associé aux documents légaux/premium
- **Gradient Bleu/Indigo** : pour les éléments du club
- Conformité au Design System `Claude.md`

### Responsive
- Formulaire adaptatif sur tous les écrans
- Modal scrollable pour les longues lettres
- Impression optimisée

### Accessibilité
- Labels clairs et explicites
- Aide contextuelle (placeholders, descriptions)
- Alertes visuelles si informations manquantes
- Navigation au clavier

## 🔐 Sécurité et conformité

### RGPD
- Les données personnelles des volontaires sont traitées conformément au RGPD
- Stockage minimal des informations nécessaires
- Accès restreint aux administrateurs du club

### Conformité légale
- Respect de la Loi du 3 juillet 2005 (Belgique)
- Tous les points obligatoires sont couverts :
  1. ✅ Informations sur l'organisation
  2. ✅ Assurances (RC minimum)
  3. ✅ Régime des défraiements
  4. ✅ Devoir de discrétion

## 🚀 Améliorations futures possibles

### Court terme
- [ ] Génération PDF côté serveur (package Laravel PDF)
- [ ] Envoi par email de la lettre à l'enseignant
- [ ] Historique des lettres générées
- [ ] Signature électronique

### Moyen terme
- [ ] Multi-langues (FR, NL, EN)
- [ ] Templates personnalisables par club
- [ ] Rappels automatiques de renouvellement annuel
- [ ] Export en masse (tous les enseignants)

### Long terme
- [ ] Intégration avec un service de signature électronique (DocuSign, etc.)
- [ ] Archivage légal des documents signés
- [ ] Tableau de bord des volontaires (statut des documents)

## 📖 Références légales

- **Loi du 3 juillet 2005** relative aux droits des volontaires (Belgique)
- **Article 458 du Code pénal belge** : Secret professionnel
- **Plafonds légaux de défraiement** : variables selon la législation en vigueur

## 🛠️ Installation et déploiement

### Prérequis
- PHP 8.1+
- Laravel 10+
- MySQL ou compatible
- Node.js 18+
- Nuxt 3

### Étapes de déploiement

1. **Exécuter les migrations**
```bash
php artisan migrate
```

2. **Vérifier les permissions**
Les clubs doivent avoir accès aux routes :
- `/club/profile` (lecture/écriture)
- `/club/volunteer-letter` (lecture)
- `/club/teachers` (lecture)

3. **Tester**
- Se connecter en tant que club
- Compléter le profil avec les informations légales
- Générer une lettre pour un enseignant test
- Vérifier l'impression/PDF

## ✅ Checklist de validation

Avant mise en production :
- [x] Migrations créées et testées
- [x] Modèles mis à jour
- [x] API fonctionnelle (lecture/écriture des données)
- [x] Interface utilisateur intuitive
- [x] Formulaire validé
- [x] Template de lettre conforme à la loi
- [x] Impression optimisée
- [ ] Tests unitaires (à ajouter)
- [ ] Tests d'intégration (à ajouter)
- [ ] Documentation utilisateur (ce fichier)

## 📞 Support

Pour toute question sur l'implémentation ou l'utilisation :
- Consulter ce document
- Consulter `Claude.md` pour le Design System
- Vérifier les migrations dans `database/migrations/`

---

**Dernière mise à jour** : 28 octobre 2025  
**Version** : 1.0.0  
**Statut** : ✅ Complété

