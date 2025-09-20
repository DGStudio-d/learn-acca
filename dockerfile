# Multi-stage build for Laravel application
FROM php:8.2-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    icu-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    xml

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Frontend build stage
FROM node:20-alpine AS frontend

WORKDIR /var/www/html

# Copy package files
COPY package.json package-lock.json* ./

# Install npm dependencies
RUN npm ci --only=production

# Copy source files needed for build
COPY resources/ resources/
COPY public/ public/
COPY vite.config.js ./

# Build frontend assets
RUN npm run build

# Production stage
FROM base AS production

# Create application user
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

# Copy application code
COPY --chown=www:www . .

# Copy built frontend assets from frontend stage
COPY --from=frontend --chown=www:www /var/www/html/public/build ./public/build

# Copy vendor from base stage
COPY --from=base --chown=www:www /var/www/html/vendor ./vendor

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set proper permissions
RUN chown -R www:www /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Switch to non-root user
USER www

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Expose port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php artisan inspire || exit 1

# Start PHP-FPM
CMD ["php-fpm"]