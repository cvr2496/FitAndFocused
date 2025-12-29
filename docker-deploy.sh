#!/bin/bash
set -e

echo "🚀 Deploying with Docker..."

# Pull latest code
git pull origin main

# Stop existing containers
docker compose down

# Build and start containers
docker compose up -d --build

echo "✅ Deployment complete!"
echo "🌐 App running at: https://golf-scarlet.exe.xyz"
echo ""
echo "📝 Useful commands:"
echo "  docker compose logs -f         # View logs"
echo "  docker compose restart         # Restart app"
echo "  docker compose down            # Stop app"
echo "  docker compose exec app bash   # Enter container"

