#!/usr/bin/env bash

set -e

# Install composer dev dependencies
cd /data
composer install --prefer-dist --no-interaction --optimize-autoloader --no-progress

# Run behat integration tests
./vendor/bin/behat --config=tests/features/behat.yml --strict --profile=integration
