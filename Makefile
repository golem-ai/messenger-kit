.PHONY: help
.DEFAULT_GOAL := help

COMPOSE_EXEC ?= docker-compose exec

# Prefix any command that should be run within the fpm docker container with $(EXEC_FPM)
ifeq (, $(shell which docker-compose))
	EXEC_PHP ?=
else
	EXEC_PHP ?= $(COMPOSE_EXEC) php
endif

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

vendor:
	$(MAKE) composer-install

.PHONY: composer-install
composer-install: ## Install PHP dependencies
	$(EXEC_PHP) composer install --no-interaction --no-progress

.PHONY: docker-sh
docker-sh: ## Starts a bash session in the php container
	docker-compose exec php /bin/bash

.PHONY: docker-up
docker-up: ## Start Docker containers
	docker-compose up --detach --build --remove-orphans

.PHONY: phpstan
phpstan: vendor ## Static analysis
	$(EXEC_PHP) vendor/bin/phpstan

.PHONY: cs
cs: vendor ## Coding standards check
	$(EXEC_PHP) vendor/bin/ecs check

.PHONY: cs-fix
cs-fix: vendor ## Coding standards fix
	$(EXEC_PHP) vendor/bin/ecs check --fix

.PHONY: phpunit
phpunit: vendor ## Tests
	$(EXEC_PHP) vendor/bin/phpunit

.PHONY: all
all: phpstan cs phpunit ## Runs all test/lint targets

.PHONY: pre-commit
pre-commit: cs-fix all