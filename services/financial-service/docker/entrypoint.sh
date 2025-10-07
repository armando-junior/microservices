#!/bin/bash
set -e

echo "🏦 Financial Service - Starting..."

# Aguardar o banco de dados estar pronto
echo "⏳ Waiting for database..."
until PGPASSWORD=$DB_PASSWORD psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c '\q' 2>/dev/null; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "✅ Database is ready!"

# Rodar migrations
echo "🗄️  Running migrations..."
php artisan migrate --force

# Limpar cache
echo "🧹 Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Otimizar para produção
echo "⚡ Optimizing..."
php artisan config:cache
php artisan route:cache

echo "🚀 Financial Service - Ready!"

# Executar o comando passado como argumento
exec "$@"
