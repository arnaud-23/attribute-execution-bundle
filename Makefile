.PHONY: setup start stop test phpstan clean coverage coverage-html xdebug-coverage xdebug-debug xdebug-off

# Setup the project
setup:
	docker-compose build
	docker-compose run --rm php composer install

# Start the containers
start:
	docker-compose up -d

# Stop the containers
stop:
	docker-compose down

# Run PHPUnit tests
test:
	docker-compose exec php vendor/bin/phpunit -c phpunit.xml.dist --testdox

# Run PHPUnit tests with coverage
coverage:
	docker-compose exec php mkdir -p build/coverage
	docker-compose exec php chmod -R 777 build/coverage
	docker-compose exec php vendor/bin/phpunit -c phpunit.xml.dist --coverage-clover build/coverage/coverage.xml

# Run PHPUnit tests with HTML coverage report
coverage-html:
	docker-compose exec php mkdir -p build/coverage/html
	docker-compose exec php chmod -R 777 build/coverage
	docker-compose exec php vendor/bin/phpunit -c phpunit.xml.dist --coverage-html build/coverage/html

# Run PHPStan analysis
phpstan:
	docker-compose exec php vendor/bin/phpstan analyse -c phpstan.neon --ansi

# Clean up generated files and containers
clean:
	docker-compose down -v
	rm -rf vendor/
	rm -rf .phpunit.cache/
	rm -rf build/coverage/