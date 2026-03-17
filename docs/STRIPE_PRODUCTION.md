# Stripe en production

## Pourquoi Stripe peut ne pas fonctionner « en ligne »

### 1. Variables d'environnement manquantes ou incorrectes

En production, vérifier dans `.env` (ou la config du déploiement) :

| Variable | Rôle | Exemple |
|----------|------|---------|
| `STRIPE_SECRET` | Clé secrète API (obligatoire pour créer une session Checkout) | `sk_live_...` en prod, `sk_test_...` en test |
| `STRIPE_KEY` | Clé publique (frontend, si utilisé) | `pk_live_...` / `pk_test_...` |
| `STRIPE_WEBHOOK_SECRET` | Signature des webhooks (création abonnement après paiement) | `whsec_...` |
| `FRONTEND_URL` | URL du frontend pour success_url / cancel_url | `https://activibe.be` |

- Si `STRIPE_SECRET` est vide ou absente → la création de session Checkout échoue (500). Les logs Laravel contiennent : `Stripe: STRIPE_SECRET non configuré`.
- Si `FRONTEND_URL` est absente → les redirections Stripe pointent vers `http://localhost:3000` après paiement.

### 2. Clés test vs live

- **En production** : utiliser les clés **live** du Dashboard Stripe (onglet « Clés API » → « Clés en direct ») : `sk_live_...`, `pk_live_...`.
- Les clés **test** (`sk_test_...`, `pk_test_...`) ne permettent pas de vrais paiements en live.

### 3. Webhook non configuré ou mauvaise URL

Après un paiement réussi, Stripe envoie un événement `checkout.session.completed` à votre backend. Sans webhook correct :

- Le paiement peut être pris par Stripe mais **l’abonnement n’est pas créé** dans votre base (SubscriptionInstance, etc.).

À faire dans le [Dashboard Stripe → Développeurs → Webhooks](https://dashboard.stripe.com/webhooks) :

1. Ajouter un endpoint : `https://votre-api.com/api/stripe/webhook`.
2. Sélectionner l’événement `checkout.session.completed`.
3. Récupérer le **Secret de signature** et le mettre dans `STRIPE_WEBHOOK_SECRET` en production.

### 4. Erreur 500 au clic sur « Payer »

Causes possibles déjà corrigées ou à surveiller :

- **Description du produit** : un bug dans le `sprintf` de la description Stripe a été corrigé (champs null, mauvais nombre d’arguments). Si une 500 persiste, activer `APP_DEBUG=true` temporairement et regarder le message d’erreur dans la réponse ou les logs.
- **Prix** : le montant est en centimes ; un prix à 0 peut être refusé par Stripe selon le contexte.

### 5. Vérification rapide

- Logs Laravel (production) : chercher `Stripe`, `createCheckoutSession`, `STRIPE_SECRET non configuré`, `Erreur création session Stripe Checkout`.
- Dashboard Stripe → Logs : voir si des appels API échouent (clé invalide, montant invalide, etc.).

## Résumé checklist production

1. `STRIPE_SECRET` (et si besoin `STRIPE_KEY`) définis avec les **clés live**.
2. `FRONTEND_URL` = URL réelle du frontend (ex. `https://activibe.be`).
3. Webhook Stripe pointant vers `https://api.../api/stripe/webhook` avec `STRIPE_WEBHOOK_SECRET` configuré.
4. Après déploiement, tester un paiement test (mode test Stripe) puis un paiement live si tout est en place.
