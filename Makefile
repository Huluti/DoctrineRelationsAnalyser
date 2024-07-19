analyze:
	php ./vendor/bin/phpstan analyse

lint:
	php ./vendor/bin/php-cs-fixer fix

test:
	php ./vendor/bin/simple-phpunit