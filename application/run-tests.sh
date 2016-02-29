#!/usr/bin/env bash
set -x
# Run database migrations
whenavail db 3306 100 /data/yii migrate --interactive=0
whenavail db 3306 100 /data/yii migrate --interactive=0 --migrationPath=console/migrations-test

# Install composer dev dependencies
cd /data
composer install --prefer-dist --no-interaction

# Install and enable xdebug for code coverage
apt-get install -y php5-xdebug git
php5enmod xdebug

# Run codeception tests

./vendor/bin/codecept run unit --coverage --coverage-xml
TESTRESULTS=$?

# Clone repo to get git parents
cd /tmp
rm -rf idp-pw-api/
git clone https://github.com/silinternational/idp-pw-api.git
cd idp-pw-api/
PARENTS=`git log --pretty=%P -n 1 ${CI_COMMIT_ID}`

# Push coverage data to scrutinizer
cd /data
curl -Lo ocular.phar https://scrutinizer-ci.com/ocular.phar
php ocular.phar code-coverage:upload --repository="g/silinternational/idp-pw-api" --revision="${CI_COMMIT_ID}" --parent="${PARENTS}" --format=php-clover tests/_output/coverage.xml

# If unit tests fail, make sure to exit with error status
if [[ "$TESTRESULTS" -ne 0 ]]; then
    exit $TESTRESULTS
fi