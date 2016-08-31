FROM silintl/php-fpm7:latest

COPY application/composer.json /data/
COPY application/composer.lock /data/
COPY application/vendor /data/

RUN mkdir -p /data


# get s3-expand
RUN curl https://raw.githubusercontent.com/silinternational/s3-expand/1.5/s3-expand -o /usr/local/bin/s3-expand
RUN chmod a+x /usr/local/bin/s3-expand

WORKDIR /data

# Install/cleanup composer dependencies
COPY application/composer.json /data/
COPY application/composer.lock /data/
RUN composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader

# It is expected that /data is = application/ in project folder
COPY application/ /data/

# Fix folder permissions
RUN chown -R nobody:nobody \
    console/runtime/ \
    frontend/runtime/ \
    frontend/web/assets/

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/s3-expand"]
CMD ["/data/run.sh"]
