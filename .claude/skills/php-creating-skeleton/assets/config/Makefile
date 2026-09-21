# =============================================================================
# Makefile: Linux and macOS (Windows through WSL)
# Requires: GNU Make 4.x+, Docker
# =============================================================================

MAKEFLAGS += --no-print-directory --warn-undefined-variables
.DEFAULT_GOAL := help
.ONESHELL:
SHELL := bash
.SHELLFLAGS := -eu -o pipefail -c

# ─── Host ────────────────────────────────────────────────────
UNAME_S    := $(shell uname -s)
UNAME_M    := $(shell uname -m)
HOST_USER  := $(shell id -u):$(shell id -g)
DOCKER_GID := $(shell getent group docker 2>/dev/null | cut -d: -f3)
TTY        := $(shell [ -t 0 ] && echo "-it" || echo "-i")

PLATFORM :=
ifneq (,$(filter arm64 aarch64,$(UNAME_M)))
    PLATFORM := --platform=linux/amd64
endif

ifeq ($(UNAME_S),Darwin)
    OPEN_CMD := open
else
    OPEN_CMD := xdg-open
endif

# ─── Project ─────────────────────────────────────────────────
PROJECT_NAME   ?= <service-name>
PHP_VERSION    := $(shell grep -m1 '"php"' composer.json | sed -E 's/[^0-9]*([0-9]+\.[0-9]+).*/\1/')
RUN_MIGRATIONS ?=

BASE_IMAGE_NS      ?= <base-images>
BASE_IMAGE_VERSION ?= 1.0.0

PHP_IMAGE          ?= $(BASE_IMAGE_NS)/php:$(PHP_VERSION)-cli-$(BASE_IMAGE_VERSION)

PROXY_DIR          ?= <local-proxy>

TEST_CONTAINER  := $(PROJECT_NAME)-test
TEST_NETWORK    := $(PROJECT_NAME)-test_default
REPORT_DIR      := reports
COVERAGE_HTML   := $(REPORT_DIR)/coverage/coverage-html/index.html
MUTATION_REPORT := $(REPORT_DIR)/coverage/mutation-report.html

# ─── Docker runners ──────────────────────────────────────────
APP_RUN = docker run $(PLATFORM) -u $(HOST_USER) --rm \
	-e COMPOSER_AUTH \
	-e COMPOSER_HOME=/tmp/composer \
	-v $(CURDIR):/app -w /app \
	$(PHP_IMAGE)

APP_TEST_RUN = docker run $(PLATFORM) -u $(HOST_USER) --rm $(TTY) \
	$(if $(DOCKER_GID),--group-add $(DOCKER_GID)) \
	-e COMPOSER_AUTH \
	-e COMPOSER_HOME=/tmp/composer \
	-e TEST_NETWORK=$(TEST_NETWORK) \
	$(if $(RUN_MIGRATIONS),-e RUN_MIGRATIONS=$(RUN_MIGRATIONS)) \
	--name $(TEST_CONTAINER) \
	--network $(TEST_NETWORK) \
	-v $(CURDIR):/app -w /app \
	-v /var/run/docker.sock:/var/run/docker.sock \
	$(PHP_IMAGE)

# ─── Setup and run ───────────────────────────────────────────
.PHONY: start
start: ## @setup Start the service stack
	@if [ -n "$(PROXY_DIR)" ] && [ -d "$(PROXY_DIR)" ]; then ( cd "$(PROXY_DIR)" && docker compose up -d proxy ); fi
	@docker compose --env-file .env.local up -d --build

.PHONY: stop
stop: ## @setup Stop the service stack and drop its data volume
	@docker compose --env-file .env.local down --volumes

.PHONY: configure
configure: ## @setup Install dependencies and normalize composer.json
	@docker network inspect $(TEST_NETWORK) > /dev/null 2>&1 || docker network create $(TEST_NETWORK) > /dev/null
	@$(APP_RUN) composer configure

.PHONY: configure-and-update
configure-and-update: ## @setup Update dependencies and normalize composer.json
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
	@rm -rf $(REPORT_DIR) vendor .phpunit.cache *.lock

# ─── Help ────────────────────────────────────────────────────
.PHONY: help
help: ## @help Display this help message
	@echo "Usage: make [target]"
	@echo ""
	@awk 'BEGIN { \
		FS = ":.*?## @"; \
		section_order[1]="setup"; \
		section_order[2]="test"; \
		section_order[3]="review"; \
		section_order[4]="reports"; \
		section_order[5]="maintenance"; \
		section_order[6]="help"; \
		section_title["setup"]="Setup and run"; \
		section_title["test"]="Testing"; \
		section_title["review"]="Code review"; \
		section_title["reports"]="Reports"; \
		section_title["maintenance"]="Maintenance"; \
		section_title["help"]="Help"; \
	} \
	/^[a-zA-Z0-9_-]+:.*## @/ { \
		split($$2, parts, " "); \
		section = parts[1]; \
		desc = substr($$2, length(section) + 2); \
		targets[section] = targets[section] sprintf("  \033[36m%-32s\033[0m %s\n", $$1, desc); \
	} \
	END { \
		for (i = 1; i <= 6; i++) { \
			s = section_order[i]; \
			if (targets[s]) { \
				printf "\033[1m%s\033[0m\n%s\n", section_title[s], targets[s]; \
			} \
		} \
	}' $(MAKEFILE_LIST)
