#!/bin/bash

# ==============================================================================
# Build Auth Service Docker Image
# ==============================================================================

set -e

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                                                               ║"
echo "║           🐳 Building Auth Service Docker Image              ║"
echo "║                                                               ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Change to project root
cd "$(dirname "$0")/.."

# Build production image
echo "📦 Building production image..."
docker build \
    -t auth-service:latest \
    -t auth-service:1.0.0 \
    -f services/auth-service/Dockerfile \
    services/auth-service/

echo ""
echo "✅ Production image built successfully!"
echo ""

# Build development image
echo "📦 Building development image..."
docker build \
    -t auth-service:dev \
    -f services/auth-service/Dockerfile.dev \
    services/auth-service/

echo ""
echo "✅ Development image built successfully!"
echo ""

# Show images
echo "📋 Created images:"
docker images | grep "auth-service"

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                                                               ║"
echo "║              ✅ Build completed successfully!                ║"
echo "║                                                               ║"
echo "╚═══════════════════════════════════════════════════════════════╝"

