#!/bin/bash

# Script para iniciar infraestrutura em etapas

set -e

echo "========================================="
echo "Iniciando Infraestrutura em Etapas"
echo "========================================="
echo ""

cd "$(dirname "$0")/.."

# Etapa 1: Bancos de Dados
echo "🗄️  Etapa 1/4: Iniciando Bancos de Dados..."
docker compose up -d \
    gateway-db \
    auth-db \
    inventory-db \
    sales-db \
    logistics-db \
    financial-db

echo "⏳ Aguardando bancos de dados ficarem prontos (30s)..."
sleep 30

# Etapa 2: Message Broker e Cache
echo ""
echo "📨 Etapa 2/4: Iniciando RabbitMQ e Redis..."
docker compose up -d rabbitmq redis

echo "⏳ Aguardando RabbitMQ e Redis (20s)..."
sleep 20

# Etapa 3: API Gateway
echo ""
echo "🌐 Etapa 3/4: Iniciando Kong Gateway..."
docker compose up -d kong-migration
sleep 10
docker compose up -d api-gateway

echo "⏳ Aguardando Kong Gateway (15s)..."
sleep 15

# Etapa 4: Monitoramento
echo ""
echo "📊 Etapa 4/4: Iniciando Stack de Monitoramento..."
docker compose up -d \
    elasticsearch \
    prometheus \
    grafana \
    jaeger

echo "⏳ Aguardando Elasticsearch (30s)..."
sleep 30

docker compose up -d logstash kibana

echo ""
echo "✅ Infraestrutura iniciada com sucesso!"
echo ""
echo "📊 Status dos serviços:"
docker compose ps
echo ""
echo "========================================="
echo "🌐 Serviços disponíveis:"
echo ""
echo "   Kong Gateway:"
echo "   - Proxy:      http://localhost:8000"
echo "   - Admin:      http://localhost:8001"
echo ""
echo "   RabbitMQ:"
echo "   - Management: http://localhost:15672"
echo "   - User: admin / Pass: admin123"
echo ""
echo "   Grafana:"
echo "   - Dashboard:  http://localhost:3000"
echo "   - User: admin / Pass: admin"
echo ""
echo "   Prometheus:   http://localhost:9090"
echo "   Jaeger:       http://localhost:16686"
echo "   Kibana:       http://localhost:5601"
echo "========================================="
echo ""

