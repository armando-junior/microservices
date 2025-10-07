#!/bin/bash

# Script para fazer stress test e gerar MUITAS métricas

set -e

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "💥 STRESS TEST - Gerador Intensivo de Métricas"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

AUTH_URL="${AUTH_URL:-http://localhost:9001}"
INVENTORY_URL="${INVENTORY_URL:-http://localhost:9002}"
SALES_URL="${SALES_URL:-http://localhost:9003}"
DURATION="${1:-30}"

echo "⚠️  AVISO: Este script vai gerar MUITAS requisições!"
echo ""
echo "🎯 Configuração:"
echo "   Duração: $DURATION segundos"
echo "   Endpoints: Auth, Inventory, Sales"
echo ""
echo "⏳ Iniciando stress test em 3 segundos..."
sleep 3

start_time=$(date +%s)
request_count=0

while true; do
    current_time=$(date +%s)
    elapsed=$((current_time - start_time))
    
    if [ $elapsed -ge $DURATION ]; then
        break
    fi
    
    # Fazer várias requisições em paralelo
    curl -s http://localhost:9001/api/health > /dev/null &
    curl -s http://localhost:9001/metrics > /dev/null &
    curl -s http://localhost:9002/api/health > /dev/null &
    curl -s http://localhost:9002/metrics > /dev/null &
    curl -s http://localhost:9003/api/health > /dev/null &
    curl -s http://localhost:9003/metrics > /dev/null &
    
    ((request_count+=6))
    
    echo "⚡ $elapsed/$DURATION segundos | $request_count requisições enviadas"
    
    sleep 0.1
done

# Aguardar processos em background
wait

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ STRESS TEST CONCLUÍDO!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📊 Estatísticas:"
echo "   Duração total: $DURATION segundos"
echo "   Total de requisições: $request_count"
echo "   Requisições/segundo: ~$((request_count / DURATION))"
echo ""
echo "🔥 Suas métricas agora devem estar BEM visíveis no Grafana!"
echo ""

