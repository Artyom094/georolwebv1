#!/usr/bin/env bash
set -euo pipefail

# Usa variables de entorno: DB_HOST, DB_USER, DB_PASS, DB_NAME, BACKUP_DIR
HOST=${DB_HOST:-localhost}
USER=${DB_USER:-}
PASS=${DB_PASS:-}
DB=${DB_NAME:-georol_cartillas}
OUT_DIR=${BACKUP_DIR:-./backups}

mkdir -p "$OUT_DIR"
TIMESTAMP=$(date +%F_%H%M%S)

if [ -z "$USER" ]; then
  echo "DB_USER not set. Export DB_USER and DB_PASS or provide .env before running."
  exit 1
fi

mysqldump -h "$HOST" -u "$USER" -p"$PASS" "$DB" | gzip > "$OUT_DIR/${DB}_$TIMESTAMP.sql.gz"
echo "Backup saved to $OUT_DIR/${DB}_$TIMESTAMP.sql.gz"
