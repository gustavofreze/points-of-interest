#!/bin/bash

DATABASE_NAME="poi_adm"

CREDENTIALS="/scripts/config/credentials.cnf"
CREATE_DATABASE="CREATE DATABASE IF NOT EXISTS ${DATABASE_NAME};"

printf "\n\nRunning DDL commands for the database %s ... \n\n" "${DATABASE_NAME}"

mysql --defaults-extra-file="${CREDENTIALS}" -e "${CREATE_DATABASE}"

printf "\n\nEnd of execution. \n"
