# Image (fpm or apache)
# FROM php:8.3.12-fpm
FROM php:8.3.12-apache

# Annotations
LABEL org.opencontainers.image.authors="piriya_v@gpo.or.th"
LABEL org.opencontainers.image.source="https://github.com/p741633/kotchasan-borrow"
LABEL org.opencontainers.image.documentation ="https://www.kotchasan.com/knowledge/free_e_borrow_equipment_reservation_inventory_system.html"

# Create a www-data user with the same UID and GID as the host user
ARG WWW_DATA_UID=1000
ARG WWW_DATA_GID=1000
RUN usermod -u $WWW_DATA_UID www-data && groupmod -g $WWW_DATA_GID www-data

# Install library
# oniguruma --> libonig-dev (mbstring requirement)
# libzip-dev zip (zip requirement)
# libpng, libwebp, libxpm, libfreetype (gd requirement)
# libicu-dev (intl requirement)
RUN apt update && apt install -y \
  libonig-dev \
  libzip-dev \
  zip \
  libjpeg62-turbo-dev \
  libpng-dev \
  libwebp-dev \
  libxpm-dev \
  libfreetype6-dev \
  libicu-dev

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Install php extension : pdo_mysql mbstring
RUN docker-php-ext-install pdo pdo_mysql

# Install php extension : mysqli (if need)
# RUN docker-php-ext-install mysqli

# Install php extension : ext mbstring
RUN docker-php-ext-install mbstring

# Install php extension : gd
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Install php extension : zip
RUN docker-php-ext-install zip

# Install php extension : intl
RUN docker-php-ext-install intl

# Install php extension :opcache
RUN docker-php-ext-install opcache
