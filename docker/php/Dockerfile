FROM php:8.2.21-fpm

# # for xdebug use
#RUN pecl install xdebug-3.3.2 \
#    && docker-php-ext-enable xdebug

RUN apt-get update && apt-get install -y \
    cron \
    vim \
    libzip-dev \
    libpng-dev \
    libgmp-dev \
    libxml2-dev \
    freetds-dev \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    zip \
    unzip \
    libmagickwand-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql pdo_dblib bcmath zip intl soap gmp \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN ln -s /usr/lib/x86_64-linux-gnu/libsybdb.so /usr/lib/
