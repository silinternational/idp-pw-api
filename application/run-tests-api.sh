#!/usr/bin/env bash
set -x
set -e

# Install composer dev dependencies
cd /data
runny composer install --prefer-dist --no-interaction --optimize-autoloader

# Copy test version of common/config/local.php if doesn't exist
if [ ! -f /data/common/config/local.php ]; then
    runny cp /data/common/config/test.php /data/common/config/local.php
fi

mkdir -p /data/runtime/mail

# Run database migrations
whenavail ${MYSQL_HOST} 3306 100 /data/yii migrate --interactive=0
whenavail ${MYSQL_HOST} 3306 100 /data/yii migrate --interactive=0 --migrationPath=console/migrations-test

# Start apache
runny apache2ctl start

# Run codeception tests
whenavail broker 80 100 echo "broker ready"
/data/vendor/bin/codecept run api -d


echo "Note: If there are unexpected errors, ensure the unit tests are run first and try again."