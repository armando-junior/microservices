#!/bin/bash

# Script para limpar completamente a infraestrutura

set -e

echo "========================================="
echo "Limpeza Completa - ERP Microservices"
echo "========================================="
echo ""
echo "⚠️  ATENÇÃO: Este script irá:"
echo "   - Parar todos os containers"
echo "   - Remover todos os volumes (DADOS SERÃO PERDIDOS)"
echo "   - Remover networks"
echo ""
read -p "Deseja continuar? (digite 'sim' para confirmar): " confirm

if [ "$confirm" != "sim" ]; then
    echo "❌ Operação cancelada"
    exit 0
fi

echo ""
echo "🧹 Iniciando limpeza completa..."
echo ""

# Parar e remover containers
echo "🛑 Parando containers..."
docker compose down -v

# Remover volumes órfãos
echo "🗑️  Removendo volumes órfãos..."
docker volume prune -f

# Remover networks órfãs
echo "🌐 Removendo networks órfãs..."
docker network prune -f

# Remover imagens não utilizadas (opcional - comentado por segurança)
# echo "🖼️  Removendo imagens não utilizadas..."
# docker image prune -a -f

echo ""
echo "✅ Limpeza completa realizada!"
echo ""
echo "💡 Para iniciar novamente:"
echo "   ./scripts/start.sh"
echo ""

