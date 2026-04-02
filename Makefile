# Local dev — docker-compose.local.yml
COMPOSE := docker compose -f docker-compose.local.yml

.PHONY: help queue queue-d queue-stop

help:
	@echo "Targets:"
	@echo "  make queue       — Laravel queue worker (foreground, Ctrl+C to stop)"
	@echo "  make queue-d     — same worker in background (docker exec -d)"
	@echo "  make queue-stop  — stop background workers in the backend container"

queue:
	$(COMPOSE) exec -it backend php artisan queue:work --verbose --tries=3 --timeout=60

queue-d:
	$(COMPOSE) exec -d backend php artisan queue:work --sleep=3 --tries=3 --timeout=60 --verbose

queue-stop:
	$(COMPOSE) exec backend sh -c 'pkill -f "queue:work" || true'
