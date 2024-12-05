#!/usr/bin/env bash

# Try to install composer dev dependencies
cd /data
composer install --no-interaction --no-scripts --no-progress

# Check the code against PSR-2.
vendor/bin/php-cs-fixer fix -v --dry-run --stop-on-violation --using-cache=no .

# If it didn't match PSR-2, then exit.
rc=$?;
if [[ $rc != 0 ]]; then
  echo ------------------------------------------------------------------------------
  echo Please run \"make psr2\" to format the code as PSR-2, then commit those changes.
  echo ------------------------------------------------------------------------------
  exit $rc;
fi

echo -------------------------------------------------
echo All PHP files appear to match PSR-2 requirements.
echo -------------------------------------------------
