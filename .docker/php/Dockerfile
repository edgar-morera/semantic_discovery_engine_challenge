FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install \
        pdo_mysql \
        intl \
        opcache \
        mbstring \
        xml \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY .docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

WORKDIR /var/www/html

USER www-data
