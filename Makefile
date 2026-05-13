PHP  = docker compose exec php
CONSOLE = $(PHP) php bin/console

.PHONY: test test-unit

test:
	$(PHP) php vendor/bin/phpunit

test-unit:
	$(PHP) php vendor/bin/phpunit --testsuite unit
