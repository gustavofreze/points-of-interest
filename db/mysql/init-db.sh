#!/bin/bash

DATABASE_NAME="poi_adm"

CREDENTIALS="/scripts/config/credentials.cnf"
CREATE_DATABASE="CREATE DATABASE ${DATABASE_NAME};"

# shellcheck disable=SC2059
printf "\n\nRunning DDL commands for the database ${DATABASE_NAME} ... \n\n"

mysql --defaults-extra-file="${CREDENTIALS}" -e "${CREATE_DATABASE}"

printf "\n\nEnd of execution. \n"
