analyze:
	php ./vendor/bin/phpstan analyse

analyze_ci:
	php ./vendor/bin/phpstan analyse --error-format github

check:
	php ./vendor/bin/php-cs-fixer fix --dry-run

lint:
	php ./vendor/bin/php-cs-fixer fix

test:
	php ./vendor/bin/simple-phpunit

coverage:
	php -dpcov.enabled=1 -dpcov.directory=. -dpcov.exclude="~vendor~" ./vendor/bin/simple-phpunit --coverage-text