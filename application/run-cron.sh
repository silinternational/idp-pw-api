#!/usr/bin/env bash

# establish a signal handler to catch the SIGTERM from a 'docker stop'
# reference: https://medium.com/@gchudnov/trapping-signals-in-docker-containers-7a57fdda7d86
term_handler() {
  killall cron
  exit 143; # 128 + 15 -- SIGTERM
}
trap 'kill ${!}; term_handler' SIGTERM

if [[ "x" == "x$LOGENTRIES_KEY" ]]; then
    echo "Missing LOGENTRIES_KEY environment variable";
else
    # Set logentries key based on environment variable
    sed -i /etc/rsyslog.conf -e "s/LOGENTRIESKEY/${LOGENTRIES_KEY}/"
    # Start syslog
    rsyslogd
    sleep 3
fi

# fix folder permissions
chown -R www-data:www-data \
  /data/console/runtime/ \
  /data/frontend/runtime/ \
  /data/frontend/web/assets/

# Run database migrations
runny /data/yii migrate --interactive=0
runny /data/yii migrate --interactive=0 --migrationPath=console/migrations-local

# Dump env to a file
env >> /etc/environment

# Start cron daemon
cron

# endless loop with a wait is needed for the trap to work
while true
do
  tail -f /dev/null & wait ${!}
done
