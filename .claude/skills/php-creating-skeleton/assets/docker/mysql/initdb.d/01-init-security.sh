#!/bin/sh

# 01-init-security.sh
# Creates the least-privilege application user and grants it DML on the service schema.
# Usage: run once by the MySQL image entrypoint on first container start, never invoked standalone.
# Arguments: none. Reads <SERVICE_NAME>_APP_USER, <SERVICE_NAME>_APP_PASSWORD, and MYSQL_DATABASE from the environment.

: "${<SERVICE_NAME>_APP_USER:?missing}"
: "${<SERVICE_NAME>_APP_PASSWORD:?missing}"

docker_process_sql --database=mysql <<EOSQL
CREATE USER IF NOT EXISTS '${<SERVICE_NAME>_APP_USER}'@'%' IDENTIFIED BY '${<SERVICE_NAME>_APP_PASSWORD}';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`${MYSQL_DATABASE}\`.* TO '${<SERVICE_NAME>_APP_USER}'@'%';
EOSQL
