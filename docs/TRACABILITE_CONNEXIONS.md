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

La clé de licence MaxMind est enregistrée dans les **secrets GitHub** du dépôt, sous
`MAXMIND_LICENSE_KEY`. Elle n'est utilisée qu'au moment du build : l'application n'en
a pas besoin à l'exécution, seulement des fichiers `.mmdb`.

**Production** — le `Dockerfile` racine télécharge les deux bases pendant le build et
les dépose dans `storage/app/geoip/`. C'est nécessaire parce que le système de
fichiers de Cloud Run est éphémère. L'étape est non bloquante : sans secret, ou si
MaxMind ne répond pas, l'image se construit quand même et la localisation reste vide.

**En local** — les fichiers ne sont pas dans le dépôt (63 Mo, licence MaxMind, et
`storage/app/` est ignoré par git). Pour les installer ou les rafraîchir :

```bash
docker compose --profile test run --rm -e MAXMIND_KEY=<votre clé> php-test sh -c '
mkdir -p storage/app/geoip
for edition in GeoLite2-City GeoLite2-ASN; do
  curl -fsSL "https://download.maxmind.com/app/geoip_download?edition_id=${edition}&license_key=${MAXMIND_KEY}&suffix=tar.gz" -o /tmp/${edition}.tar.gz \
    && tar -xzf /tmp/${edition}.tar.gz -C /tmp \
    && find /tmp -name "${edition}.mmdb" -exec cp {} storage/app/geoip/ \;
done'
```

Le répertoire `storage` appartient à l'utilisateur du conteneur : lancer la commande
depuis l'hôte échouerait sur les droits.

Les bases se périment : prévoir une mise à jour mensuelle. Les deux tests de
`LoginHistoryTest` qui interrogent la vraie base se sautent d'eux-mêmes lorsqu'elle
est absente — la CI reste donc verte sans les fichiers.

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
