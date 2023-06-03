DOCKER_RUN = docker run --rm -it --net=host -v ${PWD}:/app -w /app gustavofreze/php:8.1.7-fpm
DOCKER_EXEC = docker exec -it points-of-interest
DOCKER_COMPOSE = docker-compose
DOCKER_IMAGE_PRUNE = docker image prune --all --force
DOCKER_NETWORK_PRUNE = docker network prune --force

FLYWAY = docker run --rm -v ${PWD}/db/mysql/migrations:/flyway/sql --network=points-of-interest_default --env-file=config/configs.env flyway/flyway:9.1.2-alpine

.PHONY: configure run test test-no-coverage review show-reports stop clean clean-all

configure:
	@${DOCKER_COMPOSE} up -d --build
	@${DOCKER_EXEC} composer update --optimize-autoloader
	@${FLYWAY} migrate

run:
	@${DOCKER_RUN} composer update --optimize-autoloader

test: run review
	@${DOCKER_RUN} composer tests

test-no-coverage: run review
	@${DOCKER_RUN} composer tests-no-coverage

review:
	@${DOCKER_RUN} composer review

show-reports:
	@sensible-browser report/coverage/coverage-html/index.html report/coverage/mutation-report.html

stop:
	@${DOCKER_COMPOSE} stop $(docker ps -a -q)

clean: stop
	@${DOCKER_COMPOSE} rm -vf $(docker ps -a -q)
	@${DOCKER_NETWORK_PRUNE} --filter label="com.docker.compose.project"="points-of-interest"

clean-all: clean
	@${DOCKER_IMAGE_PRUNE} --filter label="org.opencontainers.image.title"="Traefik"
	@${DOCKER_IMAGE_PRUNE} --filter label="com.docker.compose.project"="points-of-interest"
	@${DOCKER_IMAGE_PRUNE} --filter label="org.label-schema.name"="gustavofreze/php:8.1.7-fpm"
	@${DOCKER_IMAGE_PRUNE} --filter label="maintainer"="NGINX Docker Maintainers <docker-maint@nginx.com>"
