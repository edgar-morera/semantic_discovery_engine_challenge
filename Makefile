PHP  = docker compose exec php
CONSOLE = $(PHP) php bin/console

.PHONY: test test-unit seed-products lint cs cs-fix stan deptrac phpmd analyse

seed-products:
	$(CONSOLE) app:seed:siroko-products

## Static analysis & code quality
lint:
	$(PHP) php -l src

cs:
	$(PHP) php vendor/bin/php-cs-fixer check --diff

cs-fix:
	$(PHP) php vendor/bin/php-cs-fixer fix

stan:
	$(PHP) php -d memory_limit=512M vendor/bin/phpstan analyse

deptrac:
	$(PHP) php vendor/bin/deptrac analyse --config-file=deptrac.yaml

phpmd:
	$(PHP) php vendor/bin/phpmd src text phpmd.xml

analyse: cs stan deptrac phpmd

test:
	$(PHP) php vendor/bin/phpunit

test-unit:
	$(PHP) php vendor/bin/phpunit --testsuite unit
