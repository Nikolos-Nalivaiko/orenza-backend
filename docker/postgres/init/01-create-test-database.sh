#!/bin/bash
# Отдельная база для тестов, чтобы прогон не затирал данные разработки.
# Скрипт выполняется только при первичной инициализации тома postgres.
set -euo pipefail

psql --username "${POSTGRES_USER}" --dbname "${POSTGRES_DB}" <<-EOSQL
    CREATE DATABASE "${POSTGRES_DB}_test" OWNER "${POSTGRES_USER}";
EOSQL

echo "created database ${POSTGRES_DB}_test"
