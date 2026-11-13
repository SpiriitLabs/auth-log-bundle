.DEFAULT_GOAL := help

.PHONY: help
help: ## Liste les cibles disponibles
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

.PHONY: install
install: ## Installe les dépendances Composer
	composer install

.PHONY: test
test: ## Lance la suite PHPUnit
	composer test

.PHONY: test-filter
test-filter: ## Lance les tests correspondant à FILTER=…
	vendor/bin/simple-phpunit --filter=$(FILTER)

.PHONY: phpstan
phpstan: ## Lance l'analyse statique PHPStan
	vendor/bin/phpstan analyse --no-progress

.PHONY: cs-check
cs-check: ## Vérifie le code style (dry-run)
	composer cs-check

.PHONY: cs-fix
cs-fix: ## Corrige le code style
	composer cs-fix

.PHONY: rector
rector: ## Applique Rector
	vendor/bin/rector process

.PHONY: quality
quality: phpstan cs-check ## PHPStan + code style
