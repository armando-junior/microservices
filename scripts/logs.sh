#!/bin/bash

# Script para visualizar logs dos serviços

set -e

echo "========================================="
echo "Logs - ERP Microservices"
echo "========================================="
echo ""

# Verificar se docker-compose.yml existe
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ docker-compose.yml não encontrado no diretório atual"
    exit 1
fi

# Se um serviço específico foi passado como parâmetro
if [ -n "$1" ]; then
    echo "📋 Mostrando logs de: $1"
    echo ""
    docker compose logs -f "$1"
else
    echo "📋 Mostrando logs de todos os serviços"
    echo ""
    echo "💡 Para ver logs de um serviço específico:"
    echo "   ./scripts/logs.sh <nome-do-servico>"
    echo ""
    echo "   Exemplos:"
    echo "   ./scripts/logs.sh rabbitmq"
    echo "   ./scripts/logs.sh api-gateway"
    echo "   ./scripts/logs.sh prometheus"
    echo ""
    echo "Pressione Ctrl+C para sair"
    echo ""
    sleep 3
    docker compose logs -f
fi

