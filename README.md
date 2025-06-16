# AttributeExecutionBundle

[![CI](https://github.com/arnaud-23/attribute-execution-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/arnaud-23/attribute-execution-bundle/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/Symfony-6.0%2B-blue.svg)](https://symfony.com)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Code Coverage](https://codecov.io/gh/arnaud-23/attribute-execution-bundle/branch/main/graph/badge.svg)](https://codecov.io/gh/arnaud-23/attribute-execution-bundle)

Generic attribute execution system for Symfony.

## Features

- Attribute-based middleware pipeline
- Built-in middleware for:
  - Security (role-based access control)
  - Cache (with configurable strategies)
  - Transaction management
- Extensible architecture for custom middleware
- High test coverage
- Static analysis with PHPStan level 8
- PSR-12 compliant code style

## Installation

```bash
composer require arnaud-23/attribute-execution-bundle
```

## Setup

The bundle will be automatically registered in your Symfony application. No additional configuration is required to start using the attributes.

### Service Configuration

Services using the bundle's attributes are automatically configured with the attribute proxy. You don't need to add any additional tags or configuration.

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false

    # Your services will be automatically configured when using attributes
    App\Service\YourService: ~
```

### Cache Configuration

If you're using the cache attribute, you can configure the cache strategies in your configuration:

```yaml
# config/packages/attribute_execution.yaml
attribute_execution:
    cache:
        strategies:
            array: ~  # Uses Symfony's ArrayAdapter
            redis:    # Uses Redis
                dsn: 'redis://localhost:6379'
                options:
                    prefix: 'app_cache_'
```

### Security Configuration

The security attribute requires the Symfony Security component to be installed:

```bash
composer require symfony/security-bundle
```

## Usage

```php
use Arnaud23\AttributeExecutionBundle\Attribute\Security;
use Arnaud23\AttributeExecutionBundle\Attribute\Cache;
use Arnaud23\AttributeExecutionBundle\Attribute\Transactional;

class YourService
{
    #[Security('ROLE_ADMIN')]
    #[Cache(strategy: 'redis', ttl: 3600)]
    #[Transactional('default')]
    public function yourMethod(): mixed
    {
        // Your code here
    }
}
```

### Available Attributes

#### Security
```php
#[Security('ROLE_ADMIN')]  // Method or class level
```

#### Cache
```php
#[Cache]                    // Uses default strategy (array) with 300s TTL
#[Cache(strategy: 'custom')] // Uses custom strategy
#[Cache(ttl: 3600)]         // Custom TTL in seconds
```

#### Transaction
```php
#[Transactional]            // Uses default connection
#[Transactional('custom')]  // Uses custom connection
```

## Development

```bash
# Install dependencies
composer install

# Run tests
make test

# Run tests with coverage
make coverage

# Generate HTML coverage report
make coverage-html

# Run static analysis
make phpstan
```

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email arnaud.h.lefevre@gmail.com instead of using the issue tracker. See our [Security Policy](SECURITY.md) for more details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## License

This bundle is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Credits

- [Arnaud Lefevre](https://github.com/arnaud-23) - Creator and maintainer