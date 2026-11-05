# =========================================================================
# Stage 1: Build Frontend Assets (Vite + React 19 + Tailwind CSS v4)
# =========================================================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copy dependency definitions
COPY package*.json ./

# Install npm dependencies
RUN npm install

# Copy application files needed for Vite compilation
COPY resources/ ./resources/
COPY vite.config.js ./
COPY public/ ./public/

# Compile production assets
RUN npm run build

# =========================================================================
# Stage 2: Production PHP-FPM Application Image
# =========================================================================
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Install required system packages & libraries for PHP extensions
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    icu-dev \
    mariadb-client \
    netcat-openbsd

# Configure & Install PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Copy Composer binary from official image
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy custom PHP configuration
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Copy application source code
COPY . /var/www/html

# Copy compiled frontend assets from frontend-builder stage
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

# Install production PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Setup entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Setup proper directory ownership and permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

