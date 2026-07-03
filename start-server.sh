#!/bin/bash
set -e

# Get the directory of the script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_FILE="$SCRIPT_DIR/.servers.pid"
LOG_FILE="$SCRIPT_DIR/vite-dev.log"

# Read backend URL from .env
BACKEND_URL=$(grep "^APP_URL=" "$SCRIPT_DIR/pontis-api/.env" | cut -d'=' -f2- | tr -d '\r')
if [ -z "$BACKEND_URL" ]; then
    BACKEND_URL="http://pontis.api.local" # Fallback
fi

# Check if servers are already running
if [ -f "$PID_FILE" ]; then
    OLD_PID=$(cat "$PID_FILE")
    if ps -p "$OLD_PID" > /dev/null 2>&1; then
        echo "=================================================="
        echo "Los servidores ya están corriendo (PID: $OLD_PID)."
        echo "Frontend: http://localhost:5173"
        echo "Backend:  $BACKEND_URL"
        echo "=================================================="
        exit 0
    else
        # Stale PID file
        rm -f "$PID_FILE"
    fi
fi

echo "==> Migraciones..."
cd "$SCRIPT_DIR/pontis-api"
php artisan migrate --force

echo "==> Build frontend..."
cd "$SCRIPT_DIR/pontis-app"
npm run build

echo "==> Iniciando dev server..."

# Choose Vite command (prefer local node_modules binary)
if [ -f "./node_modules/.bin/vite" ]; then
    VITE_CMD="./node_modules/.bin/vite"
else
    VITE_CMD="npx vite"
fi

# Start Vite dev server in the background and redirect output to log file
nohup $VITE_CMD > "$LOG_FILE" 2>&1 &
VITE_PID=$!

# Save PID to file
echo "$VITE_PID" > "$PID_FILE"

# Wait for the log file to contain "Local" or timeout after 5 seconds
TIMEOUT=5
COUNT=0
FRONTEND_URL="http://localhost:5173" # Fallback

while [ $COUNT -lt $TIMEOUT ]; do
    if ! ps -p "$VITE_PID" > /dev/null 2>&1; then
        echo "Error: El servidor de desarrollo de Vite no pudo iniciarse."
        echo "Revisa el archivo de log para más detalles: $LOG_FILE"
        rm -f "$PID_FILE"
        exit 1
    fi
    if [ -f "$LOG_FILE" ] && grep -q -E "(Local:|Local)" "$LOG_FILE"; then
        RAW_URL=$(grep -E "(Local:|Local)" "$LOG_FILE" | head -n 1 | awk '{print $NF}')
        CLEANED_URL=$(echo "$RAW_URL" | sed 's/\x1b\[[0-9;]*m//g' | tr -d '\r\n')
        if [ -n "$CLEANED_URL" ]; then
            FRONTEND_URL="$CLEANED_URL"
        fi
        break
    fi
    sleep 0.5
    COUNT=$((COUNT + 1))
done

echo "==> Servidores iniciados con éxito en segundo plano!"
echo "--------------------------------------------------"
echo "Frontend (React):   $FRONTEND_URL"
echo "Backend (Laravel):  $BACKEND_URL"
echo "--------------------------------------------------"
echo "Logs de Vite en: $LOG_FILE"
echo "Para detener los servidores ejecuta: ./stop-servers.sh"

