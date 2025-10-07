#!/bin/bash

# Script para iniciar infraestrutura em etapas

set -e

echo "========================================="
echo "Iniciando Infraestrutura em Etapas"
echo "========================================="
echo ""

cd "$(dirname "$0")/.."

# Etapa 1: Bancos de Dados
echo "🗄️  Etapa 1/5: Iniciando Bancos de Dados..."
docker compose up -d \
    auth-db \
    inventory-db \
    sales-db

echo "⏳ Aguardando bancos de dados ficarem prontos (30s)..."
sleep 30

# Etapa 2: Message Broker e Cache
echo ""
echo "📨 Etapa 2/5: Iniciando RabbitMQ e Redis..."
docker compose up -d rabbitmq redis

echo "⏳ Aguardando RabbitMQ e Redis (20s)..."
sleep 20

# Etapa 3: Microserviços
echo ""
echo "🚀 Etapa 3/5: Iniciando Microserviços..."
docker compose up -d auth-service inventory-service sales-service

echo "⏳ Aguardando microserviços ficarem prontos (20s)..."
sleep 20

# Etapa 4: Monitoramento
echo ""
echo "📊 Etapa 4/5: Iniciando Stack de Monitoramento..."
docker compose up -d \
    prometheus \
    grafana \
    alertmanager \
    node-exporter \
    cadvisor \
    redis-exporter

echo "⏳ Aguardando Prometheus/Grafana (15s)..."
sleep 15

# Etapa 5: Observability (ELK Stack)
echo ""
echo "🔍 Etapa 5/5: Iniciando Stack de Observability (ELK)..."
docker compose up -d elasticsearch

echo "⏳ Aguardando Elasticsearch (30s)..."
sleep 30

docker compose up -d logstash kibana jaeger

echo ""
echo "✅ Infraestrutura iniciada com sucesso!"
echo ""
echo "📊 Status dos serviços:"
docker compose ps
echo ""
echo "========================================="
echo "🌐 Serviços disponíveis:"
echo ""
echo "   Microserviços:"
echo "   - Auth:       http://localhost:8000"
echo "   - Inventory:  http://localhost:8001"
echo "   - Sales:      http://localhost:8002"
echo ""
echo "   RabbitMQ:"
echo "   - Management: http://localhost:15672"
echo "   - User: admin / Pass: admin123"
echo ""
echo "   Monitoring:"
echo "   - Grafana:    http://localhost:3000 (admin/admin123)"
echo "   - Prometheus: http://localhost:9090"
echo "   - Alertmgr:   http://localhost:9093"
echo ""
echo "   Observability:"
echo "   - Jaeger:     http://localhost:16686"
echo "   - Kibana:     http://localhost:5601"
echo "========================================="
echo ""

