FROM php:8.3-fpm

ARG UID=1000
ARG GID=1000

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    ca-certificates \
    gnupg \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------
# Node.js 22 + npm
# ---------------------------------------------------------

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && node --version \
    && npm --version \
    && rm -rf /var/lib/apt/lists/*


# ---------------------------------------------------------
# PHP extensions
# ---------------------------------------------------------

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache


# ---------------------------------------------------------
# Redis
# ---------------------------------------------------------

RUN pecl install redis \
    && docker-php-ext-enable redis


# ---------------------------------------------------------
# Xdebug
# ---------------------------------------------------------

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug


# ---------------------------------------------------------
# Composer
# ---------------------------------------------------------

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# ---------------------------------------------------------
# User
# ---------------------------------------------------------

RUN groupadd -g ${GID} laravel \
    && useradd -u ${UID} -g laravel -m laravel


# ---------------------------------------------------------
# Working directory
# ---------------------------------------------------------

WORKDIR /var/www/html


# ---------------------------------------------------------
# PHP configuration
# ---------------------------------------------------------

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-laravel.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini


# ---------------------------------------------------------
# Laravel permissions
# ---------------------------------------------------------

RUN mkdir -p \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
    && chown -R laravel:laravel /var/www/html


USER laravel


# ---------------------------------------------------------
# PHP-FPM
# ---------------------------------------------------------

CMD ["/usr/local/sbin/php-fpm", "-F"]
