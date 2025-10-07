#!/bin/bash

# Script para gerar métricas nos microserviços

set -e

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 GERADOR DE MÉTRICAS - Microservices"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Configurações
AUTH_URL="${AUTH_URL:-http://localhost:9001}"
INVENTORY_URL="${INVENTORY_URL:-http://localhost:9002}"
SALES_URL="${SALES_URL:-http://localhost:9003}"
ITERATIONS="${1:-20}"

echo "🎯 Configuração:"
echo "   Auth Service:      $AUTH_URL"
echo "   Inventory Service: $INVENTORY_URL"
echo "   Sales Service:     $SALES_URL"
echo "   Iterações:         $ITERATIONS"
echo ""
echo "⏳ Gerando métricas (isso levará ~${ITERATIONS} segundos)..."
echo ""

# Contadores
total_requests=0
successful_requests=0
failed_requests=0

# Função para fazer requisições
make_request() {
    local url=$1
    local description=$2
    
    if curl -s -o /dev/null -w "%{http_code}" "$url" | grep -q "^[23]"; then
        echo "   ✅ $description"
        ((successful_requests++))
    else
        echo "   ❌ $description (failed)"
        ((failed_requests++))
    fi
    ((total_requests++))
}

# Loop de geração de métricas
for i in $(seq 1 $ITERATIONS); do
    echo "📊 Iteração $i/$ITERATIONS:"
    
    # Auth Service
    make_request "$AUTH_URL/api/health" "Auth Health Check"
    
    # Inventory Service
    make_request "$INVENTORY_URL/api/health" "Inventory Health Check"
    
    # Sales Service
    make_request "$SALES_URL/api/health" "Sales Health Check"
    
    # Endpoints de métricas
    make_request "$AUTH_URL/metrics" "Auth Metrics"
    make_request "$INVENTORY_URL/metrics" "Inventory Metrics"
    make_request "$SALES_URL/metrics" "Sales Metrics"
    
    echo ""
    sleep 1
done

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ MÉTRICAS GERADAS COM SUCESSO!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📊 Estatísticas:"
echo "   Total de requisições:  $total_requests"
echo "   Requisições bem-sucedidas: $successful_requests"
echo "   Requisições falhadas:  $failed_requests"
echo "   Taxa de sucesso:       $(( successful_requests * 100 / total_requests ))%"
echo ""
echo "🔍 Próximos passos:"
echo "   1. Acesse o Grafana: http://localhost:3000"
echo "   2. Abra o dashboard: Microservices Overview"
echo "   3. Aguarde ~10 segundos para o Prometheus coletar as métricas"
echo "   4. Pressione F5 no navegador ou aguarde o refresh automático"
echo ""
echo "💡 Dica: Configure o refresh automático do dashboard para 5s ou 10s"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

