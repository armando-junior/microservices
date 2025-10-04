#!/bin/bash

# Script para iniciar toda a infraestrutura

set -e

echo "========================================="
echo "Iniciando Infraestrutura - ERP Microservices"
echo "========================================="
echo ""

# Verificar se Docker está rodando
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker não está rodando. Por favor, inicie o Docker primeiro."
    exit 1
fi

echo "✅ Docker está rodando"
echo ""

# Verificar se docker compose existe
if ! docker compose version &> /dev/null; then
    echo "❌ docker compose não encontrado. Por favor, instale o Docker Compose."
    exit 1
fi

echo "✅ docker compose encontrado"
echo ""

# Criar diretórios necessários se não existirem
echo "📁 Criando diretórios necessários..."
mkdir -p infrastructure/rabbitmq
mkdir -p infrastructure/prometheus
mkdir -p infrastructure/grafana/provisioning/datasources
mkdir -p infrastructure/logstash
echo "✅ Diretórios criados"
echo ""

# Verificar se arquivo docker-compose.yml existe
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ docker-compose.yml não encontrado no diretório atual"
    exit 1
fi

# Baixar imagens
echo "📥 Baixando imagens Docker (pode levar alguns minutos)..."
docker compose pull

# Iniciar serviços de infraestrutura
echo ""
echo "🚀 Iniciando serviços de infraestrutura..."
docker compose up -d

# Aguardar serviços ficarem saudáveis
echo ""
echo "⏳ Aguardando serviços ficarem prontos..."
echo "   (isso pode levar 1-2 minutos)"
echo ""

sleep 10

# Verificar status dos serviços
echo "📊 Status dos serviços:"
docker compose ps

echo ""
echo "========================================="
echo "✅ Infraestrutura iniciada com sucesso!"
echo "========================================="
echo ""
echo "🌐 Serviços disponíveis:"
echo ""
echo "   API Gateway (Kong):"
echo "   - Proxy:      http://localhost:8000"
echo "   - Admin API:  http://localhost:8001"
echo "   - Admin GUI:  http://localhost:8002"
echo ""
echo "   RabbitMQ:"
echo "   - Management: http://localhost:15672"
echo "   - User: admin / Password: admin123"
echo ""
echo "   Grafana:"
echo "   - Dashboard:  http://localhost:3000"
echo "   - User: admin / Password: admin"
echo ""
echo "   Prometheus:"
echo "   - UI:         http://localhost:9090"
echo ""
echo "   Jaeger:"
echo "   - UI:         http://localhost:16686"
echo ""
echo "   Kibana:"
echo "   - UI:         http://localhost:5601"
echo ""
echo "   Elasticsearch:"
echo "   - API:        http://localhost:9200"
echo ""
echo "   Redis:"
echo "   - Port:       6379"
echo "   - Password:   redis123"
echo ""
echo "========================================="
echo ""
echo "📝 Próximos passos:"
echo "   1. Verificar logs: ./scripts/logs.sh"
echo "   2. Ver status: ./scripts/status.sh"
echo "   3. Parar tudo: ./scripts/stop.sh"
echo ""
echo "📚 Documentação completa em: ./docs/"
echo ""

