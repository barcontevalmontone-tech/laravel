start:
	docker compose -f docker-compose.yaml up -d

connect:
	docker compose -f docker-compose.yaml exec app bash

stop:
	docker compose -f docker-compose.yaml down
