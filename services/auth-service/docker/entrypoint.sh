#!/bin/sh

set -e

echo "🚀 Starting Auth Service..."

# Aguardar alguns segundos para o banco ficar pronto
echo "⏳ Waiting 5 seconds for dependencies..."
sleep 5

# Limpar caches
echo "🧹 Clearing caches..."
rm -f bootstrap/cache/services.php bootstrap/cache/packages.php 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# Rodar migrations
echo "📊 Running migrations..."
php artisan migrate --force 2>/dev/null || echo "⚠️  Migrations skipped (may already be applied)"

# Otimizar autoloader (para produção)
if [ "$APP_ENV" = "production" ]; then
    echo "⚡ Optimizing for production..."
    composer dump-autoload --optimize --classmap-authoritative --no-dev 2>/dev/null || true
fi

echo "✅ Auth Service is ready!"

# Executar comando principal (supervisord)
exec "$@"
