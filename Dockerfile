FROM silintl/php-fpm7:latest

COPY application/composer.json /data/
COPY application/composer.lock /data/
COPY application/vendor /data/

# Install/cleanup composer dependencies
RUN composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader

# It is expected that /data is = application/ in project folder
COPY application/ /data/

# Fix folder permissions
RUN chown -R nobody:nobody \
    console/runtime/ \
    frontend/runtime/ \
    frontend/web/assets/

EXPOSE 9000
ENTRYPOINT ["s3-expand"]
CMD ["/data/run.sh"]
