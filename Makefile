PWD := $(CURDIR)
ARCH := $(shell uname -m)
PLATFORM :=

ifeq ($(ARCH),arm64)
    PLATFORM := --platform=linux/amd64
endif

PHP_IMAGE = gustavofreze/php:8.5-alpine
FLYWAY_IMAGE = flyway/flyway:11.20.2

APP_RUN := docker run -u root --rm -it --network=host -v ${PWD}:/app -w /app ${PHP_IMAGE}

FLYWAY_RUN = docker run ${PLATFORM} --rm -v ${PWD}/config/database/mysql/migrations:/flyway/sql --env-file=config/local.env --network=points-of-interest_default ${FLYWAY_IMAGE}
MIGRATE_DB = ${FLYWAY_RUN} -locations=filesystem:/flyway/sql -schemas=poi_adm -connectRetries=15

.DEFAULT_GOAL := help

.PHONY: start
start: ## Start application containers
	@docker compose up -d --build

.PHONY: stop
stop: ## Stop application containers
	@docker compose down

.PHONY: configure
configure: ## Configure development environment
	@${APP_RUN} composer update --optimize-autoloader

.PHONY: test
test: ## Run all tests with coverage
	@${APP_RUN} composer run tests

.PHONY: review
review: ## Run static code analysis
	@${APP_RUN} composer review

.PHONY: show-reports
show-reports: ## Open static analysis reports (e.g., coverage, lints) in the browser
	@sensible-browser report/coverage/coverage-html/index.html report/coverage/mutation-report.html

.PHONY: migrate-database
migrate-database: ## Run database migrations
	@${MIGRATE_DB} migrate

.PHONY: clean-database
clean-database: ## Clean database
	@${MIGRATE_DB} clean

.PHONY: help
help: ## Display this help message
	@echo "Usage: make [target]"
	@echo ""
	@echo "Setup and run"
	@grep -E '^(start|stop|configure|migrate-database|clean-database):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Testing"
	@grep -E '^(test):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Code review"
	@grep -E '^(review):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Reports"
	@grep -E '^(show-reports):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Help"
	@grep -E '^(help):.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
