FROM gustavofreze/php:8.2-fpm

LABEL author="Gustavo Freze" \
      maintainer="Gustavo Freze" \
      org.label-schema.name="gustavofreze/points-of-interest" \
      org.label-schema.vcs-url="https://github.com/gustavofreze/points-of-interest/blob/main/Dockerfile" \
      org.label-schema.schema-version="1.0"

ARG FLYWAY_VERSION=10.19.0

RUN apk --no-cache add curl mysql-client openjdk21-jre tar \
    && mkdir -p /opt/flyway \
    && curl -L "https://repo1.maven.org/maven2/org/flywaydb/flyway-commandline/${FLYWAY_VERSION}/flyway-commandline-${FLYWAY_VERSION}-linux-x64.tar.gz" | tar -xz --strip-components=1 -C /opt/flyway \
    && rm -f /opt/flyway/jre/bin/java \
    && ln -s /usr/lib/jvm/java-11-openjdk/jre/bin/java /opt/flyway/jre/bin/java \
    && ln -s /opt/flyway/flyway /usr/local/bin/flyway \
    && apk del curl tar

WORKDIR /var/www/html

COPY ./db /db

RUN chmod +x /db/entrypoint.sh

ENTRYPOINT ["bash", "/db/entrypoint.sh"]
