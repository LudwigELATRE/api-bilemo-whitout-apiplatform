# Makefile for Symfony project with API Platform

# Variables
PHP = php
COMPOSER = composer
CONSOLE = $(PHP) bin/console
DOCKER_COMPOSE = docker-compose
SYMFONY = symfony

# Default goal
.DEFAULT_GOAL := help

## —— Installation ———————————————————————————————————————————————————————————————
install: ## Install PHP dependencies
	$(COMPOSER) install

## —— Database ————————————————————————————————————————————————————————————————
db-create: ## Create the database
	$(CONSOLE) doctrine:database:create --if-not-exists

db-drop: ## Drop the database
	$(CONSOLE) doctrine:database:drop --force

db-update: ## Update the database schema
	$(CONSOLE) doctrine:schema:update --force

db-fixtures: ## Load data fixtures
	$(CONSOLE) doctrine:fixtures:load --no-interaction

db-reset: db-drop db-create db-update db-fixtures ## Reset the database

## —— Tests ———————————————————————————————————————————————————————————————————
test: ## Run PHPUnit tests
	$(PHP) bin/phpunit

## —— Development ——————————————————————————————————————————————————————————————
serve: ## Start the local web server
	$(SYMFONY) serve -d

stop: ## Stop the local web server
	$(SYMFONY) server:stop

## —— Docker —————————————————————————————————————————————————————————————————
docker-up: ## Start Docker containers
	$(DOCKER_COMPOSE) up -d

docker-down: ## Stop Docker containers
	$(DOCKER_COMPOSE) down

## —— Help ————————————————————————————————————————————————————————————————————
help: ## Display this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'