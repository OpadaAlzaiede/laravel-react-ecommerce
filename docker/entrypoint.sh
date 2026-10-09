#!/bin/sh
set -e

# Render routes traffic to $PORT (default 10000).
PORT="${PORT:-10000}"
sed -i "s/Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Managed MySQL (e.g. Aiven) needs its CA certificate; pass the PEM contents as an env var.
if [ -n "$MYSQL_SSL_CA_PEM" ]; then
    printf '%s\n' "$MYSQL_SSL_CA_PEM" > /var/www/html/storage/mysql-ca.pem
    export MYSQL_ATTR_SSL_CA=/var/www/html/storage/mysql-ca.pem
fi

php artisan optimize
chown -R www-data:www-data storage bootstrap/cache

(php artisan demo:prepare && chown -R www-data:www-data storage) &

exec "$@"
