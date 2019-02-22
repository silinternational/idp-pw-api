#!/usr/bin/env bash

# Install composer dev dependencies
cd /data
runny composer install --prefer-dist --no-interaction --optimize-autoloader

mkdir -p /data/runtime/mail

# Run database migrations
whenavail ${MYSQL_HOST} 3306 100 /data/yii migrate --interactive=0

# Install and enable xdebug for code coverage
apt-get install -y php-xdebug

# Run codeception tests
whenavail broker 80 100 echo "broker ready, running unit tests..."
runny ./vendor/bin/codecept run unit --coverage --coverage-xml
TESTRESULTS_UNIT=$?

# Run behat tests
if [ -z "$TEST_GOOGLE_PWSTORE_CONFIG_delegatedAdminEmail" ]; then
    echo -e "\e[31mGoogle passwordstore tests skipped\e[0m"
else
    runny ./vendor/bin/behat --config=tests/features/behat.yml --strict
    TESTRESULTS_BEHAT=$?
fi

## The ocular.php script sometimes works and sometimes hangs with no error message

# Clone repo to get git parents
#cd /tmp
#rm -rf idp-pw-api/
#git clone https://github.com/silinternational/idp-pw-api.git
#cd idp-pw-api/
#PARENTS=`git log --pretty=%P -n 1 ${CI_COMMIT_ID}`

# Push coverage data to scrutinizer
#cd /data
#curl -Lo ocular.phar https://scrutinizer-ci.com/ocular.phar
#php ocular.phar code-coverage:upload --repository="g/silinternational/idp-pw-api" --revision="${CI_COMMIT_ID}" --parent="${PARENTS}" --format=php-clover -n -vvv tests/_output/coverage.xml

# If unit tests fail, make sure to exit with error status
if [[ "$TESTRESULTS_UNIT" -ne 0 ]]; then
    exit $TESTRESULTS_UNIT
fi

if [[ "$TESTRESULTS_BEHAT" -ne 0 ]]; then
    exit $TESTRESULTS_BEHAT
fi
