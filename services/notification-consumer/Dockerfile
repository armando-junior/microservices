FROM php:8.2-cli-alpine

# Instalar dependências
RUN apk add --no-cache \
    supervisor \
    git \
    zip \
    unzip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar extensões PHP necessárias
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    && pecl install pcntl \
    && docker-php-ext-enable pcntl

# Criar diretórios
RUN mkdir -p /app /var/log

# Copiar arquivos
WORKDIR /app
COPY NotificationConsumer.php /app/
COPY supervisor.conf /etc/supervisor/conf.d/notification.conf

# Instalar dependências via Composer
RUN composer require php-amqplib/php-amqplib:^3.5

CMD ["supervisord", "-c", "/etc/supervisord.conf", "-n"]

