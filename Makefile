YARN = docker run --rm -u $(shell id -u):$(shell id -g) -e HOME=/tmp -v $(CURDIR):/app -w /app node:24 yarn

.PHONY: help
help: ## Liste les cibles disponibles
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.PHONY: docs-install
docs-install: ## Installe les dépendances de la doc (yarn.lock figé)
	$(YARN) install --frozen-lockfile

.PHONY: docs-upgrade
docs-upgrade: ## Met à jour les dépendances de la doc et régénère yarn.lock
	$(YARN) install
	$(YARN) upgrade

.PHONY: docs-build
docs-build: ## Génère le site de documentation
	$(YARN) docs:build
