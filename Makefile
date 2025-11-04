analyze:
	php -d memory_limit=-1 ./vendor/bin/phpstan analyse

analyze_ci:
	php -d memory_limit=-1 ./vendor/bin/phpstan analyse --error-format github

check:
	PHP_CS_FIXER_IGNORE_ENV=1 php ./vendor/bin/php-cs-fixer fix --dry-run

lint:
	PHP_CS_FIXER_IGNORE_ENV=1 php ./vendor/bin/php-cs-fixer fix

test:
	php ./vendor/bin/phpunit

coverage:
	php -dpcov.enabled=1 -dpcov.directory=. -dpcov.exclude="~vendor~" ./vendor/bin/phpunit --coverage-text
