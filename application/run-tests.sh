#!/usr/bin/env bash

set -e

# Install composer dev dependencies
cd /data
composer install --prefer-dist --no-interaction --optimize-autoloader --no-progress

mkdir -p /data/runtime/mail

# Run database migrations
whenavail ${MYSQL_HOST} 3306 100 /data/yii migrate --interactive=0

# Install and enable xdebug for code coverage
apt-get install -y php-xdebug

# Run codeception tests
whenavail broker 80 100 echo "broker ready, running unit tests..."
./vendor/bin/codecept run unit

# Run local behat tests
./vendor/bin/behat --config=tests/features/behat.yml --strict --profile=local
