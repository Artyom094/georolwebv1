#!/usr/bin/env bash
set -euo pipefail

# Uso: ./restore_db.sh path/to/backup.sql.gz
if [ "$#" -ne 1 ]; then
  echo "Usage: $0 backup-file.sql.gz"
  exit 2
fi

FILE="$1"
if [ ! -f "$FILE" ]; then
  echo "File not found: $FILE"
  exit 3
fi

HOST=${DB_HOST:-localhost}
USER=${DB_USER:-}
PASS=${DB_PASS:-}

if [ -z "$USER" ]; then
  echo "DB_USER not set. Export DB_USER and DB_PASS or provide .env before running."
  exit 1
fi

gunzip -c "$FILE" | mysql -h "$HOST" -u "$USER" -p"$PASS"
echo "Restore completed"
