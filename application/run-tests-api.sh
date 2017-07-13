#!/usr/bin/env bash
set -x

# Install composer dev dependencies
cd /data
runny composer install --prefer-dist --no-interaction --optimize-autoloader

# Copy test version of common/config/local.php
cp /data/common/config/local.test.php /data/common/config/local.php

mkdir -p /data/runtime/mail

# Run database migrations
whenavail ${MYSQL_HOST} 3306 100 /data/yii migrate --interactive=0
whenavail ${MYSQL_HOST} 3306 100 /data/yii migrate --interactive=0 --migrationPath=console/migrations-test

# Start apache
runny apache2ctl start

# Run codeception tests
/data/vendor/bin/codecept run api -d
