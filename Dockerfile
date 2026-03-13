FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install pdo_sqlite zip \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/linuxcms-entrypoint
RUN chmod +x /usr/local/bin/linuxcms-entrypoint

COPY . /var/www/html

RUN mkdir -p /var/www/html/data /var/www/html/uploads /var/www/html/.linuxcms-runtime \
    && chown -R www-data:www-data /var/www/html/data /var/www/html/uploads /var/www/html/.linuxcms-runtime

EXPOSE 80

ENTRYPOINT ["linuxcms-entrypoint"]
CMD ["apache2-foreground"]
