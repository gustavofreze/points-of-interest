PWD := $(shell pwd -L)
PHP_IMAGE := gustavofreze/php:8.2
APP_RUN := docker run -u root --rm -it --network=host -v ${PWD}:/app -w /app ${PHP_IMAGE}

FLYWAY_IMAGE := flyway/flyway:10.19.0
FLYWAY_RUN := docker run --rm -v ${PWD}/db/mysql/migrations:/flyway/sql --env-file=config/local.env --link points-of-interest-adm --network=points-of-interest_default ${FLYWAY_IMAGE}
MIGRATE_DB := ${FLYWAY_RUN} -locations=filesystem:/flyway/sql -schemas=poi_adm

.DEFAULT_GOAL := help

.PHONY: start configure test review fix-style show-reports clean migrate-database clean-database help

start: ## Start Docker compose services
	@docker-compose up -d --build

configure: ## Configure development environment
	@${APP_RUN} composer update --optimize-autoloader

test: ## Run all tests
	@${APP_RUN} composer run tests

review: ## Run code review
	@${APP_RUN} composer review

fix-style: ## Fix code style
	@${APP_RUN} composer fix-style

show-reports: ## Open code coverage reports in browser
	@sensible-browser report/coverage/coverage-html/index.html report/coverage/mutation-report.html

migrate-database: ## Run database migrations
	@${MIGRATE_DB} migrate

clean-database: ## Clean database
	@${MIGRATE_DB} clean

help: ## Display this help message
	@echo "Usage: make [target]"
	@echo ""
	@echo "Setup and run"
	@grep -E '^(configure|start|migrate-database|clean-database):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Testing"
	@grep -E '^(test|show-reports):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Code review "
	@grep -E '^(review|fix-style):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
	@echo ""
