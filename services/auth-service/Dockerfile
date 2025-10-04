# ==============================================================================
# Auth Service - Production Dockerfile
# Multi-stage build for optimized image size
# ==============================================================================

# Stage 1: Composer Dependencies
FROM composer:2.7 AS composer-stage

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies (no dev)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

# Copy application code
COPY . .

# Generate optimized autoload files
RUN composer dump-autoload --optimize --classmap-authoritative

# ==============================================================================
# Stage 2: Production Image
FROM php:8.3-fpm-alpine

# Metadata
LABEL maintainer="auth-service" \
      description="Authentication Microservice with Laravel" \
      version="1.0.0"

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    bash \
    curl \
    && rm -rf /var/cache/apk/*

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    bcmath \
    pcntl

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 256M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "post_max_size = 50M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "upload_max_filesize = 50M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "max_execution_time = 300" >> "$PHP_INI_DIR/conf.d/custom.ini"

# Copy application from composer stage
COPY --from=composer-stage --chown=www-data:www-data /app /var/www

# Copy Docker configuration files
COPY --chown=www-data:www-data docker/nginx.conf /etc/nginx/nginx.conf
COPY --chown=www-data:www-data docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY --chown=www-data:www-data docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create required directories and set permissions
RUN mkdir -p \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs \
    /var/www/bootstrap/cache \
    /run/nginx \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8000/api/health || exit 1

# Expose port
EXPOSE 8000

# Start supervisor (runs as root, manages www-data processes)
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
