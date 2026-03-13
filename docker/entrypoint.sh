#!/bin/sh
set -eu

mkdir -p /var/www/html/data /var/www/html/uploads /var/www/html/.linuxcms-runtime
chown -R www-data:www-data /var/www/html/data /var/www/html/uploads /var/www/html/.linuxcms-runtime

exec "$@"
