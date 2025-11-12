# 🔧 Correction de l'encodage UTF-8

## 🎯 Problème Identifié

Le backend Laravel retourne des noms mal encodés :
- ❌ `Manager Centre Ã‰toiles`
- ✅ `Manager Centre Étoiles`

## 📋 Étapes de Résolution

### 1️⃣ Arrêter les services Docker

```bash
cd /home/olivier/projets/bookyourcoach
docker compose -f docker-compose.local.yml down
```

### 2️⃣ Redémarrer les services avec la nouvelle configuration

```bash
docker compose -f docker-compose.local.yml up -d
```

Cela applique la nouvelle configuration MySQL qui force UTF-8MB4.

### 3️⃣ Accéder au conteneur backend

```bash
docker compose -f docker-compose.local.yml exec backend bash
```

### 4️⃣ Vérifier les données actuelles (DRY RUN)

```bash
php artisan fix:utf8-encoding --dry-run
```

Exemple de sortie :
```
🔧 Correction de l'encodage UTF-8...
Mode: DRY RUN (aucune modification)

📋 Table: users
👤 User #11 (manager@centre-equestre-des-etoiles.fr):
   • name: 'Manager Centre Ã‰toiles' → 'Manager Centre Étoiles'

📊 Résultat: 1 utilisateur(s) corrigé(s) sur 15
```

### 5️⃣ Appliquer les corrections

```bash
php artisan fix:utf8-encoding
```

### 6️⃣ Vérifier la correction

Connectez-vous à l'application et vérifiez que les noms s'affichent correctement dans le header.

## 🔍 Vérification de la base de données

Si vous voulez vérifier manuellement la base de données :

```bash
# Accéder à MySQL
docker compose -f docker-compose.local.yml exec mysql mysql -u root -p
# Mot de passe: root_password (voir .env.local)

# Utiliser la base de données
USE bookyourcoach_local;

# Afficher l'encodage de la table
SHOW CREATE TABLE users;

# Vérifier les noms
SELECT id, name, email FROM users WHERE name LIKE '%Ã%';

# Sortir de MySQL
EXIT;
```

## 🐛 Problème CORS (Notifications)

Les erreurs CORS sur `/api/club/notifications/unread-count` suggèrent que le backend s'arrête après un certain temps.

### Vérifier les logs du backend :

```bash
docker compose -f docker-compose.local.yml logs backend -f
```

### Solutions possibles :

1. **Redémarrer le backend régulièrement** (solution temporaire)
   ```bash
   docker compose -f docker-compose.local.yml restart backend
   ```

2. **Augmenter les limites PHP** (si timeout)
   - Modifier `docker/php/php.ini`
   - Augmenter `max_execution_time` et `memory_limit`

3. **Vérifier les workers de queue**
   ```bash
   docker compose -f docker-compose.local.yml exec backend php artisan queue:work --daemon
   ```

## ✅ Résultat Attendu

Après ces étapes :
- ✅ Les noms avec accents s'affichent correctement
- ✅ Le header affiche "Manager Centre Étoiles"
- ✅ Les nouvelles données sont correctement encodées en UTF-8
- ✅ Les cookies contiennent les données correctement encodées

## 📝 Note Importante

La configuration MySQL a été modifiée pour forcer UTF-8MB4 à chaque connexion.
Toutes les **nouvelles données** seront automatiquement correctement encodées.

Les **données existantes** doivent être corrigées avec le script `fix:utf8-encoding`.

## 🔄 Automatisation Future

Pour éviter ce problème à l'avenir :
1. Toujours utiliser UTF-8MB4 lors de l'insertion de données
2. Vérifier l'encodage des fichiers sources (`.php`, `.vue`) → UTF-8
3. Configurer l'éditeur de code pour UTF-8
4. Tester avec des caractères accentués lors du développement

