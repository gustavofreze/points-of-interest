# Container-first workflow. Every PHP and Composer command runs inside the cli image, so nothing beyond Docker and
# GNU Make is installed on the host. The composer scripts are the single source of truth for what each target runs.

SHELL := /bin/bash
.ONESHELL:
.SHELLFLAGS := -eu -o pipefail -c
.DEFAULT_GOAL := help

PROJECT_NAME := points-of-interest
PHP_VERSION  := $(shell grep -m1 '"php"' composer.json | grep -oE '[0-9]+\.[0-9]+')
PHP_IMAGE    := gustavofreze/php:$(PHP_VERSION)-cli-1.0.4

TEST_NETWORK := $(PROJECT_NAME)-test_default

REPORT_DIR       := reports
COVERAGE_HTML    := $(REPORT_DIR)/coverage/coverage-html/index.html
MUTATION_REPORT  := $(REPORT_DIR)/coverage/mutation-report.html

HOST_USER    := $(shell id -u):$(shell id -g)
DOCKER_GID   := $(shell getent group docker 2>/dev/null | cut -d: -f3)
DOCKER_GROUP := $(if $(DOCKER_GID),--group-add $(DOCKER_GID),)
INTERACTIVE  := $(shell [ -t 0 ] && echo -it)
OPEN_CMD     := $(shell command -v sensible-browser 2>/dev/null || command -v xdg-open 2>/dev/null || echo open)

APP_RUN = docker run $(INTERACTIVE) --rm \
	-u $(HOST_USER) \
	-e COMPOSER_HOME=/tmp/composer \
	-e COMPOSER_AUTH \
	-v $(CURDIR):/app -w /app \
	$(PHP_IMAGE)

APP_TEST_RUN = docker run $(INTERACTIVE) --rm \
	-u $(HOST_USER) \
	$(DOCKER_GROUP) \
	-e COMPOSER_HOME=/tmp/composer \
	-e COMPOSER_AUTH \
	-e TEST_NETWORK=$(TEST_NETWORK) \
	--name $(PROJECT_NAME)-test \
	--network $(TEST_NETWORK) \
	-v $(CURDIR):/app -w /app \
	-v /var/run/docker.sock:/var/run/docker.sock \
	$(PHP_IMAGE)

# ─── Setup and run ───────────────────────────────────────────
.PHONY: start
start: ## @setup Start the service stack
	@docker compose --env-file .env.local up -d --build
	@docker compose --env-file .env.local rm -f $(PROJECT_NAME)-migrate

.PHONY: stop
stop: ## @setup Stop the service stack and drop its data volume
	@docker compose --env-file .env.local down --volumes

.PHONY: configure
configure: ## @setup Install dependencies
	@docker network inspect $(TEST_NETWORK) > /dev/null 2>&1 || docker network create $(TEST_NETWORK) > /dev/null
	@$(APP_RUN) composer configure

.PHONY: configure-and-update
configure-and-update: ## @setup Update dependencies
	@$(APP_RUN) composer configure-and-update

# ─── Testing ─────────────────────────────────────────────────
.PHONY: tests
tests: ## @test Run all tests with coverage and mutation testing
	@$(APP_TEST_RUN) composer tests

.PHONY: test-file
test-file: ## @test Run a single test file (usage: make test-file FILE=ClassNameTest)
	@$(APP_TEST_RUN) composer test-file $(FILE)

# ─── Code review ─────────────────────────────────────────────
.PHONY: review
review: ## @review Run static code analysis
	@$(APP_RUN) composer review

.PHONY: fix-review
fix-review: ## @review Fix static code analysis issues
	@$(APP_RUN) composer fix-review

# ─── Reports ─────────────────────────────────────────────────
.PHONY: show-reports
show-reports: ## @reports Open coverage and mutation reports in the browser
	@$(OPEN_CMD) $(COVERAGE_HTML)
	@$(OPEN_CMD) $(MUTATION_REPORT)

.PHONY: show-outdated
show-outdated: ## @reports Show outdated direct dependencies
	@$(APP_RUN) composer outdated --direct

# ─── Maintenance ─────────────────────────────────────────────
.PHONY: clean
clean: ## @maintenance Remove dependencies and generated artifacts
	@rm -rf $(REPORT_DIR) vendor .phpunit.cache

# ─── Help ────────────────────────────────────────────────────
.PHONY: help
help: ## @help Display this help message
	@echo "Usage: make [target]"
	@echo ""
	@awk 'BEGIN { \
		FS = ":.*?## @"; \
		order[1]="setup"; order[2]="test"; order[3]="review"; \
		order[4]="reports"; order[5]="maintenance"; order[6]="help"; \
		title["setup"]="Setup and run"; title["test"]="Testing"; title["review"]="Code review"; \
		title["reports"]="Reports"; title["maintenance"]="Maintenance"; title["help"]="Help"; \
	} \
	/^[a-zA-Z0-9_-]+:.*## @/ { \
		split($$2, parts, " "); \
		section = parts[1]; \
		description = substr($$2, length(section) + 2); \
		entries[section] = entries[section] sprintf("\033[36m%-24s\033[0m %s\n", $$1, description); \
	} \
	END { \
		for (index_ = 1; index_ <= 6; index_++) { \
			section = order[index_]; \
			if (entries[section] != "") { \
				printf "%s\n", title[section]; \
				printf "%s\n", entries[section]; \
			} \
		} \
	}' $(MAKEFILE_LIST)
