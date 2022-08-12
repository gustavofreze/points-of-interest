FROM gustavofreze/php:8.1.7-fpm

RUN docker-php-ext-install mysqli pdo pdo_mysql
