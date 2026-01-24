
# 최신 PHP 8.4 FPM 이미지 사용함
FROM php:8.4-fpm

# 시스템 패키지 및 PHP 확장 도구 설치 (Xdebug 포함)
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip bcmath \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Node.js 및 npm 설치함 (2026년 기준 안정적인 22.x 버전 권장함)
RUN curl -sL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

# Composer 최신 버전 설치함
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
