#!/bin/bash

# Script para verificar status de todos os serviços

set -e

echo "========================================="
echo "Status da Infraestrutura - ERP Microservices"
echo "========================================="
echo ""

# Verificar se docker-compose.yml existe
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ docker-compose.yml não encontrado no diretório atual"
    exit 1
fi

echo "📊 Status dos containers:"
echo ""
docker compose ps

echo ""
echo "========================================="
echo "🏥 Health Checks:"
echo "========================================="
echo ""

# Função para verificar health de um serviço
check_health() {
    local name=$1
    local url=$2
    
    if curl -f -s -o /dev/null "$url"; then
        echo "✅ $name: Healthy"
    else
        echo "❌ $name: Unhealthy"
    fi
}

# Verificar serviços
check_health "Kong API Gateway" "http://localhost:8001/status"
check_health "RabbitMQ" "http://localhost:15672"
check_health "Prometheus" "http://localhost:9090/-/healthy"
check_health "Grafana" "http://localhost:3000/api/health"
check_health "Jaeger" "http://localhost:16686"
check_health "Elasticsearch" "http://localhost:9200/_cluster/health"
check_health "Kibana" "http://localhost:5601/api/status"

echo ""
echo "========================================="
echo "💾 Volumes:"
echo "========================================="
echo ""
docker volume ls | grep microservices || echo "Nenhum volume encontrado"

echo ""
echo "========================================="
echo "🌐 Networks:"
echo "========================================="
echo ""
docker network ls | grep microservices || echo "Nenhuma network encontrada"

echo ""

