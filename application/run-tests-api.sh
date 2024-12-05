#!/usr/bin/env bash
set -x

# Install composer dev dependencies
cd /data
composer install --prefer-dist --no-interaction --optimize-autoloader --no-progress

mkdir -p /data/runtime/mail

# Run database migrations
whenavail ${MYSQL_HOST} 3306 100 /data/yii migrate --interactive=0

# Start apache
apache2ctl start

# Run codeception tests
whenavail broker 80 100 echo "broker ready"
/data/vendor/bin/codecept run api -d
TESTRESULTS_API=$?

echo "Note: If there are unexpected errors, try 'make clean' or manually redo id-broker test migration."

if [[ "TESTRESULTS_API" -ne 0 ]]; then
    exit $TESTRESULTS_API
fi
