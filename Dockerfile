FROM silintl/php7-apache:7.4.19
LABEL maintainer="Phillip Shipley <phillip_shipley@sil.org>"

RUN apt-get update -y && \
    apt-get install -y \
# Needed to install s3cmd
        python-pip \
# Needed to build php extensions
        libfreetype6-dev \
        libgmp-dev \
        libjpeg62-turbo-dev \
        libldap2-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libcurl4-openssl-dev \
# Clean up to reduce docker image size
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN curl https://raw.githubusercontent.com/silinternational/runny/0.2/runny -o /usr/local/bin/runny
RUN chmod a+x /usr/local/bin/runny

# Install and enable, see the README on the docker hub for the image
RUN pecl install memcache-4.0.5.2 && docker-php-ext-enable memcache
RUN docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ && \
    docker-php-ext-install gmp ldap zip

# Copy in vhost configuration
COPY dockerbuild/vhost.conf /etc/apache2/sites-enabled/

# Ensure the DocumentRoot folder exists
RUN mkdir -p /data

# Validate apache configuration
RUN ["apache2ctl", "configtest"]

# Copy in any additional PHP ini files
COPY dockerbuild/*.ini "$PHP_INI_DIR/conf.d/"

# get s3cmd and s3-expand
RUN pip install s3cmd
RUN curl https://raw.githubusercontent.com/silinternational/s3-expand/1.5/s3-expand -o /usr/local/bin/s3-expand
RUN chmod a+x /usr/local/bin/s3-expand

# Clean up all the build stuff we don't need
RUN apt purge -y dpkg-dev cpp-8 gcc-8 python2-dev python2.7-dev && \
    apt autoremove -y

WORKDIR /data

# Install/cleanup composer dependencies
COPY application/composer.json /data/
COPY application/composer.lock /data/
RUN composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader --no-progress

# It is expected that /data is = application/ in project folder
COPY application/ /data/

# Fix folder permissions
RUN chown -R www-data:www-data \
    console/runtime/ \
    frontend/runtime/ \
    frontend/web/assets/

# ErrorLog inside a VirtualHost block is ineffective for unknown reasons
RUN sed -i -E 's@ErrorLog .*@ErrorLog /proc/self/fd/2@i' /etc/apache2/apache2.conf

RUN touch /etc/default/locale

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/s3-expand"]

CMD ["/data/run.sh"]
