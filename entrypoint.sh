#!/bin/bash

printf "Waiting for Database to start... \n\n"

sleep 15

flyway -connectRetries=15 migrate

php-fpm -F
