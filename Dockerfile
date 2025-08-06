FROM php:8.1-cli

RUN apt-get update && apt-get install -y \
    libssl-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libzip-dev \
    unzip \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    autoconf \
    g++ \
    libxml2-dev

RUN docker-php-ext-install pdo sockets mbstring xml

RUN pecl install swoole \
    && docker-php-ext-enable swoole

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY . .

RUN chmod -R 775 /var/www/html && \
    chown -R www-data:www-data /var/www/html

CMD ["php", "-a"]