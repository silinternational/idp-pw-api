#!/usr/bin/env bash
set -x

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

# Install and enable xdebug for code coverage
#apt-get install -y php5-xdebug git
#php5enmod xdebug

# Run codeception tests
runny ./vendor/bin/codecept run unit

##### Disabled reporting of code coverage on 3/30 because scrutinizer isnt working with it yet

#./vendor/bin/codecept run unit --coverage --coverage-xml
#TESTRESULTS=$?
#

## Clone repo to get git parents
#cd /tmp
#rm -rf idp-pw-api/
#git clone https://github.com/silinternational/idp-pw-api.git
#cd idp-pw-api/
#PARENTS=`git log --pretty=%P -n 1 ${CI_COMMIT_ID}`
#
## Push coverage data to scrutinizer
#cd /data
#curl -Lo ocular.phar https://scrutinizer-ci.com/ocular.phar
#php ocular.phar code-coverage:upload --repository="g/silinternational/idp-pw-api" --revision="${CI_COMMIT_ID}" --parent="${PARENTS}" --format=php-clover -n -vvv tests/_output/coverage.xml
#
## If unit tests fail, make sure to exit with error status
#if [[ "$TESTRESULTS" -ne 0 ]]; then
#    exit $TESTRESULTS
#fi
