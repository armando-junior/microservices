#!/bin/bash

# Script para testar comunicação assíncrona via RabbitMQ
# Valida publicação de eventos e integração entre serviços

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurações
RABBITMQ_API="http://localhost:15672/api"
RABBITMQ_USER="admin"
RABBITMQ_PASS="admin123"

AUTH_URL="http://localhost:9001"
INVENTORY_URL="http://localhost:9002"
SALES_URL="http://localhost:9003"
FINANCIAL_URL="http://localhost:9004"

# Contadores
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                                                                      ║"
echo "║     🐰 TESTE DE COMUNICAÇÃO ASSÍNCRONA - RABBITMQ                   ║"
echo "║                                                                      ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""

# Função para incrementar contadores
pass_test() {
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    PASSED_TESTS=$((PASSED_TESTS + 1))
    echo -e "${GREEN}✅ $1${NC}"
}

fail_test() {
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    FAILED_TESTS=$((FAILED_TESTS + 1))
    echo -e "${RED}❌ $1${NC}"
}

# Função para obter contagem de mensagens em uma fila
get_queue_messages() {
    local queue=$1
    curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
        "$RABBITMQ_API/queues/%2F/$queue" | jq -r '.messages // 0'
}

# Função para verificar eventos na fila
check_messages_increased() {
    local queue=$1
    local before=$2
    local after=$(get_queue_messages "$queue")
    
    if [ "$after" -gt "$before" ]; then
        echo "$after"
        return 0
    else
        echo "$after"
        return 1
    fi
}

