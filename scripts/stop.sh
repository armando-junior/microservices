#!/bin/bash

# Script para parar toda a infraestrutura

set -e

echo "========================================="
echo "Parando Infraestrutura - ERP Microservices"
echo "========================================="
echo ""

# Verificar se docker-compose.yml existe
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ docker-compose.yml não encontrado no diretório atual"
    exit 1
fi

echo "🛑 Parando todos os containers..."
docker compose down

echo ""
echo "✅ Todos os containers foram parados!"
echo ""
echo "💡 Para remover também os volumes (dados), use:"
echo "   docker compose down -v"
echo ""

