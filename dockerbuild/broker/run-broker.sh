#!/usr/bin/env bash
set -x

# establish a signal handler to catch the SIGTERM from a 'docker stop'
# reference: https://medium.com/@gchudnov/trapping-signals-in-docker-containers-7a57fdda7d86
term_handler() {
  apache2ctl stop
  exit 143; # 128 + 15 -- SIGTERM
}
trap 'kill ${!}; term_handler' SIGTERM

# Try to install composer dev dependencies
cd /data
composer install --no-interaction --no-scripts

# If that failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Try to run database migrations
whenavail $MYSQL_HOST 3306 100 ./yii migrate --interactive=0

# If that failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Redo test migration to ensure it's fresh
./yii migrate/redo 1 --interactive=0

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

apache2ctl -k start -D FOREGROUND

# endless loop with a wait is needed for the trap to work
while true
do
  tail -f /dev/null & wait ${!}
done
