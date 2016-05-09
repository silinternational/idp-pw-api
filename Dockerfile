FROM silintl/php7:latest
MAINTAINER Phillip Shipley <phillip_shipley@sil.org>

ENV REFRESHED_AT 2016-04-22

COPY dockerbuild/vhost.conf /etc/apache2/sites-enabled/

RUN mkdir -p /data

# Copy in syslog config
RUN rm -f /etc/rsyslog.d/*
COPY dockerbuild/rsyslog.conf /etc/rsyslog.conf
RUN mkdir -p /opt/ssl
COPY dockerbuild/logentries.all.crt /opt/ssl/logentries.all.crt

# It is expected that /data is = application/ in project folder
COPY application/ /data/

WORKDIR /data

# Fix folder permissions
RUN chown -R www-data:www-data \
    console/runtime/ \
    frontend/runtime/ \
    frontend/web/assets/

# Install/cleanup composer dependencies
RUN composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader

EXPOSE 80
CMD ["/data/run.sh"]
