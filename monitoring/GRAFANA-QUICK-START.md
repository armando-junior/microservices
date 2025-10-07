# 📊 Grafana - Guia Rápido de Visualização

## 🚀 Acesso Rápido

**URL:** http://localhost:3000

**Credenciais:**
- **Usuário:** `admin`
- **Senha:** `admin123`

**Dashboard Principal:**
- **URL Direta:** http://localhost:3000/d/microservices-overview
- **Nome:** Microservices Overview
- **Auto-provisionado:** Sim (carrega automaticamente)

---

## 📋 Passo a Passo para Visualizar Dashboards

### 1️⃣ **Acessar o Grafana**

1. Abra seu navegador
2. Acesse: http://localhost:3000
3. Faça login com as credenciais acima

### 2️⃣ **Visualizar Dashboards Provisionados**

Temos um dashboard **automático** já configurado:

#### **Opção A: Via Menu Lateral**

1. No menu lateral esquerdo, clique em **"Dashboards"** (ícone de 4 quadrados)
2. Você verá a lista de dashboards disponíveis
3. Clique em **"Microservices Overview"**

#### **Opção B: Via Busca**

1. Pressione `Ctrl + K` (ou `Cmd + K` no Mac)
2. Digite: `Microservices Overview`
3. Pressione Enter

#### **Opção C: URL Direta**

Após fazer login, acesse diretamente:
```
http://localhost:3000/dashboards
```

---

## 📊 Dashboard "Microservices Overview"

Este dashboard foi **provisionado automaticamente** e contém:

### **Painéis Disponíveis:**

1. **📊 Services Status**
   - Status UP/DOWN de cada serviço

2. **📈 Total HTTP Requests (Rate 5m)**
   - Taxa de requisições HTTP por serviço

3. **❌ Error Rate (%)**
   - Percentual de erros HTTP por serviço

4. **⏱️ Response Time (avg)**
   - Tempo médio de resposta por serviço

5. **💾 Memory Usage**
   - Uso de memória PHP por serviço

6. **📦 Business Metrics - Orders**
   - Pedidos criados
   - Pedidos confirmados
   - Pedidos cancelados

7. **📦 Business Metrics - Products**
   - Produtos criados
   - Ajustes de estoque
   - Produtos com estoque baixo

8. **🔐 Business Metrics - Auth**
   - Tentativas de login
   - Logins bem-sucedidos
   - Logins falhados
   - Usuários registrados

---

## 🔄 Como Atualizar os Dados

### **Opção 1: Refresh Automático**

No canto superior direito do dashboard:
1. Clique no dropdown de refresh (padrão: "Off")
2. Selecione: `5s`, `10s`, `30s`, `1m`, etc.
3. O dashboard atualizará automaticamente

### **Opção 2: Refresh Manual**

Clique no ícone de **refresh** (↻) no canto superior direito

---

## ⏰ Time Range (Intervalo de Tempo)

No canto superior direito:
1. Clique no seletor de tempo (padrão: "Last 6 hours")
2. Escolha:
   - **Last 5 minutes** - Dados recentes
   - **Last 15 minutes** - Análise curta
   - **Last 1 hour** - Análise média
   - **Last 6 hours** - Padrão
   - **Last 24 hours** - Análise diária
   - **Custom range** - Período customizado

---

## 🎨 Customizar Dashboards

### **Adicionar Novo Painel:**

1. No dashboard, clique em **"Add"** → **"Visualization"**
2. Selecione a fonte de dados: **Prometheus**
3. Escreva sua query PromQL, por exemplo:
   ```promql
   rate(http_requests_total[5m])
   ```
4. Clique em **"Apply"**

### **Editar Painel Existente:**

1. Passe o mouse sobre o painel
2. Clique no título do painel
3. Selecione **"Edit"**
4. Faça suas alterações
5. Clique em **"Save"** (ícone de disquete no topo)

---

## 📥 Importar Dashboards Pré-Prontos

### **Da Comunidade Grafana:**

1. Acesse: https://grafana.com/grafana/dashboards/
2. Busque por dashboards relevantes:
   - **Node Exporter Full** (ID: 1860)
   - **Redis Dashboard** (ID: 11835)
   - **RabbitMQ** (ID: 10991)
   - **PostgreSQL** (ID: 9628)

3. No Grafana:
   - Menu → **"Dashboards"** → **"Import"**
   - Cole o **Dashboard ID** ou faça **upload do JSON**
   - Clique em **"Load"**
   - Selecione a fonte de dados: **Prometheus**
   - Clique em **"Import"**

