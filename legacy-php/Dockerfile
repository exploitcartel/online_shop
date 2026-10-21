# VULNERABLE: PHP 5.4 is end-of-life (no security patches since September
# 2015) and is used here only to reproduce the historical
# session.upload_progress file-inclusion technique. It is isolated in its
# own container, separate from the main shop (which needs PHP 7+ syntax).
FROM php:5.4-fpm

COPY lab.ini /usr/local/etc/php/conf.d/lab.ini

WORKDIR /var/www/legacy
