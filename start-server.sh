#!/bin/bash
set -e

echo "==> Migraciones..."
cd "$(dirname "$0")/pontis-api"
php artisan migrate --force

echo "==> Build frontend..."
cd "../pontis-app"
npm run build

echo "==> Iniciando dev server..."
npm run dev
