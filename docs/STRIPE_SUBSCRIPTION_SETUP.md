# Configuration Stripe pour les modèles d'abonnement

## Objectif

Associer un modèle d'abonnement BookYourCoach a un produit/tarif Stripe pour permettre la souscription en ligne.

Dans l'application :

- `Activer le paiement Stripe pour ce modèle` : rend le modèle souscriptible en ligne
- `Stripe Price ID` : obligatoire si le paiement Stripe est activé
- `Stripe Product ID` : optionnel, utile pour garder la reference du produit Stripe

## 1. Créer le produit dans Stripe

Dans le dashboard Stripe :

1. Ouvrir `Catalogue produits`
2. Cliquer sur `Ajouter un produit`
3. Saisir le nom du produit
   Exemple : `Abonnement natation 10 cours`
4. Ajouter une description si besoin
5. Enregistrer

Une fois le produit cree, Stripe genere un identifiant du type :

- `prod_...`

C'est cette valeur qu'il faut copier dans `Stripe Product ID` si tu veux conserver la reference produit dans BookYourCoach.

## 2. Créer le tarif du produit

Depuis la fiche produit Stripe :

1. Cliquer sur `Ajouter un tarif`
2. Choisir :
   - devise : `EUR`
   - type : `Prix unique`
   - montant : le prix de l'abonnement
3. Enregistrer

Stripe genere alors un identifiant du type :

- `price_...`

C'est cette valeur qu'il faut copier dans `Stripe Price ID`.

## 3. Saisir les infos dans BookYourCoach

Dans `Club > Modèles d'abonnements` :

1. Créer ou modifier un modèle
2. Cocher `Activer le paiement Stripe pour ce modèle`
3. Renseigner :
   - `Stripe Price ID` : obligatoire
   - `Stripe Product ID` : optionnel mais recommande
4. Enregistrer

## 4. Comportement côté application

Quand `Activer le paiement Stripe pour ce modèle` est coche :

- le modèle devient disponible dans la souscription en ligne
- le backend utilise prioritairement `stripe_price_id` pour créer la session Stripe Checkout
- si `stripe_price_id` est absent, la sauvegarde est refusee

Quand l'option n'est pas cochee :

- le modèle reste un modèle interne club
- il n'est pas propose a l'élève pour paiement Stripe

## 5. Où trouver les identifiants dans Stripe

### Product ID

Depuis la page du produit Stripe :

- chercher l'identifiant affiche dans les details du produit
- format : `prod_...`

### Price ID

Depuis le tarif du produit :

- ouvrir le tarif
- copier l'identifiant du tarif
- format : `price_...`

## 6. Recommandation pratique

Pour chaque modèle BookYourCoach vendu en ligne :

1. créer 1 produit Stripe
2. créer 1 tarif Stripe
3. coller au minimum le `price_...` dans BookYourCoach
4. coller aussi le `prod_...` pour garder une trace claire

## 7. Vérification rapide

Avant de tester un paiement :

1. le modèle est `actif`
2. `Activer le paiement Stripe pour ce modèle` est coche
3. `Stripe Price ID` est renseigne
4. les cles Stripe sont bien configurees dans l'environnement
5. le webhook Stripe est configure si on veut la creation automatique de l'abonnement apres paiement

## 8. Exemple

### Dans Stripe

- Produit : `prod_S8abc123xyz`
- Tarif : `price_1RabcXYZ987654`

### Dans BookYourCoach

- `Activer le paiement Stripe pour ce modèle` : coche
- `Stripe Product ID` : `prod_S8abc123xyz`
- `Stripe Price ID` : `price_1RabcXYZ987654`
