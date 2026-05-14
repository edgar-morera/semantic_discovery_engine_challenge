PHP  = docker compose exec php
CONSOLE = $(PHP) php bin/console

.PHONY: test test-unit seed-products lint cs cs-fix stan deptrac phpmd analyse k6-smoke k6-load k6-stress

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

## Performance testing
k6-smoke:
	docker compose --profile k6 run --rm k6 run /scripts/smoke.js

k6-load:
	docker compose --profile k6 run --rm -e BASE_URL=http://nginx k6 run --out json=/scripts/results/load.json /scripts/load.js

k6-stress:
	docker compose --profile k6 run --rm -e BASE_URL=http://nginx k6 run --out json=/scripts/results/stress.json /scripts/stress.js

test:
	$(PHP) php vendor/bin/phpunit

test-unit:
	$(PHP) php vendor/bin/phpunit --testsuite unit
