FROM composer:2.0 as vendor

WORKDIR /var/www/html

COPY database/ database/
COPY composer.json composer.json
COPY composer.lock composer.lock

RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

COPY . .

FROM php:7.3-apache

LABEL maintainer="David Fichtenbaum"

WORKDIR /var/www/html

EXPOSE 80

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    g++ \
    make \
    git \
    unzip \
    zip \
    libmcrypt-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    libzip-dev \
    &&  rm -rf /var/lib/apt/lists/*


RUN docker-php-ext-configure bcmath --enable-bcmath \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql \
    && docker-php-ext-configure mbstring --enable-mbstring \
    && docker-php-ext-configure zip --with-libzip \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    pdo_mysql \
    zip \
    curl \
    json \
    fileinfo \
    tokenizer \
    xml \
    opcache \
    ctype \
    pcntl

COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor /var/www/html/vendor
COPY --chown=www-data:www-data . /var/www/html
RUN chown -R www-data:www-data /var/www/html/vendor

RUN php artisan key:generate
RUN php artisan config:cache
RUN php artisan route:cache





