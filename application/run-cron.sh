#!/usr/bin/env bash

if [[ "x" == "x$LOGENTRIES_KEY" ]]; then
    echo "Missing LOGENTRIES_KEY environment variable";
else
    # Set logentries key based on environment variable
    sed -i /etc/rsyslog.conf -e "s/LOGENTRIESKEY/${LOGENTRIES_KEY}/"
    # Start syslog
    rsyslogd
fi

# fix folder permissions
chown -R www-data:www-data \
  /data/console/runtime/ \
  /data/frontend/runtime/ \
  /data/frontend/web/assets/

# Run database migrations
/data/yii migrate --interactive=0
/data/yii migrate --interactive=0 --migrationPath=console/migrations-local

# make sure rsyslog can read logentries cert
chmod a+r /opt/ssl/logentries.all.crt

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
