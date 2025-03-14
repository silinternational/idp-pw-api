#!/usr/bin/env bash

# establish a signal handler to catch the SIGTERM from a 'docker stop'
# reference: https://medium.com/@gchudnov/trapping-signals-in-docker-containers-7a57fdda7d86
term_handler() {
  apache2ctl stop
  exit 143; # 128 + 15 -- SIGTERM
}
trap 'kill ${!}; term_handler' SIGTERM

echo "starting idp-pw-api version $GITHUB_REF_NAME"

# Run database migrations
/data/yii migrate --interactive=0

if [[ $APP_ENV == "dev" ]]; then
    export XDEBUG_CONFIG="remote_enable=1 remote_host="$REMOTE_DEBUG_IP
    apt-get update && apt-get install -y php-xdebug
fi

if [[ $PARAMETER_STORE_PATH ]]; then
  config-shim --path $PARAMETER_STORE_PATH apache2ctl -k start -D FOREGROUND
elif [[ $APP_ID ]]; then
  config-shim --app $APP_ID --config $CONFIG_ID --env $ENV_ID apache2ctl -k start -D FOREGROUND
else
  apache2ctl -k start -D FOREGROUND
fi

# endless loop with a wait is needed for the trap to work
while true
do
  tail -f /dev/null & wait ${!}
done
