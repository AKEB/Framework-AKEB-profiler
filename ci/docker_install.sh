#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

export https_proxy=http://m100.cache.pvt:3128 
export http_proxy=http://m100.cache.pvt:3128

apt-get update -yqq
apt-get install git libfreetype6-dev libjpeg62-turbo-dev libmcrypt-dev libssl-dev libz-dev zlib1g-dev libsqlite3-dev \
                zip unzip libxml2-dev libcurl3-dev libedit-dev libpspell-dev libldap2-dev unixodbc-dev libpq-dev wget -yqq

ln -fs /usr/lib/x86_64-linux-gnu/libldap.so /usr/lib/

pear config-set http_proxy http://m100.cache.pvt:3128
# pear config-set https_proxy http://m100.cache.pvt:3128

pecl install xdebug && docker-php-ext-enable xdebug

{ \
    echo "xdebug.mode=coverage"; \
    echo "xdebug.start_with_request=yes"; \
    echo "xdebug.client_host=host.docker.internal"; \
    echo "xdebug.client_port=9000"; \
} > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \

echo "zend_extension=xdebug.so" >> /usr/local/etc/php/php.ini
