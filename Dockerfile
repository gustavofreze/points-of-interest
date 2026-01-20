FROM alpine:3.20 AS flyway

ARG FLYWAY_VERSION=11.20.2
ARG FLYWAY_DIST=linux-alpine-x64

RUN apk add --no-cache curl tar ca-certificates \
    && mkdir -p /opt/flyway \
    && curl -fsSL "https://github.com/flyway/flyway/releases/download/flyway-${FLYWAY_VERSION}/flyway-commandline-${FLYWAY_VERSION}-${FLYWAY_DIST}.tar.gz" \
      | tar -xz --strip-components=1 -C /opt/flyway \
    && rm -rf /opt/flyway/jre

FROM gustavofreze/php:8.5-alpine-fpm

LABEL org.opencontainers.image.title="gustavofreze/points-of-interest"
LABEL org.opencontainers.image.authors="Gustavo Freze"

RUN apk add --no-cache openjdk21-jre-headless ca-certificates bash

COPY --from=flyway /opt/flyway /opt/flyway

RUN ln -sf /opt/flyway/flyway /usr/local/bin/flyway

WORKDIR /var/www/html

COPY ./ /var/www/html
COPY ./config/database /database
COPY ./entrypoint.sh /entrypoint.sh
COPY ./config/php/www.conf /usr/local/etc/php-fpm.d/www.conf

RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
