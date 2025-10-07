#!/bin/bash

#############################################################
# Financial Service - Metrics Generator
# Gera métricas para visualização no Grafana
#############################################################

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

API_URL="http://localhost:9004/api/v1"
ITERATIONS=${1:-50}
DELAY=${2:-0.5}

echo -e "${BLUE}"
cat << "EOF"
╔══════════════════════════════════════════════════════════════════════╗
║                                                                      ║
║           🏦 FINANCIAL SERVICE - METRICS GENERATOR                  ║
║                                                                      ║
╚══════════════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

echo -e "${YELLOW}Gerando métricas para o Financial Service...${NC}"
echo -e "${YELLOW}Iterações: $ITERATIONS | Delay: ${DELAY}s${NC}\n"

# Arrays de dados de teste
SUPPLIER_NAMES=("Fornecedor Alpha" "Fornecedor Beta" "Fornecedor Gamma" "Fornecedor Delta")
CATEGORY_NAMES=("Fornecedores" "Salários" "Aluguel" "Energia" "Marketing")
CATEGORY_TYPES=("expense" "expense" "expense" "expense" "income")

SUPPLIER_IDS=()
CATEGORY_IDS=()

# Criar alguns suppliers
echo -e "${GREEN}Criando Suppliers...${NC}"
for i in {1..3}; do
    TIMESTAMP=$(date +%s%N)
    SUPPLIER_NAME="${SUPPLIER_NAMES[$((i % 4))]} $TIMESTAMP"
    
    RESPONSE=$(curl -s -X POST "$API_URL/suppliers" \
        -H "Content-Type: application/json" \
        -d "{
            \"name\": \"$SUPPLIER_NAME\",
            \"document\": \"$(printf '%014d' $((10000000000000 + RANDOM % 90000000000000)))\",
            \"email\": \"supplier$TIMESTAMP@test.com\",
            \"phone\": \"+55 11 9$(printf '%04d' $RANDOM)-$(printf '%04d' $RANDOM)\",
            \"address\": \"Rua Teste, $i - São Paulo, SP\"
        }")
    
    SUPPLIER_ID=$(echo "$RESPONSE" | jq -r '.data.id // empty')
    if [ -n "$SUPPLIER_ID" ]; then
        SUPPLIER_IDS+=("$SUPPLIER_ID")
        echo -e "  ${GREEN}✓${NC} Supplier criado: $SUPPLIER_ID"
    fi
    
    sleep $DELAY
done

# Criar algumas categories
echo -e "\n${GREEN}Criando Categories...${NC}"
for i in {0..4}; do
    TIMESTAMP=$(date +%s%N)
    CATEGORY_NAME="${CATEGORY_NAMES[$i]} $TIMESTAMP"
    
    RESPONSE=$(curl -s -X POST "$API_URL/categories" \
        -H "Content-Type: application/json" \
        -d "{
            \"name\": \"$CATEGORY_NAME\",
            \"type\": \"${CATEGORY_TYPES[$i]}\",
            \"description\": \"Categoria de teste\"
        }")
    
    CATEGORY_ID=$(echo "$RESPONSE" | jq -r '.data.id // empty')
    if [ -n "$CATEGORY_ID" ]; then
        CATEGORY_IDS+=("$CATEGORY_ID")
        echo -e "  ${GREEN}✓${NC} Category criada: $CATEGORY_ID"
    fi
    
    sleep $DELAY
done

# Loop principal gerando métricas
echo -e "\n${GREEN}Gerando métricas de operação...${NC}"

