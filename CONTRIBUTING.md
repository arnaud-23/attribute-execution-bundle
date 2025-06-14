# Contributing to AttributeExecutionBundle

Thank you for your interest in contributing to AttributeExecutionBundle! This document provides guidelines and instructions for contributing.

## Code of Conduct

Please be respectful and considerate of others when contributing to this project.

## Development Setup

1. Fork the repository
2. Clone your fork:
   ```bash
   git clone https://github.com/your-username/attribute-execution-bundle.git
   cd attribute-execution-bundle
   ```
3. Install dependencies:
   ```bash
   composer install
   ```

## Development Workflow

1. Create a new branch for your feature/fix:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. Make your changes and ensure tests pass:
   ```bash
   make test
   make phpstan
   ```

3. Commit your changes with a clear commit message:
   ```bash
   git commit -m "Description of your changes"
   ```

4. Push to your fork and create a Pull Request

## Pull Request Guidelines

- Ensure your PR description clearly describes the changes
- Include tests for new features or bug fixes
- Update documentation if necessary
- Follow the existing code style
- Make sure all tests pass
- Ensure PHPStan analysis passes

## Testing

- Write unit tests for new features
- Ensure existing tests pass
- Run the full test suite:
  ```bash
  make test
  ```

## Code Quality

- Follow PSR-12 coding standards
- Run static analysis:
  ```bash
  make phpstan
  ```
- Run code style checks:
  ```bash
  make ecs
  ```

## Documentation

- Update README.md if necessary
- Add PHPDoc blocks to new classes and methods
- Update examples if the API changes

## Release Process

1. Update version in composer.json
2. Update CHANGELOG.md
3. Create a new release tag
4. Push changes to main branch

Thank you for contributing! 