---

## 🔍 Explorando Métricas (Explore)

1. Menu lateral → **"Explore"** (ícone de bússola)
2. Selecione: **Prometheus**
3. Digite queries PromQL, exemplos:

```promql
# Taxa de requisições HTTP por serviço
rate(http_requests_total[5m])

# Tempo de resposta médio
avg(http_request_duration_seconds) by (service)

# Taxa de erro
rate(http_requests_total{status=~"5.."}[5m])

# Uso de memória
php_memory_usage_bytes

# Pedidos criados hoje
increase(sales_orders_created_total[24h])
```

---

## 🚨 Visualizar Alertas

1. Menu lateral → **"Alerting"** → **"Alert rules"**
2. Veja todos os alertas configurados no Prometheus
3. Estados possíveis:
   - 🟢 **Normal** - Tudo OK
   - 🟡 **Pending** - Alerta em avaliação
   - 🔴 **Firing** - Alerta ativo

**Nota:** Os alertas são gerenciados pelo **Prometheus** e **Alertmanager**.

Para ver alertas ativos:
- **Prometheus:** http://localhost:9090/alerts
- **Alertmanager:** http://localhost:9093

---

## 📊 Painéis Recomendados para Criar

### **1. Painel de Negócio - Vendas**
```promql
# Total de vendas do dia
sum(increase(sales_orders_confirmed_total[24h]))

# Ticket médio
sum(increase(sales_order_total_value[24h])) / sum(increase(sales_orders_confirmed_total[24h]))
```

### **2. Painel de Performance**
```promql
# Latência P95
histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))

# Taxa de requisições
sum(rate(http_requests_total[5m])) by (service)
```

### **3. Painel de Infraestrutura**
```promql
# CPU por container
rate(container_cpu_usage_seconds_total[5m])

# Memória por container
container_memory_usage_bytes
```

---

## 🛠️ Troubleshooting

### **Dashboard não aparece:**

1. Verifique se o Grafana está rodando:
   ```bash
   docker compose ps grafana
   ```

2. Verifique os logs:
   ```bash
   docker compose logs grafana | tail -50
   ```

3. Verifique o provisionamento:
   ```bash
   docker compose exec grafana ls -la /etc/grafana/provisioning/dashboards/
   docker compose exec grafana ls -la /var/lib/grafana/dashboards/
   ```

4. Reinicie o Grafana:
   ```bash
   docker compose restart grafana
   ```

### **Sem dados nos painéis:**

1. Verifique se o Prometheus está coletando métricas:
   - Acesse: http://localhost:9090/targets
   - Todos os targets devem estar **UP**

2. Gere tráfego nos serviços:
   ```bash
   ./scripts/test-auth-api.sh
   ```

3. Verifique se as métricas estão expostas:
   ```bash
   curl http://localhost:8000/metrics
   curl http://localhost:8001/metrics
   curl http://localhost:8002/metrics
   ```

### **Erro de conexão com Prometheus:**

1. Verifique se o Prometheus está rodando:
   ```bash
   docker compose ps prometheus
   ```

2. Teste a conexão:
   ```bash
   curl http://localhost:9090/-/healthy
   ```

3. No Grafana:
   - Menu → **"Configuration"** → **"Data sources"**
   - Clique em **"Prometheus"**
   - Clique em **"Test"** (deve mostrar "Data source is working")

---

## 🎯 Dicas e Atalhos

| Atalho | Ação |
|--------|------|
| `Ctrl + K` | Busca global |
| `Ctrl + S` | Salvar dashboard |
| `d` | Abrir dropdown de dashboard |
| `t` | Abrir seletor de tempo |
| `r` | Refresh manual |
| `Esc` | Fechar painel de edição |

---

## 📚 Recursos Adicionais

- **Documentação Grafana:** https://grafana.com/docs/
- **Dashboards Prontos:** https://grafana.com/grafana/dashboards/
- **PromQL Básico:** https://prometheus.io/docs/prometheus/latest/querying/basics/
- **Grafana Academy:** https://grafana.com/tutorials/

---

## 🚀 Próximos Passos

1. ✅ Explorar o dashboard "Microservices Overview"
2. ✅ Gerar carga nos serviços para ver métricas em ação
3. ✅ Importar dashboards da comunidade
4. ✅ Criar painéis customizados para seu negócio
5. ✅ Configurar alertas visuais no Grafana

---

**💡 Dica:** Mantenha o dashboard aberto em uma segunda tela ou aba para monitoramento em tempo real!

