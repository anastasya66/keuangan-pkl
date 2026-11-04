# ============================================================
#  FinToko — Dockerfile
#  PHP 8.2 + Apache | Production-Ready for Docker Compose
# ============================================================
FROM php:8.2-apache

# Metadata labels
LABEL maintainer="FinToko Enterprise <dev@fintoko.id>"
LABEL description="FinToko – Sistem Informasi Keuangan & Inventaris"
LABEL version="1.0.0"

# -----------------------------------------------------------
# System dependencies + PHP extensions
# -----------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        zip \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# -----------------------------------------------------------
# Apache: enable mod_rewrite, set DocumentRoot to /public
# -----------------------------------------------------------
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf

# -----------------------------------------------------------
# PHP: OPcache tuning
# -----------------------------------------------------------
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.memory_consumption=192'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# -----------------------------------------------------------
# php.ini: security hardening (termasuk session hardening)
# -----------------------------------------------------------
RUN { \
    echo 'expose_php = Off'; \
    echo 'error_reporting = E_ALL'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /var/log/apache2/php_errors.log'; \
    echo 'upload_max_filesize = 5M'; \
    echo 'post_max_size = 6M'; \
    echo 'max_execution_time = 60'; \
    echo 'memory_limit = 256M'; \
    echo 'date.timezone = Asia/Jakarta'; \
    echo ''; \
    echo '; === Session Hardening (cookie_secure diatur dinamis di helpers.php) ==='; \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.cookie_samesite = Strict'; \
    echo 'session.use_strict_mode = 1'; \
    echo 'session.use_only_cookies = 1'; \
    echo 'session.use_trans_sid = 0'; \
    echo 'session.sid_length = 48'; \
    echo 'session.sid_bits_per_character = 6'; \
} > /usr/local/etc/php/conf.d/fintoko.ini

# -----------------------------------------------------------
# .htaccess: Blokir eksekusi PHP di folder uploads
# -----------------------------------------------------------
RUN mkdir -p /var/www/html/uploads/bukti && \
    echo 'php_flag engine off' > /var/www/html/uploads/.htaccess && \
    echo 'php_flag engine off' > /var/www/html/uploads/bukti/.htaccess

# -----------------------------------------------------------
# Copy source code
# -----------------------------------------------------------
WORKDIR /var/www/html
COPY . .

# Permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/public && \
    chmod -R 775 /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]
