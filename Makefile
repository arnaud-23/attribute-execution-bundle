.PHONY: setup start stop test phpstan clean

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

# Run PHPStan analysis
phpstan:
	docker-compose exec php vendor/bin/phpstan analyse -c phpstan.neon --ansi

# Clean up generated files and containers
clean:
	docker-compose down -v
	rm -rf vendor/
	rm -rf .phpunit.cache/ 