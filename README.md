# AttributeExecutionBundle

[![CI](https://github.com/arnaud-23/attribute-execution-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/arnaud-23/attribute-execution-bundle/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/Symfony-6.0%2B-blue.svg)](https://symfony.com)

Generic attribute execution system for Symfony.

## Features

- Attribute-based middleware pipeline
- Built-in middleware for:
  - Security (role-based access control)
  - Cache (with configurable strategies)
  - Transaction management
- Extensible architecture for custom middleware

## Installation

```bash
composer require arnaud-23/attribute-execution-bundle
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

## Development

```bash
# Install dependencies
composer install

# Run tests
make test

# Run static analysis
make phpstan
```

## License

This bundle is licensed under the MIT License.