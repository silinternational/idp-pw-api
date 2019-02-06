#!/usr/bin/env bash

if [[ "x" == "x$LOGENTRIES_KEY" ]]; then
    echo "Missing LOGENTRIES_KEY environment variable";
else
    # Set logentries key based on environment variable
    sed -i /etc/rsyslog.conf -e "s/LOGENTRIESKEY/${LOGENTRIES_KEY}/"
    # Start syslog
    rsyslogd
    sleep 10
fi

# Run database migrations
runny /data/yii migrate --interactive=0

if [[ $APP_ENV == "dev" ]]; then
    export XDEBUG_CONFIG="remote_enable=1 remote_host="$REMOTE_DEBUG_IP
    apt-get install php-xdebug
fi

# Run apache in foreground
apache2ctl -D FOREGROUND
