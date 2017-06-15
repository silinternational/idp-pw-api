#!/usr/bin/env bash

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
output=$(/data/yii migrate --interactive=0 2>&1)

# If they failed, exit.
rc=$?;
if [[ $rc != 0 ]]; then
    logger --priority user.err --stderr "Migrations failed with status ${rc} and output: ${output}"
    exit $rc;
fi

output=$(/data/yii migrate --interactive=0 --migrationPath=console/migrations-local 2>&1)
# If they failed, exit.
rc=$?;
if [[ $rc != 0 ]]; then
    logger --priority user.err "Migrations failed with status ${rc} and output: ${output}"
    exit $rc;
fi


# Dump env to a file
touch /etc/cron.d/idp
env | while read line ; do
   echo "$line" >> /etc/cron.d/idp
done

# Add env vars to idp-cron to make available to scripts
cat /etc/cron.d/idp-cron >> /etc/cron.d/idp

# Remove original cron file without env vars
rm -f /etc/cron.d/idp-cron

# Start cron daemon
cron -f
