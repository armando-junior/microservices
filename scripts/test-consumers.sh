#!/bin/bash

# Script para testar consumers RabbitMQ
# Monitora o processamento de mensagens em tempo real

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configurações
RABBITMQ_API="http://localhost:15672/api"
RABBITMQ_USER="admin"
RABBITMQ_PASS="admin123"

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                                                                      ║"
echo "║     🧪 TESTE DE CONSUMERS - RABBITMQ                                ║"
echo "║                                                                      ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""

# Função para obter contagem de mensagens
get_queue_count() {
    local queue=$1
    curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
        "$RABBITMQ_API/queues/%2F/$queue" | jq -r '.messages // 0'
}

# Função para obter contagem de consumers
get_consumers_count() {
    local queue=$1
    curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
        "$RABBITMQ_API/queues/%2F/$queue" | jq -r '.consumers // 0'
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 ESTADO INICIAL DAS FILAS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

QUEUES=("inventory.queue" "sales.queue" "financial.queue" "notification.queue")

echo -e "${CYAN}Fila                 Mensagens    Consumers${NC}"
echo "────────────────────────────────────────────────"

for queue in "${QUEUES[@]}"; do
    messages=$(get_queue_count "$queue")
    consumers=$(get_consumers_count "$queue")
    printf "%-20s %-12s %-10s\n" "$queue" "$messages" "$consumers"
done

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 INICIANDO CONSUMERS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo -e "${YELLOW}Consumers serão iniciados em background...${NC}"
echo ""

# Salva contagens iniciais
INVENTORY_BEFORE=$(get_queue_count "inventory.queue")
SALES_BEFORE=$(get_queue_count "sales.queue")

echo "1. Iniciando Inventory Consumer..."
docker compose exec -d inventory-service php artisan rabbitmq:consume-inventory --prefetch=1 > /dev/null 2>&1
echo -e "${GREEN}✅ Inventory Consumer iniciado${NC}"
sleep 2

echo "2. Iniciando Sales Consumer..."
docker compose exec -d sales-service php artisan rabbitmq:consume-sales --prefetch=1 > /dev/null 2>&1
echo -e "${GREEN}✅ Sales Consumer iniciado${NC}"
sleep 2

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "⏳ MONITORANDO PROCESSAMENTO (aguarde 10 segundos)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Monitora por 10 segundos
for i in {1..10}; do
    echo -ne "\r⏱️  Segundo $i/10 - Processando..."
    sleep 1
done

echo -e "\n"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 ESTADO APÓS PROCESSAMENTO"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo -e "${CYAN}Fila                 Antes    Depois   Processadas   Consumers${NC}"
echo "─────────────────────────────────────────────────────────────────"

INVENTORY_AFTER=$(get_queue_count "inventory.queue")
SALES_AFTER=$(get_queue_count "sales.queue")
INVENTORY_CONSUMERS=$(get_consumers_count "inventory.queue")
SALES_CONSUMERS=$(get_consumers_count "sales.queue")

INVENTORY_PROCESSED=$((INVENTORY_BEFORE - INVENTORY_AFTER))
SALES_PROCESSED=$((SALES_BEFORE - SALES_AFTER))

printf "%-20s %-8s %-8s %-13s %-10s\n" \
    "inventory.queue" "$INVENTORY_BEFORE" "$INVENTORY_AFTER" "$INVENTORY_PROCESSED" "$INVENTORY_CONSUMERS"

printf "%-20s %-8s %-8s %-13s %-10s\n" \
    "sales.queue" "$SALES_BEFORE" "$SALES_AFTER" "$SALES_PROCESSED" "$SALES_CONSUMERS"

echo ""

# Verificar sucesso
TOTAL_PROCESSED=$((INVENTORY_PROCESSED + SALES_PROCESSED))

if [ $TOTAL_PROCESSED -gt 0 ]; then
    echo -e "${GREEN}✅ SUCESSO! $TOTAL_PROCESSED mensagens processadas${NC}"
else
    echo -e "${RED}⚠️  Nenhuma mensagem processada. Verificar logs...${NC}"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📝 LOGS DOS CONSUMERS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo -e "${CYAN}Inventory Service (últimas 20 linhas):${NC}"
docker compose logs --tail=20 inventory-service 2>&1 | grep -i "consumer\|rabbitmq\|processing\|reserved\|released" || echo "Sem logs relevantes"

echo ""
echo -e "${CYAN}Sales Service (últimas 20 linhas):${NC}"
docker compose logs --tail=20 sales-service 2>&1 | grep -i "consumer\|rabbitmq\|processing\|order" || echo "Sem logs relevantes"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🗄️  VERIFICANDO BANCO DE DADOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "Reservas de estoque criadas:"
docker compose exec -T inventory-service php artisan tinker --execute="echo DB::table('stock_reservations')->count() . ' reservas\n';" 2>/dev/null || echo "Erro ao consultar"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 PRÓXIMOS PASSOS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "1. Verificar RabbitMQ Management:"
echo "   URL: http://localhost:15672"
echo "   User: admin / Pass: admin123"
echo ""

echo "2. Ver logs em tempo real:"
echo "   docker compose logs -f inventory-service"
echo "   docker compose logs -f sales-service"
echo ""

echo "3. Verificar reservas no banco:"
echo "   docker compose exec inventory-service php artisan tinker"
echo "   >>> DB::table('stock_reservations')->get();"
echo ""

echo "4. Parar consumers (se necessário):"
echo "   docker compose exec inventory-service pkill -f 'artisan rabbitmq:consume'"
echo "   docker compose exec sales-service pkill -f 'artisan rabbitmq:consume'"
echo ""

echo "✨ Teste completo!"

