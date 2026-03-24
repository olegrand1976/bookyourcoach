# Importer un dump MySQL prod en local (Docker)

## Erreur #1822 « Missing index … referenced table `clubs` »

Les exports **phpMyAdmin** créent souvent les tables **sans** `PRIMARY KEY` dans le `CREATE TABLE`, puis ajoutent les clés à la fin du fichier (`ALTER TABLE … ADD PRIMARY KEY`).

Si l’import s’arrête avant cette section (timeout, import partiel, limite de requêtes) ou si les blocs sont exécutés dans un ordre incorrect, `clubs.id` n’a pas d’index au moment où MySQL crée `course_slots_club_id_foreign` → **erreur 1822**.

## Méthode recommandée (Docker local)

1. Démarrer MySQL local :

   ```bash
   docker compose -f docker-compose.local.yml up -d mysql-local
   ```

2. Importer avec correction automatique du dump :

   ```bash
   FIX=1 ./scripts/import-mysql-dump-local-docker.sh /chemin/vers/book-your-coach.sql
   ```

   Sans correction (dump déjà sain ou `mysqldump` classique) :

   ```bash
   ./scripts/import-mysql-dump-local-docker.sh /chemin/vers/dump.sql
   ```

3. Vérifier `.env.local` : `DB_HOST=127.0.0.1`, `DB_PORT=3308`, `DB_DATABASE=book_your_coach_local`, identifiants `activibe_user` / `activibe_password`.

4. Migrations Laravel (si besoin d’aligner le schéma sur le code) :

   ```bash
   php artisan migrate:status
   ```

## Corriger un fichier `.sql` sans importer

```bash
php scripts/fix-phpmyadmin-mysql-dump.php /chemin/vers/dump.sql > dump-fixed.sql
# ou écraser le fichier :
php scripts/fix-phpmyadmin-mysql-dump.php /chemin/vers/dump.sql --in-place
```

Puis import manuel :

```bash
docker exec -i activibe-mysql-local mysql -uroot -prootpassword --max_allowed_packet=512M book_your_coach_local < dump-fixed.sql
```

## phpMyAdmin

Cocher **« Désactiver la vérification des clés étrangères »** pour l’import ne remplace pas l’index manquant sur la table référencée : il faut que le fichier soit **importé en entier** jusqu’aux `ALTER TABLE … ADD PRIMARY KEY`, ou utiliser le script de correction ci-dessus.
