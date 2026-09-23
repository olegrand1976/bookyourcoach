# Double authentification (2FA) — comptes club et admin

La 2FA par application d'authentification (TOTP) est **obligatoire** pour les rôles `club` et `admin`. Elle ne change rien pour les enseignants et les élèves.

## Parcours

1. `POST /api/auth/login` : pour un compte club ou admin, un mot de passe correct ne donne **pas** de jeton. La réponse contient seulement `data.challenge_token`, valable 10 minutes, avec l'un des deux indicateurs suivants :
   - `two_factor_setup_required` : premier enrôlement. Appeler `POST /auth/two-factor/setup` pour obtenir le QR code, puis `POST /auth/two-factor/setup/confirm` avec le premier code. La réponse contient le jeton et 8 codes de récupération.
   - `two_factor_required` : appeler `POST /auth/two-factor/challenge` avec `code` ou `recovery_code`, et éventuellement `remember_device`.
2. Si l'appareil est de confiance (cookie `2fa-device`, 30 jours), le code n'est pas demandé. L'enrôlement, lui, l'est toujours.
3. Middleware `2fa` : si un compte club ou admin sans 2FA configurée présente un jeton, il reçoit un `403` avec `code: two_factor_setup_required`. Cela couvre les jetons émis avant la mise en place de la 2FA. Le front renvoie alors vers `/login?two_factor=setup`.

Garde-fous :
- un même code n'est accepté qu'une fois ;
- un challenge est détruit après 5 codes faux ;
- limiteurs `two-factor` (par challenge et par IP) et `two-factor-account` ;
- secret et codes de récupération chiffrés en base, masqués du JSON ;
- les codes faux sont enregistrés dans l'historique des connexions (`invalid_two_factor`).

## Réglages de l'utilisateur

Ils se trouvent dans Profil club, ou dans « Mon compte » pour un admin (`/admin/profile`) :
- régénérer les codes de récupération ;
- changer de téléphone ;
- révoquer les appareils de confiance.

Les actions qui touchent au second facteur exigent **mot de passe + code courant**. Il n'est pas possible de désactiver la 2FA.

## Téléphone perdu : réinitialisation par un admin

1. Vérifier l'identité de la personne par un autre canal que la demande elle-même.
2. Dans Admin → Utilisateurs, utiliser l'action « Réinitialiser la double authentification » (icône téléphone). L'API correspondante est `POST /api/admin/users/{id}/two-factor/reset`.
3. Effets de la réinitialisation :
   - le secret, les codes et les appareils de confiance sont effacés ;
   - **toutes les sessions** du compte sont révoquées ;
   - à la prochaine connexion, la personne reconfigure sa 2FA.

Un admin ne peut pas réinitialiser sa propre 2FA : cela passe par un autre admin. S'il n'y en a pas, utiliser `php artisan tinker` :

```php
app(\App\Services\TwoFactorService::class)->reset(\App\Models\User::where('email', '…')->firstOrFail());
```

## Interrupteur de secours

`AUTH_TWO_FACTOR_ENFORCED=false` rétablit la connexion par mot de passe seul pour tous les clubs et admins, et neutralise le middleware `2fa`. Il sert **uniquement** en cas d'incident, par exemple si l'enrôlement est bloqué en masse. Le repasser à `true` dès l'incident réglé. Les secrets déjà configurés sont conservés.

Autres réglages (`config/bookyourcoach.php`, clé `auth`) :

| Variable | Défaut | Rôle |
|---|---|---|
| `AUTH_TWO_FACTOR_ISSUER` | `APP_NAME` | Nom affiché dans l'application d'authentification |
| `AUTH_TWO_FACTOR_CHALLENGE_TTL_MINUTES` | 10 | Durée de validité de l'étape 2FA |
| `AUTH_TWO_FACTOR_TRUSTED_DEVICE_DAYS` | 30 | Durée d'un appareil de confiance |

## Tests

- Backend :
  - `tests/Feature/Api/TwoFactorTest.php` : parcours de connexion et middleware ;
  - `TwoFactorAccountTest.php` : réglages et reset ;
  - `tests/Unit/Services/TwoFactorServiceTest.php`.
  La factory enrôle par défaut les clubs et admins qu'elle fabrique. Utiliser `->withoutTwoFactor()` pour tester l'enrôlement.
- Front : `tests/unit/TwoFactorStep.test.ts` et `TwoFactorSettings.test.ts`.
- E2E : le compte de `E2eClubAccountSeeder` est enrôlé avec `E2E_CLUB_TOTP_SECRET` et dispose d'un appareil de confiance `E2E_CLUB_DEVICE_TOKEN` (voir `frontend/.env.test.example`).
