# Auth Service - Dockerfile
FROM php:8.3-fpm-alpine

# Metadata
LABEL maintainer="auth-service"
LABEL description="Authentication Microservice with Laravel"

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    bash \
    supervisor \
    nginx

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    bcmath \
    pcntl

# Install Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/auth-service

# Copy composer files first for better caching
COPY composer.json composer.lock* ./

# Install PHP dependencies (if composer files exist)
RUN if [ -f composer.json ]; then composer install --no-scripts --no-autoloader; fi

# Copy application code
COPY . .

# Generate autoload files
RUN if [ -f composer.json ]; then composer dump-autoload --optimize; fi

# Set permissions
RUN chown -R www-data:www-data /var/www/auth-service \
    && chmod -R 755 /var/www/auth-service/storage \
    && chmod -R 755 /var/www/auth-service/bootstrap/cache

# Copy Nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy Supervisor configuration
COPY docker/supervisord.conf /etc/supervisord.conf

# Expose port
EXPOSE 8000

# Start Supervisor (manages PHP-FPM and Nginx)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