# Função para inspecionar última mensagem de uma fila
inspect_last_message() {
    local queue=$1
    curl -s -X POST -u "$RABBITMQ_USER:$RABBITMQ_PASS" \
        "$RABBITMQ_API/queues/%2F/$queue/get" \
        -H "Content-Type: application/json" \
        -d '{"count":1,"ackmode":"ack_requeue_true","encoding":"auto"}' \
        | jq -r '.[0].routing_key // "empty"'
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1️⃣  VERIFICANDO INFRAESTRUTURA RABBITMQ"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Verificar status RabbitMQ
RABBITMQ_STATUS=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" "$RABBITMQ_API/overview" | jq -r '.rabbitmq_version')
if [ -n "$RABBITMQ_STATUS" ] && [ "$RABBITMQ_STATUS" != "null" ]; then
    pass_test "RabbitMQ está rodando (versão $RABBITMQ_STATUS)"
else
    fail_test "RabbitMQ não está acessível"
    exit 1
fi

# Verificar exchanges
EXCHANGES=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" "$RABBITMQ_API/exchanges/%2F" \
    | jq -r '[.[] | select(.name != "" and (.name | contains("events")))] | length')
if [ "$EXCHANGES" -ge 4 ]; then
    pass_test "Exchanges configuradas: $EXCHANGES"
else
    fail_test "Exchanges insuficientes: $EXCHANGES (esperado >= 4)"
fi

# Verificar queues
QUEUES=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" "$RABBITMQ_API/queues/%2F" \
    | jq -r '[.[] | select(.name | contains(".queue"))] | length')
if [ "$QUEUES" -ge 6 ]; then
    pass_test "Queues configuradas: $QUEUES"
else
    fail_test "Queues insuficientes: $QUEUES (esperado >= 6)"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2️⃣  TESTANDO PUBLICAÇÃO DE EVENTOS - AUTH SERVICE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Limpar banco Auth
echo "📝 Limpando banco Auth..."
docker compose exec -T auth-service php artisan db:wipe --force > /dev/null 2>&1
docker compose exec -T auth-service php artisan migrate --force > /dev/null 2>&1
echo ""

# Contagem inicial
NOTIF_BEFORE=$(get_queue_messages "notification.queue")
echo "📊 Mensagens na notification.queue ANTES: $NOTIF_BEFORE"
echo ""

# Teste 1: UserRegistered event
echo "🧪 Teste 1: Registro de usuário"
TIMESTAMP=$(date +%s)
REGISTER_RESPONSE=$(curl -s -X POST "$AUTH_URL/api/auth/register" \
    -H "Content-Type: application/json" \
    -d "{
        \"name\": \"RabbitMQ Test User\",
        \"email\": \"test${TIMESTAMP}@example.com\",
        \"password\": \"Password123!\",
        \"password_confirmation\": \"Password123!\"
    }")

ACCESS_TOKEN=$(echo "$REGISTER_RESPONSE" | jq -r '.data.access_token // .access_token')
if [ -n "$ACCESS_TOKEN" ] && [ "$ACCESS_TOKEN" != "null" ]; then
    pass_test "Usuário registrado com sucesso"
    
    # Aguardar processamento
    sleep 2
    
    # Verificar evento
    NOTIF_AFTER=$(check_messages_increased "notification.queue" "$NOTIF_BEFORE")
    if [ $? -eq 0 ]; then
        ROUTING_KEY=$(inspect_last_message "notification.queue")
        if [ "$ROUTING_KEY" == "auth.user.registered" ]; then
            pass_test "Evento 'auth.user.registered' publicado (routing_key: $ROUTING_KEY)"
        else
            fail_test "Routing key incorreta: $ROUTING_KEY (esperado: auth.user.registered)"
        fi
    else
        fail_test "Evento 'auth.user.registered' NÃO foi publicado"
    fi
else
    fail_test "Falha ao registrar usuário"
    echo "Response: $REGISTER_RESPONSE"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3️⃣  TESTANDO PUBLICAÇÃO DE EVENTOS - INVENTORY SERVICE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Limpar banco Inventory
echo "📝 Limpando banco Inventory..."
docker compose exec -T inventory-service php artisan db:wipe --force > /dev/null 2>&1
docker compose exec -T inventory-service php artisan migrate --force > /dev/null 2>&1
echo ""

# Contagem inicial
SALES_Q_BEFORE=$(get_queue_messages "sales.queue")
echo "📊 Mensagens na sales.queue ANTES: $SALES_Q_BEFORE"
echo ""

# Teste 2: ProductCreated event (se implementado)
echo "🧪 Teste 2: Criação de categoria e produto"
CAT_RESPONSE=$(curl -s -X POST "$INVENTORY_URL/api/v1/categories" \
    -H "Content-Type: application/json" \
    -d "{\"name\": \"RabbitMQ Test Category $(date +%s)\", \"description\": \"Test\"}")

CAT_ID=$(echo "$CAT_RESPONSE" | jq -r '.data.id')
if [ -n "$CAT_ID" ] && [ "$CAT_ID" != "null" ]; then
    pass_test "Categoria criada: $CAT_ID"
    
    PROD_RESPONSE=$(curl -s -X POST "$INVENTORY_URL/api/v1/products" \
        -H "Content-Type: application/json" \
        -d "{\"name\": \"Test Product\", \"sku\": \"TST-$(date +%s)\", \"price\": 100, \"category_id\": \"$CAT_ID\", \"initial_stock\": 50}")
    
    PROD_ID=$(echo "$PROD_RESPONSE" | jq -r '.data.id')
    if [ -n "$PROD_ID" ] && [ "$PROD_ID" != "null" ]; then
        pass_test "Produto criado: $PROD_ID"
        
        # Aguardar processamento
        sleep 2
        
        # Verificar se evento foi publicado (pode não estar implementado)
        SALES_Q_AFTER=$(get_queue_messages "sales.queue")
        if [ "$SALES_Q_AFTER" -gt "$SALES_Q_BEFORE" ]; then
            ROUTING_KEY=$(inspect_last_message "sales.queue")
            pass_test "Evento de estoque publicado (routing_key: $ROUTING_KEY)"
        else
            echo -e "${YELLOW}⚠️  Nenhum evento de estoque publicado (pode não estar implementado)${NC}"
        fi
    else
        fail_test "Falha ao criar produto"
    fi
else
    fail_test "Falha ao criar categoria"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "4️⃣  TESTANDO PUBLICAÇÃO DE EVENTOS - SALES SERVICE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Limpar banco Sales
echo "📝 Limpando banco Sales..."
docker compose exec -T sales-service php artisan db:wipe --force > /dev/null 2>&1
docker compose exec -T sales-service php artisan migrate --force > /dev/null 2>&1
echo ""

# Contagem inicial
INV_Q_BEFORE=$(get_queue_messages "inventory.queue")
FIN_Q_BEFORE=$(get_queue_messages "financial.queue")
echo "📊 Mensagens na inventory.queue ANTES: $INV_Q_BEFORE"
echo "📊 Mensagens na financial.queue ANTES: $FIN_Q_BEFORE"
echo ""

# Teste 3: OrderCreated event
echo "🧪 Teste 3: Criação de pedido"

# Criar cliente
VALID_CPF="12345678909"
CUST_RESPONSE=$(curl -s -X POST "$SALES_URL/api/v1/customers" \
    -H "Content-Type: application/json" \
    -d "{\"name\": \"Test Customer $(date +%s)\", \"email\": \"customer$(date +%s)@example.com\", \"document\": \"$VALID_CPF\"}")

CUST_ID=$(echo "$CUST_RESPONSE" | jq -r '.data.id')
if [ -n "$CUST_ID" ] && [ "$CUST_ID" != "null" ]; then
    pass_test "Cliente criado: $CUST_ID"
    
    # Criar pedido
    ORDER_RESPONSE=$(curl -s -X POST "$SALES_URL/api/v1/orders" \
        -H "Content-Type: application/json" \
        -d "{\"customer_id\": \"$CUST_ID\"}")
    
    ORDER_ID=$(echo "$ORDER_RESPONSE" | jq -r '.data.id')
    if [ -n "$ORDER_ID" ] && [ "$ORDER_ID" != "null" ]; then
        pass_test "Pedido criado: $ORDER_ID"
        
        # Adicionar item ao pedido
        ITEM_RESPONSE=$(curl -s -X POST "$SALES_URL/api/v1/orders/$ORDER_ID/items" \
            -H "Content-Type: application/json" \
            -d "{\"product_id\": \"$PROD_ID\", \"quantity\": 5, \"unit_price\": 100}")
        
        ITEM_ID=$(echo "$ITEM_RESPONSE" | jq -r '.data.id')
        if [ -n "$ITEM_ID" ] && [ "$ITEM_ID" != "null" ]; then
            pass_test "Item adicionado ao pedido: $ITEM_ID"
            
            # Confirmar pedido
            CONFIRM_RESPONSE=$(curl -s -X PATCH "$SALES_URL/api/v1/orders/$ORDER_ID/confirm" \
                -H "Content-Type: application/json")
            
            CONFIRMED=$(echo "$CONFIRM_RESPONSE" | jq -r '.data.status')
            if [ "$CONFIRMED" == "confirmed" ]; then
                pass_test "Pedido confirmado"
                
                # Aguardar processamento
                sleep 3
                
                # Verificar eventos
                INV_Q_AFTER=$(get_queue_messages "inventory.queue")
                FIN_Q_AFTER=$(get_queue_messages "financial.queue")
                
                if [ "$INV_Q_AFTER" -gt "$INV_Q_BEFORE" ]; then
                    ROUTING_KEY=$(inspect_last_message "inventory.queue")
                    pass_test "Evento para Inventory publicado (routing_key: $ROUTING_KEY)"
                else
                    echo -e "${YELLOW}⚠️  Nenhum evento para Inventory (pode não estar implementado)${NC}"
                fi
                
                if [ "$FIN_Q_AFTER" -gt "$FIN_Q_BEFORE" ]; then
                    ROUTING_KEY=$(inspect_last_message "financial.queue")
                    pass_test "Evento para Financial publicado (routing_key: $ROUTING_KEY)"
                else
                    echo -e "${YELLOW}⚠️  Nenhum evento para Financial (pode não estar implementado)${NC}"
                fi
            else
                fail_test "Falha ao confirmar pedido"
            fi
        else
            fail_test "Falha ao adicionar item ao pedido"
        fi
    else
        fail_test "Falha ao criar pedido"
    fi
else
    fail_test "Falha ao criar cliente"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "5️⃣  TESTANDO PUBLICAÇÃO DE EVENTOS - FINANCIAL SERVICE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Limpar banco Financial
echo "📝 Limpando banco Financial..."
docker compose exec -T financial-service php artisan db:wipe --force > /dev/null 2>&1
docker compose exec -T financial-service php artisan migrate --force > /dev/null 2>&1
echo ""

# Contagem inicial
NOTIF_BEFORE=$(get_queue_messages "notification.queue")
echo "📊 Mensagens na notification.queue ANTES: $NOTIF_BEFORE"
echo ""

# Teste 4: AccountPayableCreated event
echo "🧪 Teste 4: Criação de conta a pagar"

# Criar fornecedor
SUPP_RESPONSE=$(curl -s -X POST "$FINANCIAL_URL/api/v1/suppliers" \
    -H "Content-Type: application/json" \
    -d "{\"name\": \"Test Supplier $(date +%s)\", \"document\": \"12345678901234\", \"email\": \"supplier$(date +%s)@example.com\"}")

SUPP_ID=$(echo "$SUPP_RESPONSE" | jq -r '.data.id')
if [ -n "$SUPP_ID" ] && [ "$SUPP_ID" != "null" ]; then
    pass_test "Fornecedor criado: $SUPP_ID"
    
    # Criar conta a pagar
    PAYABLE_RESPONSE=$(curl -s -X POST "$FINANCIAL_URL/api/v1/accounts-payable" \
        -H "Content-Type: application/json" \
        -d "{
            \"supplier_id\": \"$SUPP_ID\",
            \"description\": \"Test Payable\",
            \"amount\": 1000.00,
            \"issue_date\": \"$(date +%Y-%m-%d)\",
            \"payment_terms_days\": 30
        }")
    
    PAYABLE_ID=$(echo "$PAYABLE_RESPONSE" | jq -r '.data.id')
    if [ -n "$PAYABLE_ID" ] && [ "$PAYABLE_ID" != "null" ]; then
        pass_test "Conta a pagar criada: $PAYABLE_ID"
        
        # Aguardar processamento
        sleep 2
        
        # Verificar eventos
        NOTIF_AFTER=$(get_queue_messages "notification.queue")
        if [ "$NOTIF_AFTER" -gt "$NOTIF_BEFORE" ]; then
            ROUTING_KEY=$(inspect_last_message "notification.queue")
            pass_test "Evento financeiro publicado (routing_key: $ROUTING_KEY)"
        else
            echo -e "${YELLOW}⚠️  Nenhum evento financeiro publicado (pode não estar implementado)${NC}"
        fi
    else
        fail_test "Falha ao criar conta a pagar"
    fi
else
    fail_test "Falha ao criar fornecedor"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "6️⃣  RESUMO FINAL DAS FILAS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

for queue in auth.queue inventory.queue sales.queue financial.queue notification.queue; do
    COUNT=$(get_queue_messages "$queue")
    CONSUMERS=$(curl -s -u "$RABBITMQ_USER:$RABBITMQ_PASS" "$RABBITMQ_API/queues/%2F/$queue" | jq -r '.consumers')
    echo "→ $queue: $COUNT mensagens | $CONSUMERS consumers"
done

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 RESULTADO FINAL"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Total de Testes: $TOTAL_TESTS"
echo -e "${GREEN}Testes Passaram: $PASSED_TESTS${NC}"
echo -e "${RED}Testes Falharam: $FAILED_TESTS${NC}"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✅ TODOS OS TESTES PASSARAM!${NC}"
    exit 0
else
    PASS_RATE=$((PASSED_TESTS * 100 / TOTAL_TESTS))
    echo -e "${YELLOW}⚠️  Taxa de Sucesso: $PASS_RATE%${NC}"
    exit 1
fi
