PHP  = docker compose exec php
CONSOLE = $(PHP) php bin/console

.PHONY: test test-unit seed-products

seed-products:
	$(CONSOLE) app:seed:siroko-products

test:
	$(PHP) php vendor/bin/phpunit

test-unit:
	$(PHP) php vendor/bin/phpunit --testsuite unit
