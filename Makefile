.PHONY: up down restart logs shell-api shell-client test

# Construit les images puis démarre l'API et le client
up:
	docker compose up --build -d

# Arrête et supprime les conteneurs
down:
	docker compose down

# Arrête puis reconstruit et redémarre les services
restart: down up

logs:
	docker compose logs -f

shell-api:
	docker compose exec api bash

shell-client:
	docker compose exec client sh

# Tests du back (PHPUnit) et du front (Vitest) ; les services doivent être démarrés
test:
	docker compose exec -T api bin/phpunit
	docker compose exec -T client npx ng test --watch=false
