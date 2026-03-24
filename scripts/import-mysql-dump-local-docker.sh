#!/usr/bin/env bash
# Import d’un dump MySQL (ex. prod) dans la base locale Docker (docker-compose.local.yml).
#
# Prérequis : conteneur activibe-mysql-local qui tourne (port hôte 3308).
#
# Usage :
#   ./scripts/import-mysql-dump-local-docker.sh /chemin/vers/book-your-coach.sql
#   FIX=1 ./scripts/import-mysql-dump-local-docker.sh /chemin/vers/dump.sql   # applique fix-phpmyadmin-mysql-dump.php
#
set -euo pipefail

DUMP="${1:?Usage: $0 <fichier.sql>}"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CONTAINER="${MYSQL_CONTAINER:-activibe-mysql-local}"
DB_NAME="${MYSQL_DATABASE:-book_your_coach_local}"
ROOT_PW="${MYSQL_ROOT_PASSWORD:-rootpassword}"

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
  echo "Erreur : le conteneur « $CONTAINER » n’est pas démarré." >&2
  echo "Lance : docker compose -f docker-compose.local.yml up -d mysql-local" >&2
  exit 1
fi

MYSQL=(docker exec -i "$CONTAINER" mysql -uroot -p"$ROOT_PW")

echo "→ Réinitialisation de la base « $DB_NAME »…"
"${MYSQL[@]}" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "→ Droits pour activibe_user@% sur $DB_NAME…"
"${MYSQL[@]}" -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO 'activibe_user'@'%'; FLUSH PRIVILEGES;" 2>/dev/null || true

IMPORT_FILE="$DUMP"
TMP=""
cleanup() { [[ -n "$TMP" && -f "$TMP" ]] && rm -f "$TMP"; }
trap cleanup EXIT

if [[ "${FIX:-0}" == "1" ]]; then
  TMP="$(mktemp --suffix=.sql)"
  echo "→ Correction du dump (PRIMARY KEY inline)…"
  php "$ROOT/scripts/fix-phpmyadmin-mysql-dump.php" "$DUMP" > "$TMP"
  IMPORT_FILE="$TMP"
fi

echo "→ Import (peut prendre plusieurs minutes, max_allowed_packet augmenté)…"
docker exec -i "$CONTAINER" mysql -uroot -p"$ROOT_PW" \
  --max_allowed_packet=512M \
  --default-character-set=utf8mb4 \
  "$DB_NAME" < "$IMPORT_FILE"

echo "→ Terminé. Vérifie .env.local : DB_DATABASE=$DB_NAME, DB_HOST=127.0.0.1, DB_PORT=3308"
