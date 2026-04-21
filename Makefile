SHELL := /bin/sh

PHP ?= 8.5
NETWORK ?=
IMAGE ?= ghcr.io/phpyh/php:$(PHP)

# Docker

DOCKER ?= docker
DOCKER_COMPOSE ?= $(DOCKER) compose
export HOST_USER ?= $(shell id -u):$(shell id -g)
TTY ?= $(if $(shell test -t 0 && echo 1),--tty)

TOOLS ?= $(if $(IN_CONTAINER),,$(DOCKER) run \
	--rm --interactive $(TTY) --init --user $(HOST_USER) \
	--env-file .env$(if $(wildcard .env.local), --env-file .env.local) \
	--env IN_CONTAINER=true \
	--env HISTFILE=/workspace/var/.docker_history \
	--env COMPOSER_CACHE_DIR=/workspace/var/.composer_cache \
	--volume .:/workspace:cached \
	--workdir /workspace \
	$(if $(NETWORK),--network $(NETWORK)) \
	$(IMAGE))

RUN ?= $(if $(IN_CONTAINER),,$(DOCKER_COMPOSE) run --rm php)

t: terminal
terminal: var ## (t) Open a shell in the PHP container
	@$(if $(IN_CONTAINER),echo 'Already inside docker container.'; exit 1,)
	$(RUN) /bin/sh
.PHONY: t terminal

run: ## Run a command in the PHP container: ARGS='php --version'
	$(RUN) $(ARGS)
.PHONY: run

up: ## Start services
	$(DOCKER_COMPOSE) up --remove-orphans --build --detach $(ARGS)
.PHONY: up

down: ## Stop services
	$(DOCKER_COMPOSE) down --remove-orphans $(ARGS)
.PHONY: down

# Composer

COMPOSER ?= $(TOOLS) composer

i: install
install: ## (i) Install dependencies
	$(COMPOSER) install
	@rm -f vendor/.lowest
	@touch vendor
.PHONY: i install

u: update
update: ## (u) Update dependencies
	$(COMPOSER) update
	@rm -f vendor/.lowest
	@touch vendor
.PHONY: u update

il: install-lowest
install-lowest: ## (il) Install lowest possible dependencies
	@if [ -f composer.lock ]; then echo 'Recipe `install-lowest` is not available in projects with `composer.lock`'; exit 1; fi
	$(COMPOSER) update --prefer-lowest --prefer-stable
	@touch vendor/.lowest
	@touch vendor
.PHONY: il install-lowest

c: composer
composer: ## (c) Run Composer: ARGS='require foo/bar'
	$(COMPOSER) $(ARGS)
.PHONY: c composer

composer-validate: ## Validate composer.json
	$(COMPOSER) validate --no-check-publish $(ARGS)
.PHONY: composer-validate

composer-normalize: ## Normalize composer.json
	$(COMPOSER) normalize --no-check-lock --no-update-lock --diff $(ARGS)
.PHONY: composer-normalize

composer-normalize-check: ## Check composer.json is normalized
	$(COMPOSER) normalize --diff --dry-run $(ARGS)
.PHONY: composer-normalize-check

# Quality

fixer: var ## Fix code style
	$(TOOLS) php-cs-fixer fix --diff --verbose $(ARGS)
.PHONY: fixer

fixer-check: var ## Check code style
	$(TOOLS) php-cs-fixer fix --diff --verbose --dry-run $(ARGS)
.PHONY: fixer-check

rector: var vendor ## Apply Rector rules
	$(TOOLS) rector process $(ARGS)
.PHONY: rector

rector-check: var vendor ## Check Rector rules
	$(TOOLS) rector process --dry-run $(ARGS)
.PHONY: rector-check

phpstan: var vendor ## Run static analysis
	$(TOOLS) phpstan analyze --memory-limit=1G $(ARGS)
.PHONY: phpstan

test: var vendor up ## Run the test suite
	$(TOOLS) vendor/bin/phpunit $(ARGS)
.PHONY: test

infect: var vendor up ## Run mutation testing
	$(TOOLS) infection --show-mutations $(ARGS)
.PHONY: infect

deps-analyze: vendor ## Check for unused/missing dependencies
	$(TOOLS) composer-dependency-analyser $(ARGS)
.PHONY: deps-analyze

# CI

fix: fixer rector composer-normalize ## Fix code style and normalize composer.json
.PHONY: fix

check: fixer-check rector-check composer-validate composer-normalize-check deps-analyze phpstan ## Run all checks
.PHONY: check

rescaffold:
	$(DOCKER) run \
	  --volume .:/project \
	  --user $(HOST_USER) \
	  --interactive $(TTY) --rm --init \
	  --pull always \
	  ghcr.io/phpyh/scaffolder:latest \
	  --user-name-default '$(shell git config user.name 2>/dev/null || whoami 2>/dev/null)' \
	  --user-email-default '$(shell git config user.email 2>/dev/null)'
	git add --all 2>/dev/null || true
.PHONY: rescaffold

# ---

var:
	mkdir var

vendor: composer.json $(wildcard composer.lock)
	@if [ -f vendor/.lowest ]; then $(MAKE) install-lowest; else $(MAKE) install; fi

help:
	@awk 'BEGIN {FS=":.*?## "} /^# [A-Za-z]/ {printf "%s\033[1;33m[%s]\033[0m\n", (s ? "\n" : ""), substr($$0, 3); s=1} /^[a-zA-Z0-9_-]+:.*## / {printf "\033[32m%-28s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
.PHONY: help

.DEFAULT_GOAL := help
