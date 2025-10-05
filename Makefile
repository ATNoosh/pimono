SHELL := /bin/sh

BACKEND := mini-wallet-backend
FRONTEND := mini-wallet-frontend

.PHONY: help
help:
	@echo "Targets:"
	@echo "  setup-backend       - composer install, migrate"
	@echo "  serve-backend       - php artisan serve"
	@echo "  migrate             - run php artisan migrate"
	@echo "  outbox-dispatch     - run outbox dispatcher once"
	@echo "  setup-frontend      - npm install (frontend)"
	@echo "  dev-frontend        - npm run dev (frontend)"
	@echo "  build-frontend      - npm run build (frontend)"

.PHONY: setup-backend
setup-backend:
	cd $(BACKEND) && composer install && php artisan migrate

.PHONY: serve-backend
serve-backend:
	cd $(BACKEND) && php artisan serve

.PHONY: migrate
migrate:
	cd $(BACKEND) && php artisan migrate

.PHONY: outbox-dispatch
outbox-dispatch:
	cd $(BACKEND) && php artisan outbox:dispatch --limit=500

.PHONY: setup-frontend
setup-frontend:
	cd $(FRONTEND) && npm install

.PHONY: dev-frontend
dev-frontend:
	cd $(FRONTEND) && npm run dev

.PHONY: build-frontend
build-frontend:
	cd $(FRONTEND) && npm run build