for i in $(seq 1 $ITERATIONS); do
    OPERATION=$((RANDOM % 10))
    
    case $OPERATION in
        0|1|2)  # 30% - List Suppliers
            curl -s "$API_URL/suppliers" > /dev/null
            echo -ne "  ${BLUE}[$i/$ITERATIONS]${NC} List Suppliers\r"
            ;;
        3|4)    # 20% - List Categories
            curl -s "$API_URL/categories" > /dev/null
            echo -ne "  ${BLUE}[$i/$ITERATIONS]${NC} List Categories\r"
            ;;
        5|6)    # 20% - Create Account Payable
            if [ ${#SUPPLIER_IDS[@]} -gt 0 ] && [ ${#CATEGORY_IDS[@]} -gt 0 ]; then
                SUPPLIER_ID=${SUPPLIER_IDS[$((RANDOM % ${#SUPPLIER_IDS[@]}))]}
                CATEGORY_ID=${CATEGORY_IDS[$((RANDOM % ${#CATEGORY_IDS[@]}))]}
                AMOUNT=$((RANDOM % 50000 + 1000))
                
                curl -s -X POST "$API_URL/accounts-payable" \
                    -H "Content-Type: application/json" \
                    -d "{
                        \"supplier_id\": \"$SUPPLIER_ID\",
                        \"category_id\": \"$CATEGORY_ID\",
                        \"description\": \"Pagamento teste $i\",
                        \"amount\": $AMOUNT,
                        \"issue_date\": \"$(date +%Y-%m-%d)\",
                        \"payment_terms_days\": $((RANDOM % 60 + 15))
                    }" > /dev/null
                echo -ne "  ${BLUE}[$i/$ITERATIONS]${NC} Create Account Payable ($AMOUNT)\r"
            fi
            ;;
        7)      # 10% - Create Account Receivable
            if [ ${#CATEGORY_IDS[@]} -gt 0 ]; then
                CATEGORY_ID=${CATEGORY_IDS[$((RANDOM % ${#CATEGORY_IDS[@]}))]}
                AMOUNT=$((RANDOM % 80000 + 2000))
                
                curl -s -X POST "$API_URL/accounts-receivable" \
                    -H "Content-Type: application/json" \
                    -d "{
                        \"customer_id\": \"$(uuidgen)\",
                        \"category_id\": \"$CATEGORY_ID\",
                        \"description\": \"Recebimento teste $i\",
                        \"amount\": $AMOUNT,
                        \"issue_date\": \"$(date +%Y-%m-%d)\",
                        \"payment_terms_days\": $((RANDOM % 45 + 15))
                    }" > /dev/null
                echo -ne "  ${BLUE}[$i/$ITERATIONS]${NC} Create Account Receivable ($AMOUNT)\r"
            fi
            ;;
        8)      # 10% - Get Supplier
            if [ ${#SUPPLIER_IDS[@]} -gt 0 ]; then
                SUPPLIER_ID=${SUPPLIER_IDS[$((RANDOM % ${#SUPPLIER_IDS[@]}))]}
                curl -s "$API_URL/suppliers/$SUPPLIER_ID" > /dev/null
                echo -ne "  ${BLUE}[$i/$ITERATIONS]${NC} Get Supplier\r"
            fi
            ;;
        9)      # 10% - List Accounts
            if [ $((RANDOM % 2)) -eq 0 ]; then
                curl -s "$API_URL/accounts-payable" > /dev/null
                echo -ne "  ${BLUE}[$i/$ITERATIONS]${NC} List Accounts Payable\r"
            else
                curl -s "$API_URL/accounts-receivable" > /dev/null
                echo -ne "  ${BLUE}[$i/$ITERATIONS]${NC} List Accounts Receivable\r"
            fi
            ;;
    esac
    
    sleep $DELAY
done

echo -e "\n\n${GREEN}✅ Métricas geradas com sucesso!${NC}\n"

# Mostrar estatísticas
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}📊 ESTATÍSTICAS:${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# Buscar métricas
METRICS=$(curl -s http://localhost:9004/metrics)

echo -e "\n${BLUE}HTTP Requests:${NC}"
echo "$METRICS" | grep "^financial_http_requests_total" | head -1

echo -e "\n${BLUE}Suppliers Created:${NC}"
echo "$METRICS" | grep "^financial_suppliers_created_total" | head -1

echo -e "\n${BLUE}Categories Created:${NC}"
echo "$METRICS" | grep "^financial_categories_created_total" | head -1

echo -e "\n${BLUE}Accounts Payable:${NC}"
echo "$METRICS" | grep "^financial_accounts_payable_created_total" | head -1
echo "$METRICS" | grep "^financial_accounts_payable_amount_total" | head -1

echo -e "\n${BLUE}Accounts Receivable:${NC}"
echo "$METRICS" | grep "^financial_accounts_receivable_created_total" | head -1
echo "$METRICS" | grep "^financial_accounts_receivable_amount_total" | head -1

echo -e "\n${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}🔗 Acesse o Grafana:${NC} http://localhost:3000"
echo -e "${GREEN}   Dashboard:${NC} Financial Service"
echo -e "${GREEN}   Usuário:${NC} admin"
echo -e "${GREEN}   Senha:${NC} admin"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"

