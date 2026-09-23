# Traçabilité des connexions

Historique des connexions réussies et refusées, avec adresse IP, appareil et
localisation approximative. Mis en place après l'incident du 23/09/2026, où
reconstituer qui s'était connecté a exigé les journaux de l'hébergeur : l'application
enregistrait l'adresse du relais interne de Cloud Run, identique pour tout le monde.

## L'adresse IP réelle

Derrière le load balancer Google, `X-Forwarded-For` vaut
`<valeur fournie par le client>, <IP client>, <IP du LB>`. La vraie adresse est donc
l'**avant-dernière** ; tout ce qui est à gauche peut avoir été forgé par le client.

`ClientIpResolver` retient cette entrée, réglée par `AUTH_TRUSTED_PROXY_HOPS` (1 par
défaut). La chaîne brute est conservée dans `login_attempts.forwarded_for` : si
l'infrastructure change, on peut vérifier a posteriori que la bonne entrée est
retenue et corriger la variable **sans redéployer de code**.

Contrôle recommandé après toute modification d'infrastructure :

```bash
php artisan auth:login-history <email> --limit=5
```

et comparer l'adresse retenue avec `httpRequest.remoteIp` des journaux Cloud Run.

## Localisation — installation de GeoLite2

La résolution est **hors ligne** : aucune adresse de vos utilisateurs n'est envoyée à
un tiers. En l'absence des fichiers, la localisation reste vide et rien ne casse.

1. Créer un compte gratuit MaxMind et générer une clé de licence.
2. Télécharger `GeoLite2-City.mmdb` et `GeoLite2-ASN.mmdb`.
3. Les déposer dans `storage/app/geoip/` (ou pointer `GEOIP_CITY_DATABASE` et
   `GEOIP_ASN_DATABASE` ailleurs).
4. En production Cloud Run, le système de fichiers est éphémère : les fichiers
   doivent être **inclus dans l'image** (Dockerfile) ou montés depuis un bucket.
   Sans cela, la localisation restera vide en production.

Les bases se périment : prévoir une mise à jour mensuelle.

## Ce que la localisation vaut, et ce qu'elle ne vaut pas

Sur une ligne grand public, une IP donne au mieux la ville de rattachement du
fournisseur d'accès — `accuracy_radius_km` le rappelle dans chaque ligne. **Elle situe
une connexion, elle ne localise pas une personne.** Sur mobile, l'écart avec la
position réelle peut atteindre plusieurs dizaines de kilomètres.

## Accès et données personnelles

Les adresses IP et les localisations sont des données personnelles.

- Chacun consulte son propre historique (`GET /api/auth/login-history`) : c'est ce qui
  permet de repérer soi-même un accès non reconnu.
- Les administrateurs plateforme consultent n'importe quel compte
  (`GET /api/admin/users/{id}/login-history`), pour enquêter.
- Les gérants de club n'y ont **pas** accès : ce serait de la surveillance
  d'enseignants et d'élèves, avec une base légale à établir au préalable.
- L'adresse visée, la chaîne brute et l'agent utilisateur ne sont exposés qu'aux
  administrateurs.

Conservation : **365 jours** (`AUTH_LOGIN_HISTORY_RETENTION_DAYS`), purge planifiée
chaque nuit par `auth:prune-login-history`.

Restent à faire côté conformité, hors code : mentionner cette collecte dans la
politique de confidentialité, et l'inscrire au registre des traitements avec sa
finalité — sécurité des comptes — et sa durée.
