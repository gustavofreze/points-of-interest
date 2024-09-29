#!/bin/bash

wait_for_db() {
    attempt=0
    max_attempts=60
    db_ready=false

    while [ $attempt -lt $max_attempts ]; do
        if mysqladmin ping -h"$MYSQL_DATABASE_HOST" -u"$MYSQL_DATABASE_USER" -p"$MYSQL_DATABASE_PASSWORD" -P"$MYSQL_DATABASE_PORT" --silent; then
            db_ready=true
            break
        fi

        sleep 1
        ((attempt++))
    done

    if ! $db_ready; then
        echo "Database is not ready after $max_attempts attempts."
        exit 1
    fi
}

wait_for_db

flyway migrate

php-fpm -F
