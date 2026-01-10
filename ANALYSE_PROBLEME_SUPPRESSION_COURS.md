# Analyse du problème de suppression des cours futurs

## Situation observée

- **Cours sélectionné** : ID 250, Gioia Di Franco, 14 janvier 2026 à 14:00
- **Message dans la modale** : "Aucun cours futur trouvé pour cet abonnement"
- **Attendu** : L'option "Supprimer tous les cours futurs" devrait apparaître car il y a 6 cours futurs

## Vérifications effectuées

### ✅ Données en base
- Le cours existe bien (ID 250)
- L'abonnement est bien lié (Abonnement ID: 7)
- Il y a bien **6 cours futurs** après le 14 janvier 2026 à 14:00
- L'API backend retourne bien les cours futurs (8 cours dont le cours actuel)

### ❌ Problème identifié

L'endpoint `/api/subscription-instances/{instanceId}/future-lessons` a plusieurs restrictions :

1. **Authentification requise** : L'utilisateur doit être authentifié
2. **Rôle requis** : L'utilisateur doit avoir le rôle `'club'` (ligne 1878)
3. **Vérification d'appartenance** : L'abonnement doit appartenir au club de l'utilisateur connecté (lignes 1893-1903)

### Points à vérifier

1. **Rôle de l'utilisateur** : L'admin `b.murgo1976@gmail.com` a-t-il aussi le rôle `'club'` ?
2. **Appartenance du club** : L'abonnement ID 7 appartient-il au club ID 11 (ACTI'VIBE) ?
3. **Erreur API** : L'appel API échoue-t-il silencieusement ?

## Solutions possibles

### Solution 1 : Corriger la vérification d'appartenance
Si l'abonnement n'est pas détecté comme appartenant au club, il faut soit :
- Corriger la vérification dans `getFutureLessons`
- Ou permettre l'accès si les cours liés à l'abonnement appartiennent au club

### Solution 2 : Améliorer la gestion d'erreur frontend
Le code frontend catch l'erreur mais ne log pas suffisamment. Il faut :
- Logger l'erreur complète dans la console
- Afficher un message d'erreur si l'API échoue
- Utiliser la méthode fallback si l'API principale échoue

### Solution 3 : Vérifier le format de date
L'API utilise `startOfDay()` donc si on passe `2026-01-14`, elle cherche après `2026-01-14 00:00:00`.
Le cours actuel est à 14:00, donc il sera inclus dans les résultats et doit être exclu côté client.

## Code concerné

- **Backend** : `app/Http/Controllers/Api/SubscriptionController.php::getFutureLessons()` (ligne 1865)
- **Frontend** : `frontend/pages/club/planning.vue::checkFutureLessonsForDelete()` (ligne 2679)

## Actions recommandées

1. ✅ Vérifier que l'abonnement ID 7 appartient au club ID 11 → **CONFIRMÉ**
2. ⚠️ Vérifier le rôle de l'utilisateur connecté (doit être 'club')
3. ⚠️ Vérifier dans les logs du navigateur si l'API retourne une erreur (console.log)
4. ⚠️ Améliorer le logging côté frontend pour diagnostiquer
5. ⚠️ Vérifier que le cours chargé contient bien les `subscription_instances` dans le payload

## Diagnostic à effectuer dans le navigateur

Ouvrir la console du navigateur (F12) et vérifier :
1. Les logs de `checkFutureLessonsForDelete` 
2. Si l'appel API `/subscription-instances/7/future-lessons?after_date=2026-01-14` est effectué
3. La réponse de l'API (succès/erreur)
4. Si les `subscription_instances` sont bien présentes dans `lessonToDelete.value`

## Cause probable

Le problème est probablement que :
- Le cours chargé depuis `/lessons/{id}` n'inclut pas les `subscription_instances` dans la réponse
- Ou l'appel API `getFutureLessons` échoue silencieusement (erreur 403/404 non gérée)
- Ou le format de la date `after_date` n'est pas correct

## Solution immédiate

Vérifier que l'endpoint `/lessons/{id}?include=subscription_instances` retourne bien les `subscription_instances` dans la réponse.
