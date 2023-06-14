FROM gustavofreze/php:8.2.6-fpm

RUN docker-php-ext-install mysqli pdo_mysql
